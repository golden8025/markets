<?php

namespace App\Http\Controllers;

use App\Models\Market;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatController extends Controller
{
    // public function marketsStatistics()
    // {
    //     $thirtyDaysAgo = Carbon::now()->subDays(30);

    //     // Получаем все магазины и агрегируем данные их визитов
    //     $markets = Market::all()->map(function ($market) use ($thirtyDaysAgo) {
    //         $stats = DB::table('visit_infos')
    //             ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
    //             ->join('products', 'visit_infos.product_id', '=', 'products.id')
    //             ->where('visits.market_id', $market->id) // Используем -> как в PHP
    //             ->where('visits.created_at', '>=', $thirtyDaysAgo)
    //             ->select(
    //                 DB::raw('SUM(visit_infos.profit) as total_profit'),
    //                 DB::raw('COUNT(DISTINCT visits.id) as visit_count'),
    //                 // Считаем минус: (продано * цена) - прибыль
    //                 DB::raw('SUM(GREATEST(0, (visit_infos.loaded - visit_infos.left) * products.price - visit_infos.profit)) as total_minus')
    //             )
    //             ->first();

    //         return [
    //             'id' => $market->id,
    //             'name' => $market->name,
    //             'address' => $market->address ?? 'Manzil ko\'rsatilmagan',
    //             'profit' => (int)($stats->total_profit ?? 0),
    //             'minus' => (int)($stats->total_minus ?? 0),
    //             'visit_count' => (int)($stats->visit_count ?? 0),
    //         ];
    //     });

    //     return response()->json($markets);
    // }


    public function marketsStatistics()
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $markets = Market::all()->map(function ($market) use ($thirtyDaysAgo) {
            $stats = DB::table('visit_infos')
                ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
                ->join('products', 'visit_infos.product_id', '=', 'products.id')
                ->where('visits.market_id', $market->id)
                ->where('visits.created_at', '>=', $thirtyDaysAgo)
                ->select(
                    DB::raw('SUM(visit_infos.profit) as total_profit'),
                    DB::raw('COUNT(DISTINCT visits.id) as visit_count'),
                    // Используем CASE WHEN вместо GREATEST для совместимости с SQLite
                    DB::raw("SUM(
                        CASE 
                            WHEN ((visit_infos.loaded - visit_infos.left) * products.price - visit_infos.profit) > 0 
                            THEN ((visit_infos.loaded - visit_infos.left) * products.price - visit_infos.profit) 
                            ELSE 0 
                        END
                    ) as total_minus")
                )
                ->first();

            return [
                'id' => $market->id,
                'name' => $market->name,
                // 'address' => $market->address ?? 'Manzil ko\'rsatilmagan',
                'profit' => (int)($stats->total_profit ?? 0),
                'minus' => (int)($stats->total_minus ?? 0),
                'visit_count' => (int)($stats->visit_count ?? 0),
            ];
        });

        return response()->json($markets);
    }

    public function agentsStatistics(Request $request)
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // Получаем агентов с их визитами за 30 дней
        $agents = User::where('role', 'agent')->get()->map(function ($user) use ($thirtyDaysAgo) {
            $visitInfos = DB::table('visit_infos')
                ->join('visits', 'visit_infos.visit_id', '=', 'visits.id')
                ->where('visits.user_id', $user->id)
                ->where('visits.created_at', '>=', $thirtyDaysAgo)
                ->select('visit_infos.*', 'visits.created_at')
                ->get();

            $totalProfit = $visitInfos->sum('profit');
            
            // Расчет минуса
            $totalMinus = 0;
            foreach ($visitInfos as $info) {
                $product = DB::table('products')->where('id', $info->product_id)->first();
                $expected = ($info->loaded - $info->left) * ($product->price ?? 0);
                $minus = max(0, $expected - $info->profit);
                $totalMinus += $minus;
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'profit' => (int)$totalProfit,
                'minus' => (int)$totalMinus,
                'visit_count' => $visitInfos->unique('visit_id')->count(),
            ];
        });

        return response()->json($agents);
    }
}
