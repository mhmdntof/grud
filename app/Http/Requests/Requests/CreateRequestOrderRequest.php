<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequestOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

        'request_type' => 'required|in:normal,urgent',
            'items' => 'required|array|min:1|max:5',

            'items.*.product_id' => 'required|exists:products,id',

            'items.*.quantity' => 'required|integer|min:1',
        ];
    }
}