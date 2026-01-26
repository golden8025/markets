<?php

namespace Database\Factories;

use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarketStock>
 */
class MarketStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'market_id' => Market::factory(),
            'total_loaded_qty' => $this->faker->numberBetween(100, 1000),
            'total_loaded_amount' => $this->faker->randomFloat(2, 1000, 10000),
            'current_stock' => $this->faker->numberBetween(0, 100),
        ];
    }
}
