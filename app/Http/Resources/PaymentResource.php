<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'method' => $this->method,
            'status' => $this->status,
            'amount' => $this->amount,
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
