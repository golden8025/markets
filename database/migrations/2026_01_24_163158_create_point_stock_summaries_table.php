<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('point_stock_summaries', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('sales_points_id')->constrained();
            $table->int('total_loaded_qty');
            $table->bigInteger('total_loaded_amount');
            $table->int('current_stock');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_stock_summaries');
    }
};
