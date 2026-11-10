<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository implements OrderRepositoryInterface
{
    public function listing(User $user, bool $admin = false): LengthAwarePaginator
    {
        return Order::with(['items', 'payment'])
            ->when(!$admin, fn ($q) => $q->where('user_id', $user->id))
            ->latest('id')
            ->paginate(20);
    }

    public function get(int $id, bool $lock = false): Order
    {
        return Order::with(['items', 'payment'])
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->findOrFail($id);
    }

    public function byKey(int $userId, string $key): ?Order
    {
        return Order::with(['items', 'payment'])
            ->where('user_id', $userId)
            ->where('idempotency_key', $key)
            ->first();
    }

    public function create(array $data, array $items, array $payment): Order
    {
        $order = Order::create($data);
        $order->items()->createMany($items);
        $order->payment()->create($payment);

        return $order->load(['items', 'payment']);
    }

    public function status(Order $order, \App\Enums\OrderStatus $status): void
    {
        $order->update(['status' => $status]);
    }

    public function payment(Order $order, array $data): void
    {
        $order->payment->update($data);
    }

    public function purchased(int $userId, int $productId): bool
    {
        return Order::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereHas(
                'items',
                fn ($q) => $q->where('product_id', $productId),
            )
            ->exists();
    }
}
