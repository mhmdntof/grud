<?php

namespace App\Http\Requests\DepartmentHeader;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:request_order_items,id',
            'items.*.received_quantity' => 'required|integer|min:0|max:10000',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'يجب تحديد المواد المستلمة',
            'items.min' => 'يجب تحديد مادة واحدة على الأقل',
            'items.*.id.exists' => 'المادة غير موجودة',
            'items.*.received_quantity.required' => 'الكمية المستلمة مطلوبة',
            'items.*.received_quantity.min' => 'الكمية يجب أن تكون 0 على الأقل',
            'items.*.received_quantity.max' => 'الكمية لا يمكن أن تتجاوز 10000',
        ];
    }
}
