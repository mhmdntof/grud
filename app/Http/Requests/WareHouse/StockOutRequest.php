<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class StockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'department_id' => 'required|exists:departments,id',
            'quantity' => 'required|integer|min:1',
            'request_id' => 'nullable|exists:requests,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'المنتج مطلوب',
            'product_id.exists' => 'المنتج غير موجود',
            'department_id.required' => 'القسم مطلوب',
            'quantity.required' => 'الكمية مطلوبة',
            'quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'request_id.exists' => 'الطلب غير موجود',
        ];
    }
}
