<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class CreateInventorySessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'inventory_date' => 'required|date|before_or_equal:today',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم عملية الجرد مطلوب',
            'inventory_date.required' => 'تاريخ الجرد مطلوب',
            'inventory_date.before_or_equal' => 'تاريخ الجرد يجب أن يكون اليوم أو في الماضي',
            'product_ids.*.exists' => 'بعض المنتجات غير موجودة',
        ];
    }
}
