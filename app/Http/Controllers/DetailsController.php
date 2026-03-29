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

class DetailsController extends Controller
{
    public function details(string $id)
    {
        $market = Market::with('group')->findOrFail($id);
        $dateFrom = now()->subDays(29)->startOfDay();
        $dateTo = now()->endOfDay();


        $summary = DB::table('visit_infos')
            ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
            ->where('visits.market_id', $id)
            ->where('visits.created_at', '>=', $dateFrom)
            ->select(
                DB::raw('COUNT(DISTINCT visits.id) as total_visits'),
                DB::raw('SUM(visit_infos.profit) as total_profit'),
                DB::raw('SUM(CASE WHEN visit_infos.profit < 0 THEN ABS(visit_infos.profit) ELSE 0 END) as total_loss'),
                DB::raw('SUM(visit_infos.loaded - visit_infos.left) as total_sold_qty'),
                DB::raw('COUNT(CASE WHEN (visit_infos.loaded - visit_infos.left) < 0 THEN 1 END) as loss_products_count')
            )->first();


        $rawDailyStats = DB::table('visits')
            ->join('visit_infos', 'visits.id', '=', 'visit_infos.visit_id')
            ->where('visits.market_id', $id)
            ->where('visits.created_at', '>=', $dateFrom)
            ->select(
                DB::raw('DATE(visits.created_at) as date'),
                DB::raw('SUM(profit) as profit')
            )
            ->groupBy('date')
            ->pluck('profit', 'date'); // Получаем массив ['2026-03-25' => 1500000]

        // Заполняем все 30 дней
        $dailyStats = [];
        for ($i = 0; $i < 30; $i++) {
            $date = $dateFrom->copy()->addDays($i)->format('Y-m-d');
            $dailyStats[] = [
                'date' => $date,
                'profit' => $rawDailyStats[$date] ?? 0, // Если данных нет, ставим 0
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
