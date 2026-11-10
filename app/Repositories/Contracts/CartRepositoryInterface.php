<?php

namespace App\Repositories\Contracts;

use App\Models\CartItem;
use Illuminate\Database\Eloquent\Collection;

interface CartRepositoryInterface
{
    public function items(int $userId): Collection;

    public function put(int $userId, int $productId, int $quantity): CartItem;

    public function remove(int $userId, int $productId): void;

    public function clear(int $userId): void;
}
