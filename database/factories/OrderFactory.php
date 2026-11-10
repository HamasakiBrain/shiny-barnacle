<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'idempotency_key' => fake()->uuid(),
            'request_hash' => hash('sha256', 'test'),
            'status' => \App\Enums\OrderStatus::Pending,
            'total' => 1000,
            'currency' => 'USD',
            'shipping_address' => [
                'name' => 'Customer',
                'line1' => '1 Main St',
                'city' => 'Boston',
                'postal_code' => '02101',
                'country' => 'US',
            ],
        ];
    }
}
