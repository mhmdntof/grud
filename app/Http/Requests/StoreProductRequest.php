<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'name' => 'required|string|max:255',

            'code' => 'required|string|unique:products,code',

            'type' => 'required|in:fixed,consumable',

            'minimum_stock' => 'nullable|integer|min:0',

            'unit' => 'nullable|string|max:100',

            'description' => 'nullable|string',
        ];
    }
}