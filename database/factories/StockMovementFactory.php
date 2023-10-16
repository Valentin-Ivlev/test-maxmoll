<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => $this->faker->numberBetween(1, 30),
            'warehouse_id' => $this->faker->numberBetween(1, 3),
            'count' => $this->faker->numberBetween(0, 100),
            'movement_type' => $this->faker->randomElement(['incoming', 'outgoing']),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now')
        ];
    }
}
