<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    public function __construct(private int $orderId, private int $total)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->orderId,
            'total' => $this->total,
            'currency' => 'USD',
            'message' => 'Your order has been placed.',
        ];
    }
}
