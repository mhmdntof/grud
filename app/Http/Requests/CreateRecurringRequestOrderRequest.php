<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRecurringRequestOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => 'required|in:normal,urgent',
            'recurring_frequency' => 'required|in:daily,weekly,monthly',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'نوع الطلب مطلوب',
            'request_type.in' => 'نوع الطلب يجب أن يكون normal أو urgent',
            'recurring_frequency.required' => 'فترة التكرار مطلوبة',
            'recurring_frequency.in' => 'فترة التكرار يجب أن تكون daily أو weekly أو monthly',
            'items.required' => 'يجب إضافة مادة واحدة على الأقل',
            'items.*.product_id.required' => 'المنتج مطلوب',
            'items.*.product_id.exists' => 'المنتج غير موجود',
            'items.*.quantity.required' => 'الكمية مطلوبة',
            'items.*.quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
        ];
    }
}
