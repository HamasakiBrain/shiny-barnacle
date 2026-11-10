<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'payment_method' => ['required', Rule::enum(\App\Enums\PaymentMethod::class)],
            'shipping_address' => ['required', 'array:name,line1,city,postal_code,country'],
            'shipping_address.name' => ['required', 'string', 'max:100'],
            'shipping_address.line1' => ['required', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.postal_code' => ['required', 'string', 'max:20'],
            'shipping_address.country' => ['required', 'string', 'regex:/^[A-Z]{2}$/'],
        ];
    }
}
