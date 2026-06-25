<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseRequest extends FormRequest
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
        'purchase_request_id' => 'required|exists:purchase_requests,id',

        'supplier_id' => 'nullable|exists:suppliers,id',

        'items' => 'required|array|min:1',

        'items.*.product_id' => 'required|exists:products,id',

        'items.*.batch_number' => 'required|string',

        'items.*.quantity' => 'required|integer|min:1',

        'items.*.expire_date' => 'required|date',

        'items.*.purchase_price' => 'nullable|numeric',

        'items.*.notes' => 'nullable|string',
    ];
}
}
