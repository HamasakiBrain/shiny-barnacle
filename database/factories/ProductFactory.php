<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => \App\Models\Category::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'sku' => fake()->unique()->bothify('SKU-########'),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(100, 20000),
            'stock' => 20,
            'is_active' => true,
        ];
    }
}
