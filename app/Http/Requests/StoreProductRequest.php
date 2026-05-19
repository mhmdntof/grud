<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         return [
        'name' => 'required|string|max:255',

        'code' => 'required|string|max:255|unique:products,code',

        'type' => 'required|in:fixed,consumable',

        'minimum_stock' => 'nullable|integer|min:0',

        'unit' => 'nullable|string|max:100',

        'description' => 'nullable|string',
    ];
    }
}
