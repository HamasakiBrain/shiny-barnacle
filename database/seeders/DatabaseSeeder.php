<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Demo seeding is restricted to local/testing.');
        }

        $password = config('store.demo_password');

        if (!is_string($password) || strlen($password) < 12) {
            throw new \RuntimeException('Set DEMO_PASSWORD in .env (at least 12 characters).');
        }

        foreach ([
            'admin' => config('store.demo_admin_email'),
            'customer' => config('store.demo_customer_email'),
        ] as $role => $email) {
            $user = User::firstOrNew(['email' => $email]);
            $user->fill([
                'name' => ucfirst($role) . ' Demo',
                'password' => Hash::make($password),
            ]);
            $user->role = Role::from($role);
            $user->save();
        }

        foreach (['Accessories', 'Electronics', 'Office'] as $i => $name) {
            $category = Category::updateOrCreate(['slug' => strtolower($name)], [
                'name' => $name,
                'is_active' => true,
            ]);

            for ($j = 1;$j <= 5;$j++) {
                Product::firstOrCreate(
                    ['sku' => "DEMO-$i-$j"],
                    [
                        'category_id' => $category->id,
                        'name' => "$name item $j",
                        'slug' => strtolower("$name-item-$j"),
                        'description' => "Demo $name product.",
                        'price' => 1000 + $j * 500,
                        'stock' => 50,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
