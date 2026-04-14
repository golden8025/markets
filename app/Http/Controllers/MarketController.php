<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMarketRequest;
use App\Http\Requests\UpdateMarketRequest;
use App\Http\Resources\MarketResource;
use App\Models\Market;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use App\Models\Visit;
use App\Models\VisitInfo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class MarketController extends Controller
{
    use ApiResponses;

    public function index()
    {
        return response()->json(Market::with(['products', 'users'])->get());
        // return MarketResource::collection(Market::all());
    }

    // public function all_markets(){
    //     $markets = Market::all();
    //     return MarketResource::collection($markets);
    // }

    public function all_markets()
    {
        $user = Auth::user();

        $markets = Market::query()
            // Если пользователь — агент, показываем только привязанные к нему маркеты
            ->when($user->role === 'agent', function ($query) use ($user) {
                return $query->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            })
            ->get();

        return MarketResource::collection($markets);
    }

    // public function store(StoreMarketRequest $request)
    // {

    //     $market = Market::create($request->validated());

    //     return response()->json([
    //         'message' => 'Yaratildi',
    //         // 'data'    => $market
    //     ], 201);
    // }

    public function store(StoreMarketRequest $request)
    {
        if (!Auth::user()?->permission) {
            return response()->json(['message' => 'Sizda xuquq yoq'], 403);
        }
        // 1. Создаем маркет
        $market = Market::create($request->validated());

        // 2. Получаем текущего пользователя
        $user = Auth::user();

        // 3. Если создатель — агент, привязываем его к этому маркету
        if ($user && $user->role === 'agent') {
            // Метод attach() добавит запись в таблицу market_users
            $market->users()->attach($user->id);
        }

        return response()->json([
            'message' => 'Yaratildi',
            'market_id' => $market->id // Полезно вернуть ID для Flutter
        ], 201);
    }



    public function show(string $id)
    {
        $market = Market::with('products')->find($id);
        
        if (!$market) {
            return response()->json([
                'message' => 'Topilmadi'
            ], 404);
        }

        $comment = $market->latestVisit?->comment;

        return response()->json([
            'id'        => $market->id,
            'name'      => $market->name,
            'key'       => $market->key,
            'type'      => $market->type,
            'latitude'  => $market->latitude,
            'longitude' => $market->longitude,
            'comment'   => $comment,
            'products'  => $market->products,
            
        ]);
    }

    public function details(string $id)
    {
        $market = Market::with(['products'])->findOrFail($id);

        $lastVisit = Visit::where('market_id', $id)
            ->with('infos')
            ->orderBy('visit_date', 'desc')
            ->first();



        $lastStats = [
            'profit' => 0,
            'debt' => 0,
            'sold_qty' => 0,
            'minus_qty' => 0,
        ];

        if ($lastVisit) {
            $lastStats['profit'] = $lastVisit->infos->sum('profit');
            $lastStats['debt'] = $lastVisit->infos->sum('debt');
            $lastStats['sold_qty'] = $lastVisit->infos->sum('sold_qty');
            $lastStats['minus_qty'] = $lastVisit->infos->where('qty', '<', 0)->count();
        }

        $monthStats = Visit::where('market_id', $id)
            ->whereMonth('visit_date', now()->month)
            ->join('visit_infos', 'visits.id', '=', 'visit_infos.visit_id')
            ->selectRaw('
                SUM(visit_infos.profit) as total_profit,
                SUM(visit_infos.debt) as total_debt,
                SUM(visit_infos.sold_qty) as total_sold
            ')
            ->first();

        return response()->json([
            'market' => $market,
            'last_visit' => $lastStats,
            'month_summary' => [
                'profit' => $monthStats->total_profit ?? 0,
                'debt' => $monthStats->total_debt ?? 0,
                'sold' => $monthStats->total_sold ?? 0,
            ]
        ]);
    }

    public function dashboard()
    {
        $today = now()->startOfDay();
        $startOfMonth = now()->startOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();

        $tenDaysAgo = now()->subDays(10)->startOfDay(); 

        // 1. Статистика за сегодня + расчет "минуса" через новое поле sold
        $todayStats = DB::table('visit_infos')
            ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
            ->join('products', 'visit_infos.product_id', '=', 'products.id')
            // ->whereDate('visits.created_at', $today)
            ->whereBetween('visits.created_at', [$tenDaysAgo, now()])
            ->select(
                DB::raw('SUM(visit_infos.profit) as total_profit'),
                DB::raw('SUM(visit_infos.loaded) as total_loaded'),
                DB::raw('SUM(visit_infos.sold) as total_sold'), // Используем новое поле
                // Минус: (sold * цена) - прибыль. 
                DB::raw('SUM(GREATEST(0, (visit_infos.sold * products.price) - visit_infos.profit)) as today_minus')
            )->first();

        // 2. Общий "минус" за все время (упрощенный запрос)
        $totalMinus = DB::table('visit_infos')
            ->join('products', 'visit_infos.product_id', '=', 'products.id')
            ->select(DB::raw('SUM(GREATEST(0, (visit_infos.sold * products.price) - visit_infos.profit)) as total'))
            ->value('total') ?? 0;

        // 3. Сравнение продаж (Текущий месяц vs Прошлый)
        $thisMonthSold = DB::table('visit_infos')
            ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
            ->whereBetween('visits.created_at', [$startOfMonth, now()])
            ->sum('sold'); // Прямое суммирование по полю

        $lastMonthSold = DB::table('visit_infos')
            ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
            ->whereBetween('visits.created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('sold');

        // 4. Сравнение "минуса" (последние 30 дней vs предыдущие 30 дней)
        $calculateMinusSql = function ($startDate, $endDate) {
            return DB::table('visit_infos')
                ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
                ->join('products', 'visit_infos.product_id', '=', 'products.id')
                ->whereBetween('visits.created_at', [$startDate, $endDate])
                ->select(DB::raw('SUM(GREATEST(0, (visit_infos.sold * products.price) - visit_infos.profit)) as total'))
                ->value('total') ?? 0;
        };

        $now = now();
        $thirtyDaysAgo = now()->subDays(30);
        $sixtyDaysAgo = now()->subDays(60);

        $currentMonthMinus = $calculateMinusSql($thirtyDaysAgo, $now);
        $lastMonthMinus = $calculateMinusSql($sixtyDaysAgo, $thirtyDaysAgo);

        $diffMinus = $currentMonthMinus - $lastMonthMinus;
        $diffSold = $thisMonthSold - $lastMonthSold;

        return response()->json([
            'today_profit'      => round(($todayStats->total_profit ?? 0) / 1000000, 2),
            'sale_points'       => DB::table('markets')->count(),
            'loaded_products'   => (int)($todayStats->total_loaded ?? 0),
            'sold_products'     => (int)($todayStats->total_sold ?? 0),
            'today_minus'       => number_format($todayStats->today_minus ?? 0, 0, '.', ' '),
            'total_minus'       => number_format($totalMinus, 0, '.', ' '),
            
            'diff_products'     => ($diffSold >= 0 ? '+' : '') . $diffSold, 
            'diff_minus'        => ($diffMinus >= 0 ? '+' : '') . number_format($diffMinus, 0, '.', ' '),
            'diff_profit'       => "+0.5", 
        ]);
    }
   

    public function statistics() 
    {
        $days = ['Yak', 'Du', 'Se', 'Chor', 'Pa', 'Ju', 'Sha'];
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        
        // 1. Получаем все данные за неделю одним запросом
        $rawStats = DB::table('visit_infos')
            ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
            ->where('visits.created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(visits.created_at) as date'),
                // ТЕПЕРЬ ИСПОЛЬЗУЕМ ПОЛЕ sold
                DB::raw('SUM(visit_infos.sold) as total_sales'),
                DB::raw('SUM(visit_infos.profit) as total_income'),
                DB::raw('COUNT(DISTINCT visits.id) as visits_count')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $weeklyData = [];
        $totalVisitsCount = 0;

        // 2. Формируем массив для фронтенда, заполняя пустые дни нулями
        for ($i = 6; $i >= 0; $i--) {
            $dateObj = Carbon::now()->subDays($i);
            $dateString = $dateObj->format('Y-m-d');
            $dayName = $days[$dateObj->dayOfWeek];

            $dayData = $rawStats->get($dateString);

            $weeklyData[] = [
                'day'    => $dayName,
                'sales'  => (int)($dayData->total_sales ?? 0),  // Кол-во проданного товара
                'income' => (int)($dayData->total_income ?? 0), // Полученные деньги
            ];

            $totalVisitsCount += ($dayData->visits_count ?? 0);
        }

        $totalIncome = collect($weeklyData)->sum('income');
        
        // Средний доход на один визит
        $avgIncome = $totalVisitsCount > 0 ? $totalIncome / $totalVisitsCount : 0;

        return response()->json([
            'weekly_data'  => $weeklyData,
            'total_visits' => $totalVisitsCount,
            'avg_income'   => (int)round($avgIncome),
        ]);
    }

   
    public function update(Request $request, string $id)
    {
        try{
            $market = Market::findOrFail($id);

      
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'type' => [
                    'sometimes', 
                    'required', 
                    Rule::in(['metan', 'propan', 'dokon'])
                ],
                'key' => 'nullable|string|max:255',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            $market->update($validated);

            return response()->json([
                'message' => 'Malumotlar yangilandi',
                
            ]);
        }
        catch(Exception $ex){
            return $this->error($ex);
        }
        
    }

    public function groupUpdate(Request $request, string $id)
    {
        
        $market = Market::findOrFail($id);

        
        $validated = $request->validate([
            'group_id' => 'sometimes|required|exists:groups,id',
            'name' => 'sometimes|required|string',
        ]);

        
        $market->update($validated);

        return response()->json([
            'message' => 'Malumot yangilandi',
            'market' => $market->load('group') 
        ]);
    }

    public function productUpdate(Request $request, string $id)
    {
        $market = Market::findOrFail($id);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'change_qty' => 'required|integer',
        ]);

        $productId = $validated['product_id'];
        $change = $validated['change_qty'];

        
        $productPivot = $market->products()->where('product_id', $productId)->first();

        if ($productPivot) {
            
            $currentQty = $productPivot->pivot->qty;
            $newQty = $currentQty + $change;

            
            if ($newQty < 0) {
                return response()->json(['error' => 'Maxsulotlar soni yetarli emas'], 400);
            }

            // Обновляем существующую запись в product_stocks
            $market->products()->updateExistingPivot($productId, [
                'qty' => $newQty
            ]);
        } else {
            // Если продукта в маркете еще нет, и мы хотим "прибавить"
            if ($change < 0) {
                return response()->json(['error' => 'Bu sotuv nuqtasida, bunday maxsulot topilmadi'], 400);
            }
            $market->products()->attach($productId, ['qty' => $change]);
        }

        return response()->json([
            'message' => 'Maxsulot soni uzgardi',
            'new_qty' => $newQty ?? $change
        ]);
    }

    // public function update_product(Request $request, string $id)
    // {
    //     $market = Market::findOrFail($id);

    //     $validated = $request->validate([
    //         'product_id' => 'required|exists:products,id',
    //         'qty' => 'required|integer|min:0',
    //     ]);

    //     $market->products()->syncWithoutDetaching([
    //         $validated['product_id'] => ['qty' => $validated['qty']]
    //     ]);

    //     return response()->json([
    //         'message' => 'Maxsulotlar soni yangilandi',
    //         'market' => $market->load('products')
    //     ]);
    // }


    
    public function destroy(string $id)
    {
        try{
            $market = Market::findOrFail($id);
            $market->delete();
            return $this->ok('Uchirildi');
        }
        catch(ModelNotFoundException $ex){
            return $this->ok('Topilmadi');
        }
    }
    
    public function products(string $id){
        $market = Market::findOrFail($id);
        $products = $market->products;

        return response()->json($products);
    }

    
   
}
