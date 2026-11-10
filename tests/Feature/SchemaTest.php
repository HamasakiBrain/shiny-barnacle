<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_factories_and_relationships(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();
        $cart = CartItem::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
        Payment::factory()->create(['order_id' => $order->id]);
        Review::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        Favorite::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        $this->assertSame($product->id, $cart->product->id);
        $this->assertCount(1, $order->items);
        $this->assertNotNull($order->payment);
        $this->assertCount(1, $user->reviews);
        $this->assertCount(1, $user->favorites);
    }
}
