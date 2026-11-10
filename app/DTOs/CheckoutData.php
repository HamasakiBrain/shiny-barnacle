<?php

namespace App\DTOs;

use App\Enums\PaymentMethod;

final readonly class CheckoutData
{
    public function __construct(
        public string $idempotencyKey,
        public PaymentMethod $method,
        public array $address,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $address = $data['shipping_address'];
        ksort($address);

        return new self($data['idempotency_key'], PaymentMethod::from($data['payment_method']), $address);
    }

    public function fingerprint(): string
    {
        return hash('sha256', json_encode([$this->method->value, $this->address], JSON_THROW_ON_ERROR));
    }
}
