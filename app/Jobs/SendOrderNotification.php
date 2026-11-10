<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOrderNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $orderId)
    {
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [5, 15, 60];
    }

    public function handle(NotificationService $notifications): void
    {
        $notifications->orderPlaced($this->orderId);
    }
}
