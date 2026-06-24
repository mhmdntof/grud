<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class RecordActualQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:inventory_items,id',
            'items.*.actual_quantity' => 'required|integer|min:0',
            'items.*.adjustment_notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'يجب إدخال مواد الجرد',
            'items.*.id.exists' => 'بعض المواد غير موجودة',
            'items.*.actual_quantity.required' => 'الكمية الفعلية مطلوبة',
            'items.*.actual_quantity.min' => 'الكمية الفعلية يجب أن تكون 0 على الأقل',
        ];
    }
}
