<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderNotification;

class QueueOrderNotification
{
    public function handle(OrderPlaced $event): void
    {
        SendOrderNotification::dispatch($event->orderId);
    }
}
