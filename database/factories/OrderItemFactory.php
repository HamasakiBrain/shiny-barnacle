<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'product_id' => \App\Models\Product::factory(),
            'name' => 'Demo Product',
            'sku' => fake()->unique()->uuid(),
            'unit_price' => 1000,
            'quantity' => 1,
        ];
    }
}
