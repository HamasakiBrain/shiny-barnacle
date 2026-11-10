<?php

namespace App\Services;

use App\Notifications\OrderPlacedNotification;
use App\Repositories\Contracts\EngagementRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class NotificationService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OrderRepositoryInterface $orders,
        private EngagementRepositoryInterface $engagement,
    ) {
    }

    public function orderPlaced(int $orderId): void
    {
        $order = $this->orders->get($orderId);
        DB::transaction(function () use ($order) {
            $user = $this->users->lock($order->user_id);
            $id = Uuid::uuid5(Uuid::NAMESPACE_URL, 'market-api/order-placed/' . $order->id)->toString();

            if ($this->engagement->hasNotification($user, $id)) {
                return;
            }

            $notification = new OrderPlacedNotification($order->id, $order->total);
            $notification->id = $id;
            $this->engagement->notify($user, $notification);
        }, 3);
    }
}
