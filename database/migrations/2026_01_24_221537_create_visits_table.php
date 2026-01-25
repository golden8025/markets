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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->date('visit_date');
            $table->integer('previous_stock');
            $table->integer('sold_qty');
            $table->integer('minus_qty');
            $table->integer('total_amount');
            $table->text('comment')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
