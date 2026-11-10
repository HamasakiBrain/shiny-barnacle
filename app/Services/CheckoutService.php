<?php

namespace App\Services;

use App\DTOs\CheckoutData;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CartRepositoryInterface $cart,
        private CatalogRepositoryInterface $catalog,
        private OrderRepositoryInterface $orders,
    ) {
    }

    public function checkout(User $user, CheckoutData $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $this->users->lock($user->id);

            if ($existing = $this->orders->byKey($user->id, $data->idempotencyKey)) {
                if ($existing->request_hash !== $data->fingerprint()) {
                    throw new BusinessException('Idempotency key was used with a different request.');
                }

                return $existing;
            }

            $cart = $this->cart->items($user->id);

            if ($cart->isEmpty()) {
                throw new BusinessException('Cart is empty.');
            }

            $products = $this->catalog->lockProducts($cart->pluck('product_id')->all());
            $items = [];
            $total = 0;

            foreach ($cart as $line) {
                $product = $products->get($line->product_id);

                if (
                    !$product
                    || !$product->is_active
                    || !$product->category->is_active
                    || $product->stock < $line->quantity
                ) {
                    throw new BusinessException('A product is unavailable or has insufficient stock.');
                }

                $total += $product->price * $line->quantity;
                $items[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $product->price,
                    'quantity' => $line->quantity,
                ];
                $this->catalog->stock($product, -$line->quantity);
            }

            $order = $this->orders->create(
                [
                    'user_id' => $user->id,
                    'idempotency_key' => $data->idempotencyKey,
                    'request_hash' => $data->fingerprint(),
                    'status' => OrderStatus::Pending,
                    'total' => $total,
                    'currency' => 'USD',
                    'shipping_address' => $data->address,
                ],
                $items,
                [
                    'method' => $data->method,
                    'status' => PaymentStatus::Pending,
                    'amount' => $total,
                ],
            );
            $this->cart->clear($user->id);
            OrderPlaced::dispatch($order->id);

            return $order;
        }, 3);
    }
}
