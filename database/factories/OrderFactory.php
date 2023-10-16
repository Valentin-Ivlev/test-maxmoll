<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{

    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition()
    {

        $created_at = $this->faker->dateTimeBetween('-1 month', 'now');
        $completed_at = $this->faker->boolean(30) ? $this->faker->dateTimeBetween($created_at, 'now') : null;

        return [
            'customer' => $this->faker->name,
            'created_at' => $created_at,
            'completed_at' => $completed_at,
            'warehouse_id' => $this->faker->numberBetween(1, 3),
            'status' => $completed_at ? 'completed' : $this->faker->randomElement(['active', 'canceled']),
        ];

    }

}
