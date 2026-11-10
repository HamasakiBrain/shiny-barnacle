<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\BusinessException;

class OrderWorkflow
{
    public function assertTransition(OrderStatus $from, OrderStatus $to): void
    {
        $allowed = match ($from) {
            OrderStatus::Pending => [OrderStatus::Processing, OrderStatus::Cancelled],
            OrderStatus::Processing => [OrderStatus::Shipped, OrderStatus::Cancelled],
            OrderStatus::Shipped => [OrderStatus::Delivered],
            default => [],
        };

        if (!in_array($to, $allowed, true)) {
            throw new BusinessException("Cannot transition from {$from->value} to {$to->value}.");
        }
    }
}
