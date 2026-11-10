<?php

namespace Tests\Unit;

use App\DTOs\CheckoutData;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessException;
use App\Services\OrderWorkflow;
use PHPUnit\Framework\TestCase;

class OrderWorkflowTest extends TestCase
{
    public function test_shipping_requires_processing(): void
    {
        $this->expectException(BusinessException::class);
        (new OrderWorkflow())->assertTransition(OrderStatus::Pending, OrderStatus::Shipped);
    }

    public function test_delivered_order_cannot_cancel(): void
    {
        $this->expectException(BusinessException::class);
        (new OrderWorkflow())->assertTransition(OrderStatus::Delivered, OrderStatus::Cancelled);
    }

    public function test_valid_transition(): void
    {
        (new OrderWorkflow())->assertTransition(OrderStatus::Processing, OrderStatus::Shipped);
        $this->addToAssertionCount(1);
    }

    public function test_fingerprint_is_independent_of_address_key_order(): void
    {
        $a = [
            'idempotency_key' => 'a',
            'payment_method' => 'cod',
            'shipping_address' => [
                'city' => 'A',
                'name' => 'B',
            ],
        ];
        $b = $a;
        $b['shipping_address'] = array_reverse($a['shipping_address'], true);
        $this->assertSame(
            CheckoutData::fromArray($a)->fingerprint(),
            CheckoutData::fromArray($b)->fingerprint(),
        );
    }
}
