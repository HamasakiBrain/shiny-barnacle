<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => [$this->isMethod('post') ? 'required' : 'sometimes', 'integer', 'exists:categories,id'],
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'alpha_dash',
                'max:255',
                Rule::unique('products')->ignore($this->route('product')),
            ],
            'sku' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'max:255',
                Rule::unique('products')->ignore($this->route('product')),
            ],
            'description' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:10000'],
            'price' => [$this->isMethod('post') ? 'required' : 'sometimes', 'integer', 'between:1,100000000'],
            'stock' => [$this->isMethod('post') ? 'required' : 'sometimes', 'integer', 'between:0,1000000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
