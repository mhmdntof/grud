<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequestRequest extends FormRequest
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

        'supplier_id' => 'nullable|exists:suppliers,id',

        'request_type' => 'required|in:normal,urgent',

        'expected_budget' => 'required|numeric|min:0',

        'reason' => 'required|string|max:1000',

        'items' => 'required|array|min:1|max:5',


        'request_frequency'=>'nullable',

        'items.*.product_id' => [
            'required',
            'exists:products,id'
        ],

        'items.*.quantity' => [
            'required',
            'integer',
            'min:1'
        ],

        'items.*.unit' => [
            'required',
            'in:piece,liter,kilogram,dozen'
        ],
    ];
}
}
