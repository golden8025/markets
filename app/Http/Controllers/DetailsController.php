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

    // Изменить количество продуктов (массовое обновление)
    // public function updateStocks(Request $request, $marketId)
    // {
    //     $items = $request->input('stocks'); // Ожидаем массив [{product_id: 1, qty: 10}, ...]

    //     foreach ($items as $item) {
    //         DB::table('product_stocks')->updateOrInsert(
    //             ['market_id' => $marketId, 'product_id' => $item['product_id']],
    //             ['qty' => $item['qty'], 'updated_at' => now()]
    //         );
    //     }

    //     return response()->json(['message' => 'Zaxira yangilandi']);
    // }
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
    
    // Для карточек берем 30 дней
    $dateMonthAgo = now()->subDays(29)->startOfDay();
    // Для графика берем последние 7 дней
    $dateWeekAgo = now()->subDays(6)->startOfDay(); 

    // 1. Сводная статистика за 30 дней (остается без изменений)
    $summary = DB::table('visit_infos')
        ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
        ->where('visits.market_id', $id)
        ->where('visits.created_at', '>=', $dateMonthAgo)
        ->select(
            DB::raw('COUNT(DISTINCT visits.id) as total_visits'),
            DB::raw('SUM(visit_infos.profit) as total_profit'),
            DB::raw('SUM(CASE WHEN visit_infos.profit < 0 THEN ABS(visit_infos.profit) ELSE 0 END) as total_loss'),
            DB::raw('SUM(visit_infos.loaded - visit_infos.left) as total_sold_qty'),
            DB::raw('COUNT(CASE WHEN visit_infos.profit < 0 THEN 1 END) as loss_products_count')
        )->first();

    // 2. Данные для графика (ТОЛЬКО ПОСЛЕДНИЕ 7 ДНЕЙ)
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

    $dailyStats = [];
    for ($i = 0; $i < 7; $i++) {
        $date = $dateWeekAgo->copy()->addDays($i)->format('Y-m-d');
        $dailyStats[] = [
            'date' => $date,
            'profit' => (int)($rawDailyStats[$date] ?? 0),
        ];
    }
    $stocks = DB::table('product_stocks')
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->where('product_stocks.market_id', $id)
            ->select('products.name', 'product_stocks.qty', 'products.price')
            ->get();

        return response()->json([
            'market' => [
                'id' => $market->id,
                'name' => $market->name,
                'group_name' => $market->group?->name,
                'type_label' => ucfirst($market->type),
                'latitude' => $market->latitude,
                'longitude' => $market->longitude,
            ],
            'statistics' => [
                'summary' => [
                    'total_visits' => (int)$summary->total_visits,
                    'total_profit' => (int)$summary->total_profit,
                    'total_loss' => (int)$summary->total_loss,
                    'total_sold_qty' => (int)$summary->total_sold_qty,
                    'loss_products_qty' => (int)$summary->loss_products_count, // Кол-во убыточных товаров
                ],
                'daily_stats' => $dailyStats,
            ],
            'stocks' => $stocks,
        ]);

    // ... остальная часть (stocks и return) остается такой же
}

    // public function details(string $id)
    // {
    //     // 1. Информация о магазине и его группе
    //     $market = Market::with('group')->findOrFail($id);

    //     // 2. Последний визит с продуктами
    //     $lastVisit = Visit::where('market_id', $id)
    //         ->with(['user', 'info.product'])
    //         ->latest()
    //         ->first();

    //     // 3. Статистика за последние 30 дней
    //     $dateFrom = now()->subDays(30);
        
    //     // Общие итоги
    //     $summary = DB::table('visit_infos')
    //         ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
    //         ->where('visits.market_id', $id)
    //         ->where('visits.created_at', '>=', $dateFrom)
    //         ->select(
    //             DB::raw('COUNT(DISTINCT visits.id) as total_visits'),
    //             DB::raw('SUM(visit_infos.profit) as total_profit'),
    //             DB::raw('SUM(visit_infos.loaded - visit_infos.left) as total_sold_qty')
    //         )->first();

    //     // Данные для графика (по дням)
    //     $dailyStats = DB::table('visits')
    //         ->join('visit_infos', 'visits.id', '=', 'visit_infos.visit_id')
    //         ->where('visits.market_id', $id)
    //         ->where('visits.created_at', '>=', $dateFrom)
    //         ->select(
    //             DB::raw('DATE(visits.created_at) as date'),
    //             DB::raw('SUM(profit) as profit')
    //         )
    //         ->groupBy('date')
    //         ->orderBy('date', 'asc')
    //         ->get();

    //     // 4. Текущие остатки в магазине (Stocks)
    //     $stocks = DB::table('product_stocks')
    //         ->join('products', 'product_stocks.product_id', '=', 'products.id')
    //         ->where('product_stocks.market_id', $id)
    //         ->select('products.name', 'product_stocks.qty', 'products.price')
    //         ->get();

    //     return response()->json([
    //         'market' => [
    //             'id' => $market->id,
    //             'name' => $market->name,
    //             'group_name' => $market->group?->name,
    //             'type' => $market->type,
    //             'type_label' => ucfirst($market->type),
    //             'key' => $market->key,
    //             'latitude' => $market->latitude,
    //             'longitude' => $market->longitude,
    //         ],
    //         'statistics' => [
    //             'summary' => [
    //                 'total_visits' => (int)($summary->total_visits ?? 0),
    //                 'total_profit' => (int)($summary->total_profit ?? 0),
    //                 'total_sold_qty' => (int)($summary->total_sold_qty ?? 0),
    //                 'total_loss' => 0,
    //             ],
    //             'daily_stats' => $dailyStats,
    //         ],
    //         'stocks' => $stocks,
    //     ]);
    // }
}
