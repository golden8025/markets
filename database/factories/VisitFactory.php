<?php

namespace Database\Factories;

use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visit>
 */
class VisitFactory extends Factory
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
            'visit_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'previous_stock' => $this->faker->numberBetween(0, 100),
            'sold_qty' => $this->faker->numberBetween(0, 50),
            'minus_qty' => $this->faker->numberBetween(0, 10),
            'total_amount' => $this->faker->randomFloat(2, 100, 5000),
            'comment' => $this->faker->sentence,
        ];
    }
}
