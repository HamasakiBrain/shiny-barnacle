<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\CatalogRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CatalogRepositoryInterface $catalog,
        private OrderRepositoryInterface $orders,
        private OrderWorkflow $workflow,
    ) {
    }

    public function listing(User $user, bool $admin = false): LengthAwarePaginator
    {
        if ($admin) {
            Gate::forUser($user)->authorize('admin');
        }

        return $this->orders->listing($user, $admin);
    }

    public function get(User $user, int $id): Order
    {
        $order = $this->orders->get($id);
        Gate::forUser($user)->authorize('view', $order);

        return $order;
    }

    public function cancel(User $user, int $id): Order
    {
        return $this->transition($user, $id, OrderStatus::Cancelled);
    }

    public function transition(User $user, int $id, OrderStatus $status): Order
    {
        $visible = $this->get($user, $id);

        if ($status !== OrderStatus::Cancelled) {
            Gate::forUser($user)->authorize('admin');
        }

        return DB::transaction(function () use ($user, $visible, $status) {
            $this->users->lock($visible->user_id);
            $order = $this->orders->get($visible->id, true);

            if ($order->status === $status) {
                return $order;
            }

            if ($user->role !== Role::Admin && $order->status !== OrderStatus::Pending) {
                throw new BusinessException('Only pending orders can be cancelled by customers.');
            }

            $this->workflow->assertTransition($order->status, $status);

            if ($status === OrderStatus::Cancelled) {
                $products = $this->catalog->lockProducts($order->items->pluck('product_id')->all());

                foreach ($order->items as $line) {
                    $this->catalog->stock($products->get($line->product_id), $line->quantity);
                }

                $this->orders->payment(
                    $order,
                    [
                        'status' => $order->payment->status === PaymentStatus::Paid
                            ? PaymentStatus::Refunded
                            : PaymentStatus::Cancelled,
                    ],
                );
            } else {
                if (
                    $order->payment->method === PaymentMethod::Mock
                    && $order->payment->status !== PaymentStatus::Paid
                ) {
                    throw new BusinessException('Mock payment must succeed before fulfillment.');
                }

                if ($status === OrderStatus::Delivered && $order->payment->method === PaymentMethod::Cod) {
                    $this->orders->payment($order, [
                        'status' => PaymentStatus::Paid,
                        'paid_at' => now(),
                    ]);
                }
            }

            $this->orders->status($order, $status);

            return $this->orders->get($order->id);
        }, 3);
    }
}
