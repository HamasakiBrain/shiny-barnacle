<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_updates_and_deactivates_catalog(): void
    {
        $admin = User::factory()->create();
        $admin->role = Role::Admin;
        $admin->save();
        $this->actingAs($admin);
        $c = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Office',
            'slug' => 'office',
        ])
            ->assertCreated()
            ->json('data.id');
        $p = $this->postJson(
            '/api/v1/admin/products',
            [
                'category_id' => $c,
                'name' => 'Desk',
                'slug' => 'desk',
                'sku' => 'DESK',
                'description' => 'Desk',
                'price' => 1234,
                'stock' => 5,
            ],
        )
            ->assertCreated()
            ->json('data.id');
        $this->patchJson("/api/v1/admin/products/$p", ['price' => 2000])
            ->assertOk()
            ->assertJsonPath(
                'data.price',
                2000,
            );
        $this->patchJson("/api/v1/admin/categories/$c", ['name' => 'Home Office'])->assertOk();
        $this->getJson("/api/v1/products/$p")->assertOk();
        $this->deleteJson("/api/v1/admin/categories/$c")->assertNoContent();
        $this->getJson('/api/v1/products')->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/products/$p")->assertNotFound();
        $this->deleteJson("/api/v1/admin/products/$p")->assertNoContent();
        $this->assertDatabaseHas('products', [
            'id' => $p,
            'is_active' => false,
        ]);
    }

    public function test_filters_pagination_and_unsafe_sort(): void
    {
        Product::factory()->create([
            'price' => 2000,
            'name' => 'Expensive',
        ]);
        Product::factory()->create([
            'price' => 1000,
            'name' => 'Affordable',
        ]);
        Product::factory()->create(['is_active' => false]);
        $this->getJson('/api/v1/products?sort=price&direction=desc&per_page=1')
            ->assertOk()
            ->assertJsonPath(
                'data.0.price',
                2000,
            )
            ->assertJsonPath(
                'meta.total',
                2,
            );
        $this->getJson('/api/v1/products?min_price=1500')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/products?search=Affordable')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/products?sort=password')->assertUnprocessable();
        $this->getJson('/api/v1/products?per_page=10000')->assertUnprocessable();
    }

    public function test_customer_cannot_modify_stock_or_read_another_cart(): void
    {
        $u = User::factory()->create();
        $other = User::factory()->create();
        $p = Product::factory()->create();
        $this->actingAs($u)
            ->putJson(
                '/api/v1/cart/items',
                [
                    'product_id' => $p->id,
                    'quantity' => 1,
                    'user_id' => $other->id,
                ],
            )
            ->assertOk();
        $this->assertDatabaseHas('cart_items', ['user_id' => $u->id]);
        $this->actingAs($other)
            ->getJson('/api/v1/cart')
            ->assertJsonCount(0, 'data');
        $this->patchJson("/api/v1/admin/products/$p->id", ['stock' => 999])->assertForbidden();
    }

    public function test_invalid_product_values_are_rejected(): void
    {
        $u = User::factory()->create();
        $u->role = Role::Admin;
        $u->save();
        $this->actingAs($u)
            ->postJson('/api/v1/admin/products', [
                'price' => -1,
                'stock' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price', 'stock', 'name']);
    }
}
