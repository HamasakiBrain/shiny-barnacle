<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'method' => \App\Enums\PaymentMethod::Cod,
            'status' => \App\Enums\PaymentStatus::Pending,
            'amount' => 1000,
        ];
    }
}
