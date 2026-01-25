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
            CREATE VIEW view_market_stats AS
            SELECT
                sp.id AS market_id,
                sp.name AS market_name,

                COALESCE(SUM(ps.total_loaded_qty), 0) AS total_loaded_qty,
                COALESCE(SUM(v.sold_qty), 0) AS total_sold_qty,
                COALESCE(SUM(v.minus_qty), 0) AS total_minus_qty,
                COALESCE(SUM(v.total_amount), 0) AS total_income

            FROM markets sp
            LEFT JOIN point_stock_summary ps
                ON ps.market_id = sp.id
            LEFT JOIN visits v
                ON v.market_id = sp.id
            GROUP BY sp.id, sp.name
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_market_stats");
    }
};
