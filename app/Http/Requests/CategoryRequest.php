<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'alpha_dash',
                'max:255',
                Rule::unique('categories')->ignore($this->route('category')),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
