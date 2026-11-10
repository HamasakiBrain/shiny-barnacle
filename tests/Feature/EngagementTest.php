<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviews_require_delivered_purchase_and_hide_email(): void
    {
        $user = User::factory()->create();
        $p = Product::factory()->create();
        $this->actingAs($user)
            ->putJson("/api/v1/products/$p->id/review", [
                'rating' => 5,
                'body' => 'Good',
            ])
            ->assertConflict();
        $o = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'delivered',
        ]);
        OrderItem::factory()->create([
            'order_id' => $o->id,
            'product_id' => $p->id,
        ]);
        $r = $this->putJson("/api/v1/products/$p->id/review", [
            'rating' => 5,
            'body' => 'Good',
        ])->assertOk();
        $this->putJson("/api/v1/products/$p->id/review", [
            'rating' => 4,
            'body' => 'Updated',
        ])->assertOk();
        $this->assertDatabaseCount('reviews', 1);
        $this->getJson("/api/v1/products/$p->id/reviews")
            ->assertOk()
            ->assertJsonMissing(['email' => $user->email]);
        $this->actingAs(User::factory()->create())
            ->deleteJson('/api/v1/reviews/' . $r->json('data.id'))
            ->assertForbidden();
    }

    public function test_favorites_are_idempotent_and_private(): void
    {
        $user = User::factory()->create();
        $p = Product::factory()->create();
        $this->actingAs($user);

        for ($i = 0;$i < 2;$i++) {
            $this->putJson("/api/v1/favorites/$p->id")->assertNoContent();
        }

        $this->assertDatabaseCount('favorites', 1);
        $this->actingAs(User::factory()->create())
            ->getJson('/api/v1/favorites')
            ->assertJsonCount(0, 'data');
        $this->deleteJson("/api/v1/favorites/$p->id")->assertNoContent();
        $this->assertDatabaseCount('favorites', 1);
    }
}
