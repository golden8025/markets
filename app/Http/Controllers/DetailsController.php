<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\Visit;
use App\Models\VisitInfo;
use App\Models\ProductStock;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DetailsController extends Controller
{
// Получить продукты в наличии для магазина
    public function getStocks($marketId)
    {
        $stocks = DB::table('products')
            ->leftJoin('product_stocks', function($join) use ($marketId) {
                $join->on('products.id', '=', 'product_stocks.product_id')
                    ->where('product_stocks.market_id', '=', $marketId);
            })
            ->select('products.id', 'products.name', DB::raw('COALESCE(product_stocks.qty, 0) as qty'))->get();

        return response()->json($stocks);
    }

   
    public function updateStocks(Request $request, $marketId)
    {
        $items = $request->input('stocks'); // [{product_id: 1, qty: 0}, {product_id: 2, qty: 10}]

        DB::transaction(function () use ($items, $marketId) {
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $qty = (int)$item['qty'];

                if ($qty <= 0) {
                    // Если количество 0 или меньше — удаляем связь
                    DB::table('product_stocks')
                        ->where('market_id', $marketId)
                        ->where('product_id', $productId)
                        ->delete();
                } else {
                    // Если больше 0 — обновляем или создаем
                    DB::table('product_stocks')->updateOrInsert(
                        ['market_id' => $marketId, 'product_id' => $productId],
                        ['qty' => $qty, 'updated_at' => now()]
                    );
                }
            }
        });

        return response()->json(['message' => 'Zaxira yangilandi']);
    }


    public function details(string $id)
    {
        $market = Market::with('group')->findOrFail($id);
        
        // Периоды
        $dateMonthAgo = now()->subDays(29)->startOfDay();
        $dateWeekAgo = now()->subDays(6)->startOfDay(); 

        // 1. Сводная статистика за 30 дней
        // Мы джойним продукты, чтобы получить актуальную цену для расчета долга (minus)
        $summary = DB::table('visit_infos')
            ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
            ->join('products', 'visit_infos.product_id', '=', 'products.id')
            ->where('visits.market_id', $id)
            ->where('visits.created_at', '>=', $dateMonthAgo)
            ->select(
                DB::raw('COUNT(DISTINCT visits.id) as total_visits'),
                DB::raw('SUM(visit_infos.profit) as total_profit'),
                // ТЕПЕРЬ ИСПОЛЬЗУЕМ ПОЛЕ sold
                DB::raw('SUM(visit_infos.sold) as total_sold_qty'),
                // Расчет общего долга (минуса) за месяц: 
                // Сумма всех (продано * цена - профит), где это значение > 0
                DB::raw('SUM(GREATEST(0, (visit_infos.sold * products.price) - visit_infos.profit)) as total_minus'),
                // Количество товаров, по которым есть долг
                //DB::raw('SUM(GREATEST (visit_infos.sold * products.price) > visit_infos.profit THEN 1 END) as debt_products_count')
                DB::raw("SUM(
                        CASE 
                            WHEN (visit_infos.sold * products.price) > visit_infos.profit 
                            THEN ((visit_infos.sold * products.price) - visit_infos.profit) / NULLIF(products.price, 0)
                            ELSE 0 
                        END
                    ) as debt_products_count")
            
            )->first();

        // 2. Данные для графика (Последние 7 дней)
        $rawDailyStats = DB::table('visits')
            ->join('visit_infos', 'visits.id', '=', 'visit_infos.visit_id')
            ->where('visits.market_id', $id)
            ->where('visits.created_at', '>=', $dateWeekAgo)
            ->select(
                DB::raw('DATE(visits.created_at) as date'),
                DB::raw('SUM(profit) as profit')
            )
            ->groupBy('date')
            ->pluck('profit', 'date');

        // $dailyStats = [];
        // for ($i = 0; $i < 7; $i++) {
        //     $date = $dateWeekAgo->copy()->addDays($i)->format('Y-m-d');
        //     $dailyStats[] = [
        //         'date' => $date,
        //         'profit' => (int)($rawDailyStats[$date] ?? 0),
        //     ];
        // }
        // 2. Данные для графика (Последние 7 визитов)
        
        $dailyStats = DB::table('visits')
            ->join('visit_infos', 'visits.id', '=', 'visit_infos.visit_id')
            ->where('visits.market_id', $id)
            ->select(
                'visits.id',
                DB::raw('DATE(visits.created_at) as date'),
                DB::raw('SUM(visit_infos.profit) as profit')
            )
            ->groupBy('visits.id', 'visits.created_at')
            ->orderBy('visits.created_at', 'desc')
            ->limit(7)
            ->get()
            ->reverse()  // oldest → newest for chart left-to-right order
            ->values()
            ->map(fn($row) => [
                'date'   => $row->date,
                'profit' => (int)$row->profit,
            ]);

        // 3. Текущие остатки в магазине
        $stocks = DB::table('product_stocks')
                ->join('products', 'product_stocks.product_id', '=', 'products.id')
                ->where('product_stocks.market_id', $id)
                ->select('products.name', 'product_stocks.qty', 'products.price')
                ->get();

        $lastComment = DB::table('visits')
            ->where('market_id', $id)
            ->orderBy('created_at', 'desc')
            ->value('comment');    
            
            
        return response()->json([
            'market' => [
                'id' => $market->id,
                'name' => $market->name,
                'key'  => $market->key,
                'group_name' => $market->group?->name,
                'type_label' => ucfirst($market->type),
                'latitude' => $market->latitude,
                'longitude' => $market->longitude,
                'comment' => $lastComment,
            ],
            'statistics' => [
                'summary' => [
                    'total_visits' => (int)($summary->total_visits ?? 0),
                    'total_profit' => (int)($summary->total_profit ?? 0),
                    'total_minus'  => (int)($summary->total_minus ?? 0),
                    'total_sold_qty' => (int)($summary->total_sold_qty ?? 0),
                    'debt_products_qty' => (int)($summary->debt_products_count ?? 0),
                ],
                'daily_stats' => $dailyStats,
            ],
            'stocks' => $stocks,
        ]);
    }
}
