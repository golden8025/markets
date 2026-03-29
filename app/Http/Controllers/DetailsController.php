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
    /**
     * Детальная информация о маркете:
     * - Основные данные маркета
     * - Продукты из последнего визита (доход, продано, убыток)
     * - Статистика за последние 30 дней
     */
    // public function details(int $id): JsonResponse
    // {
    //     // ── 1. Основные данные маркета ────────────────────────────────────────
    //     $market = Market::with('group')->findOrFail($id);

    //     // ── 2. Последний визит этого маркета ─────────────────────────────────
    //     $lastVisit = Visit::where('market_id', $id)
    //         ->with([
    //             'info.product',
    //             'visitImages',
    //             'user:id,name',
    //         ])
    //         ->latest()
    //         ->first();

    //     $lastVisitData = null;

    //     if ($lastVisit) {
    //         $products = $lastVisit->info->map(function (VisitInfo $info) {
    //             // Теоретическая выручка = цена × количество проданных
    //             $sold     = $info->loaded - $info->left;   // сколько продано
    //             $expected = $info->product->price * $sold; // ожидаемая выручка
    //             $loss     = $expected - $info->profit;      // убыток (разница)

    //             return [
    //                 'product_id'   => $info->product_id,
    //                 'product_name' => $info->product->name,
    //                 'price'        => $info->product->price,
    //                 'loaded'       => $info->loaded,       // загружено
    //                 'left'         => $info->left,          // осталось
    //                 'sold'         => $sold,                // продано
    //                 'profit'       => $info->profit,        // фактический доход
    //                 'expected'     => $expected,            // ожидаемая выручка
    //                 'loss'         => max(0, $loss),        // убыток (≥0)
    //                 'loss_qty'     => $loss > 0             // количество «убыточных» единиц
    //                     ? (int) round($loss / $info->product->price)
    //                     : 0,
    //             ];
    //         });

    //         $lastVisitData = [
    //             'id'           => $lastVisit->id,
    //             'visited_at'   => $lastVisit->created_at->toDateTimeString(),
    //             'agent'        => $lastVisit->user?->name,
    //             'comment'      => $lastVisit->comment,
    //             'images'       => $lastVisit->visitImages->pluck('image'),
    //             'total_profit' => $products->sum('profit'),
    //             'total_loss'   => $products->sum('loss'),
    //             'products'     => $products,
    //         ];
    //     }

    //     // ── 3. Статистика за последние 30 дней ───────────────────────────────
    //     $from = Carbon::now()->subDays(30)->startOfDay();
    //     $to   = Carbon::now()->endOfDay();

    //     // 3a. Агрегаты по дням (для графика)
    //     $dailyStats = DB::table('visits')
    //         ->join('visit_infos', 'visits.id', '=', 'visit_infos.visit_id')
    //         ->join('products', 'visit_infos.product_id', '=', 'products.id')
    //         ->where('visits.market_id', $id)
    //         ->whereBetween('visits.created_at', [$from, $to])
    //         ->select([
    //             DB::raw('DATE(visits.created_at) as date'),
    //             DB::raw('SUM(visit_infos.profit) as total_profit'),
    //             DB::raw('SUM((visit_infos.loaded - visit_infos.left) * products.price) as total_expected'),
    //             DB::raw('SUM(visit_infos.loaded - visit_infos.left) as total_sold'),
    //             DB::raw('COUNT(DISTINCT visits.id) as visit_count'),
    //         ])
    //         ->groupBy(DB::raw('DATE(visits.created_at)'))
    //         ->orderBy('date')
    //         ->get()
    //         ->map(function ($row) {
    //             $loss = max(0, $row->total_expected - $row->total_profit);
    //             return [
    //                 'date'         => $row->date,
    //                 'profit'       => (int) $row->total_profit,
    //                 'expected'     => (int) $row->total_expected,
    //                 'loss'         => (int) $loss,
    //                 'sold'         => (int) $row->total_sold,
    //                 'visit_count'  => (int) $row->visit_count,
    //             ];
    //         });

    //     // 3b. Итоговые цифры за 30 дней
    //     $summary30 = DB::table('visits')
    //         ->join('visit_infos', 'visits.id', '=', 'visit_infos.visit_id')
    //         ->join('products', 'visit_infos.product_id', '=', 'products.id')
    //         ->where('visits.market_id', $id)
    //         ->whereBetween('visits.created_at', [$from, $to])
    //         ->select([
    //             DB::raw('SUM(visit_infos.profit) as total_profit'),
    //             DB::raw('SUM((visit_infos.loaded - visit_infos.left) * products.price) as total_expected'),
    //             DB::raw('SUM(visit_infos.loaded - visit_infos.left) as total_sold'),
    //             DB::raw('COUNT(DISTINCT visits.id) as total_visits'),
    //         ])
    //         ->first();

    //     $totalExpected = (int) ($summary30->total_expected ?? 0);
    //     $totalProfit   = (int) ($summary30->total_profit   ?? 0);
    //     $totalLoss     = max(0, $totalExpected - $totalProfit);

    //     // 3c. Топ продуктов за 30 дней
    //     $topProducts = DB::table('visits')
    //         ->join('visit_infos', 'visits.id', '=', 'visit_infos.visit_id')
    //         ->join('products', 'visit_infos.product_id', '=', 'products.id')
    //         ->where('visits.market_id', $id)
    //         ->whereBetween('visits.created_at', [$from, $to])
    //         ->select([
    //             'products.id',
    //             'products.name',
    //             'products.price',
    //             DB::raw('SUM(visit_infos.loaded - visit_infos.left) as total_sold'),
    //             DB::raw('SUM(visit_infos.profit) as total_profit'),
    //             DB::raw('SUM((visit_infos.loaded - visit_infos.left) * products.price) as total_expected'),
    //         ])
    //         ->groupBy('products.id', 'products.name', 'products.price')
    //         ->orderByDesc('total_sold')
    //         ->limit(5)
    //         ->get()
    //         ->map(function ($p) {
    //             $loss = max(0, $p->total_expected - $p->total_profit);
    //             return [
    //                 'product_id'   => $p->id,
    //                 'product_name' => $p->name,
    //                 'price'        => $p->price,
    //                 'total_sold'   => (int) $p->total_sold,
    //                 'total_profit' => (int) $p->total_profit,
    //                 'total_loss'   => (int) $loss,
    //             ];
    //         });

    //     // 3d. Текущие остатки (product_stocks)
    //     $stocks = DB::table('product_stocks')
    //         ->join('products', 'product_stocks.product_id', '=', 'products.id')
    //         ->where('product_stocks.market_id', $id)
    //         ->select('products.id', 'products.name', 'products.price', 'product_stocks.qty')
    //         ->get();

    //     // ── 4. Сборка ответа ──────────────────────────────────────────────────
    //     return response()->json([
    //         'market' => [
    //             'id'        => $market->id,
    //             'name'      => $market->name,
    //             'key'       => $market->key,
    //             'type'      => $market->type,
    //             'latitude'  => $market->latitude,
    //             'longitude' => $market->longitude,
    //             'group'     => $market->group?->name,
    //         ],
    //         'last_visit' => $lastVisitData,
    //         'statistics' => [
    //             'period_days'  => 30,
    //             'from'         => $from->toDateString(),
    //             'to'           => $to->toDateString(),
    //             'summary' => [
    //                 'total_visits'   => (int) ($summary30->total_visits ?? 0),
    //                 'total_sold'     => (int) ($summary30->total_sold   ?? 0),
    //                 'total_profit'   => $totalProfit,
    //                 'total_expected' => $totalExpected,
    //                 'total_loss'     => $totalLoss,
    //             ],
    //             'daily'        => $dailyStats,
    //             'top_products' => $topProducts,
    //             'stocks'       => $stocks,
    //         ],
    //     ]);
    // }

    public function details($id)
    {
        // 1. Основная информация о магазине с его группой
        $market = Market::with('group')->findOrFail($id);

        // 2. Данные последнего визита
        $lastVisit = Visit::where('market_id', $id)
            ->with(['user', 'info.product']) // Данные об агенте и продуктах [cite: 221, 226]
            ->latest()
            ->first();

        // 3. Статистика за последние 30 дней (как требует экран [cite: 21])
        $dateFrom = now()->subDays(30);
        
        // Агрегируем общие показатели
        $statsSummary = DB::table('visit_infos')
            ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
            ->where('visits.market_id', $id)
            ->where('visits.created_at', '>=', $dateFrom)
            ->select(
                DB::raw('COUNT(DISTINCT visits.id) as total_visits'),
                DB::raw('SUM(loaded - `left`) as total_sold_qty'), // Продано = Загружено - Остаток 
                DB::raw('SUM(profit) as total_profit')
            )->first();

        // Кунлик даромад (для графика [cite: 111])
        $dailyStats = DB::table('visits')
            ->join('visit_infos', 'visits.id', '=', 'visit_infos.visit_id')
            ->where('visits.market_id', $id)
            ->where('visits.created_at', '>=', $dateFrom)
            ->select(
                DB::raw('DATE(visits.created_at) as date'),
                DB::raw('SUM(profit) as profit')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 4. Текущие остатки (Stocks [cite: 154])
        $stocks = ProductStock::where('market_id', $id)
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->select('products.name', 'product_stocks.qty', 'products.price') // [cite: 223, 224]
            ->get();

        // Формируем JSON ответ под вашу Flutter-модель
        return response()->json([
            'market' => [
                'id' => $market->id,
                'name' => $market->name,
                'group_name' => $market->group?->name,
                'type' => $market->type, // metan, propan, dokon [cite: 219]
                'type_label' => ucfirst($market->type),
                'key' => $market->key,
                'latitude' => $market->latitude,
                'longitude' => $market->longitude,
            ],
            'last_visit' => $lastVisit ? [
                'visited_at' => $lastVisit->created_at->toISOString(),
                'agent_name' => $lastVisit->user->name, // [cite: 216]
                'comment' => $lastVisit->comment,
                'total_profit' => $lastVisit->info->sum('profit'),
                'total_loss' => 0, // Если в БД нет поля "убыток", возвращаем 0
                'products' => $lastVisit->info->map(function($info) {
                    return [
                        'name' => $info->product->name,
                        'price' => $info->product->price,
                        'loaded_qty' => $info->loaded,
                        'left_qty' => $info->left,
                        'sold_qty' => $info->loaded - $info->left,
                        'profit' => $info->profit,
                    ];
                }),
            ] : null,
            'statistics' => [
                'date_from' => $dateFrom->format('d.m.Y'),
                'date_to' => now()->format('d.m.Y'),
                'summary' => [
                    'total_visits' => $statsSummary->total_visits ?? 0,
                    'total_sold_qty' => (int)($statsSummary->total_sold_qty ?? 0),
                    'total_profit' => (int)($statsSummary->total_profit ?? 0),
                    'total_loss' => 0,
                ],
                'daily_stats' => $dailyStats,
            ],
            'stocks' => $stocks
        ]);
    }
}
