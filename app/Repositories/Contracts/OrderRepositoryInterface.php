<?php

namespace App\Repositories\Contracts;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function listing(User $user, bool $admin = false): LengthAwarePaginator;

    public function get(int $id, bool $lock = false): Order;

    public function byKey(int $userId, string $key): ?Order;

    public function create(array $data, array $items, array $payment): Order;

    public function status(Order $order, \App\Enums\OrderStatus $status): void;

    public function payment(Order $order, array $data): void;

    public function purchased(int $userId, int $productId): bool;
}
