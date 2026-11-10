<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PaymentService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OrderRepositoryInterface $orders,
    ) {
    }

    public function mock(User $user, int $id, string $outcome): Order
    {
        $visible = $this->orders->get($id);
        Gate::forUser($user)->authorize('pay', $visible);

        return DB::transaction(function () use ($user, $id, $outcome) {
            $this->users->lock($user->id);
            $order = $this->orders->get($id, true);

            if (
                $order->payment->method !== PaymentMethod::Mock
                || $order->status === OrderStatus::Cancelled
            ) {
                throw new BusinessException('This order cannot accept mock payment.');
            }

            if ($order->payment->status === PaymentStatus::Paid) {
                return $order;
            }

            if ($order->status !== OrderStatus::Pending) {
                throw new BusinessException('Only pending orders accept payment.');
            }

            $success = $outcome === 'success';
            $this->orders->payment(
                $order,
                [
                    'status' => $success ? PaymentStatus::Paid : PaymentStatus::Failed,
                    'paid_at' => $success ? now() : null,
                ],
            );

            return $this->orders->get($id);
        }, 3);
    }
}
