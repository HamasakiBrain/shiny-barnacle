<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $method = 'cod'): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(),
            'payment_method' => $method,
            'shipping_address' => [
                'name' => 'Test',
                'line1' => '1 Main St',
                'city' => 'Boston',
                'postal_code' => '02101',
                'country' => 'US',
            ],
        ];
    }

    public function test_checkout_snapshots_prices_and_is_idempotent(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 1234,
            'stock' => 5,
        ]);
        $this->actingAs($user)
            ->putJson(
                '/api/v1/cart/items',
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            )
            ->assertOk();
        $payload = $this->payload();
        $payload['total'] = 1;
        $first = $this->postJson('/api/v1/orders', $payload)
            ->assertCreated()
            ->assertJsonPath('data.total', 2468);
        $this->postJson('/api/v1/orders', $payload)
            ->assertCreated()
            ->assertJsonPath(
                'data.id',
                $first->json('data.id'),
            );
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('cart_items', 0);
        $payload['payment_method'] = 'mock';
        $this->postJson('/api/v1/orders', $payload)->assertConflict();
    }

    public function test_checkout_failure_rolls_back_all_stock_and_keeps_cart(): void
    {
        $user = User::factory()->create();
        $a = Product::factory()->create(['stock' => 3]);
        $b = Product::factory()->create(['stock' => 2]);
        $this->actingAs($user);

        foreach ([$a, $b] as $p) {
            $this->putJson('/api/v1/cart/items', [
                'product_id' => $p->id,
                'quantity' => 2,
            ])->assertOk();
        }

        $b->update(['stock' => 1]);
        $this->postJson('/api/v1/orders', $this->payload())->assertConflict();
        $this->assertSame(3, $a->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 2);
    }

    public function test_cancel_restores_stock_once_and_ownership_is_enforced(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $p = Product::factory()->create(['stock' => 5]);
        $this->actingAs($owner)->putJson('/api/v1/cart/items', [
            'product_id' => $p->id,
            'quantity' => 2,
        ]);
        $id = $this->postJson('/api/v1/orders', $this->payload())->json('data.id');
        $this->actingAs($other)
            ->getJson("/api/v1/orders/$id")
            ->assertForbidden();
        $this->postJson("/api/v1/orders/$id/cancel")->assertForbidden();
        $this->actingAs($owner)
            ->postJson("/api/v1/orders/$id/cancel")
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'cancelled',
            );
        $this->postJson("/api/v1/orders/$id/cancel")->assertOk();
        $this->assertSame(5, $p->fresh()->stock);
    }

    public function test_mock_failure_retry_and_cod_delivery(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->role = Role::Admin;
        $admin->save();

        foreach (['mock', 'cod'] as $method) {
            $p = Product::factory()->create();
            $this->actingAs($user)->putJson('/api/v1/cart/items', [
                'product_id' => $p->id,
                'quantity' => 1,
            ]);
            $id = $this->postJson('/api/v1/orders', $this->payload($method))
                ->assertCreated()
                ->json('data.id');

            if ($method === 'mock') {
                $this->postJson("/api/v1/orders/$id/payments/mock", ['outcome' => 'failure'])
                    ->assertOk()
                    ->assertJsonPath(
                        'data.payment.status',
                        'failed',
                    );
                $this->actingAs($admin)
                    ->patchJson("/api/v1/admin/orders/$id/status", ['status' => 'processing'])
                    ->assertConflict();
                $this->actingAs($user)
                    ->postJson("/api/v1/orders/$id/payments/mock", ['outcome' => 'success'])
                    ->assertOk()
                    ->assertJsonPath(
                        'data.payment.status',
                        'paid',
                    );
            } else {
                $this->postJson("/api/v1/orders/$id/payments/mock", ['outcome' => 'success'])->assertConflict();
            }

            $this->actingAs($admin);

            foreach (['processing', 'shipped', 'delivered'] as $status) {
                $this->patchJson("/api/v1/admin/orders/$id/status", ['status' => $status])->assertOk();
            }

            $this->getJson("/api/v1/orders/$id")->assertJsonPath('data.payment.status', 'paid');
            $this->postJson("/api/v1/orders/$id/cancel")->assertConflict();
        }
    }

    public function test_empty_cart_and_invalid_quantity(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/v1/orders', $this->payload())
            ->assertConflict();
        $p = Product::factory()->create();
        $this->putJson('/api/v1/cart/items', [
            'product_id' => $p->id,
            'quantity' => 0,
        ])
            ->assertUnprocessable();
    }

    public function test_mock_payment_ownership_and_paid_cancellation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);
        $this->actingAs($owner)
            ->putJson(
                '/api/v1/cart/items',
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            )
            ->assertOk();
        $id = $this->postJson('/api/v1/orders', $this->payload('mock'))
            ->assertCreated()
            ->json('data.id');
        $this->actingAs($other)
            ->postJson("/api/v1/orders/$id/payments/mock", ['outcome' => 'success'])
            ->assertForbidden();
        $this->actingAs($owner)
            ->postJson("/api/v1/orders/$id/payments/mock", ['outcome' => 'success'])
            ->assertOk();
        $this->postJson("/api/v1/orders/$id/cancel")
            ->assertOk()
            ->assertJsonPath(
                'data.payment.status',
                'refunded',
            );
        $this->postJson("/api/v1/orders/$id/payments/mock", ['outcome' => 'success'])->assertConflict();
        $this->assertSame(5, $product->fresh()->stock);
    }
}
