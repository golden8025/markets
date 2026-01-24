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
        Schema::create('sales_points', function (Blueprint $table) {
            $table->id();

            $table->foreignId('groups_id')->constrained();
            $table->string('name');
            $table->string('key')->nullable();
            $table->enum('type', ['metan', 'propan', 'dokon']);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_points');
    }
};
