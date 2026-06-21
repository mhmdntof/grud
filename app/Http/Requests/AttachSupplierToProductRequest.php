<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AttachSupplierToProductRequest extends FormRequest
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
        'product_id' => 'required|exists:products,id',
        'supplier_id' => 'required|exists:suppliers,id',
        'notes' => 'nullable|string',
        'is_primary' => 'nullable|boolean',
    ];
}
}
