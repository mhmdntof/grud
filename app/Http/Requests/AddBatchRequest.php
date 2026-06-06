<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',

            'batch_number' => 'required|string',

            'quantity' => 'required|integer|min:1',

            'expire_date' => 'required|date',

            'purchase_price' => 'nullable|numeric|min:0',
        ];
    }
}