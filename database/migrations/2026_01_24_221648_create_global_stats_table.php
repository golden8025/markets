<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE VIEW view_global_statistics AS
            SELECT
                COUNT(DISTINCT sp.id) AS total_markets,

                COALESCE(SUM(ps.total_loaded_qty), 0) AS total_loaded_products,
                COALESCE(SUM(ps.total_loaded_amount), 0) AS total_loaded_amount,

                COALESCE(SUM(v.sold_qty), 0) AS total_sold_products,
                COALESCE(SUM(v.minus_qty), 0) AS total_minus_products,
                COALESCE(SUM(v.total_amount), 0) AS total_income

            FROM markets sp
            LEFT JOIN point_stock_summary ps
                ON ps.market_id = sp.id
            LEFT JOIN visits v
                ON v.market_id = sp.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_global_statistics");
    }
};
