<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Models\Visit;
use App\Models\VisitInfo;
use App\Models\VisitImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\StoreVisitRequest;
use App\Models\Group;
use App\Models\Market;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class VisitController extends Controller
{




public function index(Request $request)
{
    $user = Auth::user();
    // 1. Формируем запрос с фильтрами
    $visits = Visit::query()
        // Важно: подгружаем info.product для расчетов, чтобы не было N+1
        ->with(['market.group', 'info.product', 'user'])
        ->when($user->role === 'agent', function ($query) use ($user) {
            // Если агент — принудительно фильтруем только его визиты
            return $query->where('user_id', $user->id);
        }, function ($query) use ($request) {
            // Если НЕ агент (админ), разрешаем фильтрацию по выбранному user_id из запроса
            return $query->when($request->user_id, function ($q, $userId) {
                $q->where('user_id', $userId);
            });
        })
        
        // Фильтр по дате "ОТ"
        ->when($request->date_from, function ($query, $date) {
            $query->whereDate('created_at', '>=', $date);
        })
        // Фильтр по дате "ДО"
        ->when($request->date_to, function ($query, $date) {
            $query->whereDate('created_at', '<=', $date);
        })
        // Фильтр по пользователю
        ->when($request->user_id, function ($query, $userId) {
            $query->where('user_id', $userId);
        })
        // Фильтр по магазину
        ->when($request->market_id, function ($query, $marketId) {
            $query->where('market_id', $marketId);
        })
        // Фильтр по группе магазинов
        ->when($request->group_id, function ($query, $groupId) {
        $query->whereHas('market', function ($q) use ($groupId) {
        $q->where('group_id', $groupId);
    });
        })
        ->orderBy('created_at', 'desc')
        ->get();

    // 2. Трансформируем результат (ваша логика расчетов)
    $data = $visits->map(function ($visit) {
        $totals = $visit->info->reduce(function ($carry, $info) {
            $sold = $info->loaded - $info->left;
            $expected = $sold * ($info->product->price ?? 0);
            
            // Считаем "минус"
            $minus = max(0, $expected - $info->profit);

            $carry['sold'] += $sold;
            $carry['profit'] += $info->profit;
            $carry['minus'] += $minus;
            
            return $carry;
        }, ['sold' => 0, 'profit' => 0, 'minus' => 0]);

        return [
            'id'             => $visit->id,
            'market_name'    => $visit->market->name ?? 'Н/Д',
            'visit_time'     => $visit->created_at->format('d.m.Y H:i'),
            'total_sold'     => $totals['sold'],
            'total_profit'   => $totals['profit'],
            'total_minus'    => $totals['minus'],
            'short_comment'  => Str::words($visit->comment, 3, '...'),
        ];
    });

    return response()->json($data);
}

    public function getFilterData() {
        return response()->json([
            'users' => User::all(['id', 'name']),
            'groups' => Group::all(['id', 'name']),
            'markets' => Market::all(['id', 'name', 'group_id']),
        ]);
    }


    public function store(StoreVisitRequest $request)
    {
        return DB::transaction(function () use ($request) {
            
            $visit = Visit::create([
                'user_id'   => auth()->id(), 
                'market_id' => $request->market_id,
                'comment'   => $request->comment,
            ]);

            // 2. Добавляем информацию о товарах (info)
            // Ожидаем массив products: [['id' => 1, 'loaded' => 10, 'left' => 2, 'profit' => 500], ...]
            if ($request->has('products')) {
                foreach ($request->products as $item) {
                    VisitInfo::create([
                        'visit_id'   => $visit->id,
                        'product_id' => $item['id'],
                        'loaded'     => $item['loaded'],
                        'left'       => $item['left'],
                        'profit'     => $item['profit'],
                    ]);
                    
                   
                    ProductStock::updateOrCreate(
                    [
                        'market_id'  => $request->market_id,
                        'product_id' => $item['id'],
                    ],
                    [
                        'qty' => $item['left'] + $item['loaded']
                    ]
                );
                }
            }

            // 3. Сохраняем изображения (visit_images)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('visits', 'public'); // сохранение в storage/app/public/visits
                    
                    VisitImage::create([
                        'visit_id' => $visit->id,
                        'image'    => $path,
                    ]);
                }
            }

            return response()->json(['message' => 'Yaratildi!', 'visit' => $visit], 201);
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Загружаем визит со всеми вложенными связями
        $visit = Visit::with(['market', 'info.product', 'images'])->findOrFail($id);

        $report = $visit->info->map(function ($info) {
            // 1. Считаем сколько реально продано штук
            $soldCount = $info->loaded - $info->left;
            
            // 2. Ожидаемая сумма по прайсу
            $expectedAmount = $soldCount * ($info->product->price ?? 0);
            
            // 3. Расчет "минуса" (недостачи)
            // Если прибыль больше ожидаемого, минус будет 0
            $minus = max(0, $expectedAmount - $info->profit);

            return [
                'product_id'   => $info->product_id,
                'product_name' => $info->product->name ?? 'Удаленный товар',
                'price'        => $info->product->price,
                'loaded'       => $info->loaded,
                'left'         => $info->left,
                'sold'         => $soldCount,
                'profit'       => $info->profit,
                'minus'        => $minus, 
            ];
        });

        return response()->json([
            'market_name'    => $visit->market->name ?? 'Неизвестно',
            'visit_date'     => $visit->created_at->format('d.m.Y H:i'),
            'comment'        => $visit->comment,
            'photos'         => $visit->images->pluck('image')->map(fn($img) => asset('storage/' . $img)),
            
            'products_data'  => $report,
            
            // Итоговые показатели по всему визиту
            'total_profit'   => $report->sum('profit'),
            'total_minus'    => $report->sum('minus'), // Общий минус за весь визит
        ]);
    }



    public function edit($id)
    {
        try {
            // Загружаем визит вместе со связанными данными
            $visit = Visit::with([
                'market', 
                'info.product'
            ])->findOrFail($id);

            return response()->json($visit, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Visit topilmadi',
                'error' => $e->getMessage()
            ], 404);
        }
    }


    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $visit = Visit::with('info')->findOrFail($id);

            // 1. "Откатываем" старое влияние визита на склад
            foreach ($visit->info as $oldInfo) {
                $stock = ProductStock::where('market_id', $visit->market_id)
                    ->where('product_id', $oldInfo->product_id)
                    ->first();
                
                if ($stock) {
                    // Вычитаем то, что было добавлено в прошлый раз
                    $stock->decrement('qty', ($oldInfo->left + $oldInfo->loaded));
                }
            }

            // 2. Обновляем основные данные визита
            $visit->update([
                'comment' => $request->comment,
            ]);

            // 3. Удаляем старые записи о товарах и создаем новые (или обновляем)
            if ($request->has('products')) {
                // Удаляем старые привязки
                $visit->info()->delete();

                foreach ($request->products as $item) {
                    VisitInfo::create([
                        'visit_id'   => $visit->id,
                        'product_id' => $item['id'],
                        'loaded'     => $item['loaded'],
                        'left'       => $item['left'],
                        'profit'     => $item['profit'],
                    ]);

                    // Применяем НОВЫЕ данные к складу
                    ProductStock::updateOrCreate(
                        ['market_id' => $visit->market_id, 'product_id' => $item['id']],
                        ['qty' => DB::raw("qty + " . ($item['left'] + $item['loaded']))]
                    );
                }
            }

            // 4. Добавляем новые изображения, если они есть
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('visits', 'public');
                    VisitImage::create([
                        'visit_id' => $visit->id,
                        'image'    => $path,
                    ]);
                }
            }

            return response()->json(['message' => 'O\'zgartirildi!', 'visit' => $visit->load('info')]);
        });
    }

    public function destroy($id)
    {
        $visit = Visit::findOrFail($id);
        foreach($visit->images as $img) {
            Storage::disk('public')->delete($img->image);
        }
        $visit->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
