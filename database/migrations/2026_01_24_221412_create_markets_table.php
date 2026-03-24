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
        Schema::create('markets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->unique();
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
        Schema::dropIfExists('markets');
    }
};
