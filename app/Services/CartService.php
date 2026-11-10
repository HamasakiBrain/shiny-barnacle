<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CartRepositoryInterface $cart,
        private CatalogRepositoryInterface $catalog,
    ) {
    }

    public function items(User $user): Collection
    {
        return $this->cart->items($user->id);
    }

    public function put(User $user, int $productId, int $quantity): Collection
    {
        return DB::transaction(function () use ($user, $productId, $quantity) {
            $this->users->lock($user->id);
            $product = $this->catalog->lockProducts([$productId])->get($productId);
            abort_unless($product && $product->is_active && $product->category->is_active, 404);

            if ($quantity > $product->stock) {
                throw new BusinessException('Insufficient stock.');
            }

            $this->cart->put($user->id, $productId, $quantity);

            return $this->items($user);
        }, 3);
    }

    public function remove(User $user, int $productId): void
    {
        DB::transaction(function () use ($user, $productId) {
            $this->users->lock($user->id);
            $this->cart->remove($user->id, $productId);
        });
    }

    public function clear(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->users->lock($user->id);
            $this->cart->clear($user->id);
        });
    }
}
