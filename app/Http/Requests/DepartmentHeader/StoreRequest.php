<?php

namespace App\Http\Requests\DepartmentHeader;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'requested_quantity' => 'required|integer|min:1',
            'type' => 'required|in:normal,recurring,urgent',
            'needed_by' => 'nullable|date|after_or_equal:today',
            'recurring_frequency' => 'nullable|required_if:type,recurring|in:daily,weekly,monthly',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'المنتج مطلوب',
            'product_id.exists' => 'المنتج غير موجود',
            'requested_quantity.required' => 'الكمية المطلوبة مطلوبة',
            'requested_quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'type.required' => 'نوع الطلب مطلوب',
            'type.in' => 'نوع الطلب يجب أن يكون: عادي، متكرر، أو مستعجل',
            'needed_by.after_or_equal' => 'تاريخ الاستلام يجب أن يكون اليوم أو بعده',
            'recurring_frequency.required_if' => 'تردد التكرار مطلوب للطلبات المتكررة',
        ];
    }
}
