<?php

namespace App\Repositories\Eloquent;

use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CartRepository implements CartRepositoryInterface
{
    public function items(int $userId): Collection
    {
        return CartItem::with('product.category')
            ->where('user_id', $userId)
            ->orderBy('product_id')
            ->get();
    }

    public function put(int $userId, int $productId, int $quantity): CartItem
    {
        return CartItem::updateOrCreate(
            [
                'user_id' => $userId,
                'product_id' => $productId,
            ],
            ['quantity' => $quantity],
        )
            ->load('product.category');
    }

    public function remove(int $userId, int $productId): void
    {
        CartItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();
    }

    public function clear(int $userId): void
    {
        CartItem::where('user_id', $userId)->delete();
    }
}
