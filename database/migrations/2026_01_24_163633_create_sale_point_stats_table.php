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
            CREATE VIEW view_sales_point_stats AS
            SELECT
                sp.id AS sales_point_id,
                sp.name AS sales_point_name,

                COALESCE(SUM(ps.total_loaded_qty), 0) AS total_loaded_qty,
                COALESCE(SUM(v.sold_qty), 0) AS total_sold_qty,
                COALESCE(SUM(v.minus_qty), 0) AS total_minus_qty,
                COALESCE(SUM(v.total_amount), 0) AS total_income

            FROM sales_points sp
            LEFT JOIN point_stock_summary ps
                ON ps.sales_point_id = sp.id
            LEFT JOIN visits v
                ON v.sales_point_id = sp.id
            GROUP BY sp.id, sp.name
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_point_stats');
    }
};
