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
            'request_type' => 'required|in:normal,urgent,recurring',
            'items' => 'required|array|min:1|max:5',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'recurring_frequency' => 'nullable|required_if:request_type,recurring|in:daily,weekly,monthly',
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'نوع الطلب مطلوب',
            'request_type.in' => 'نوع الطلب يجب أن يكون: عادي، مستعجل، أو متكرر',
            'items.required' => 'المواد مطلوبة',
            'items.min' => 'يجب إضافة مادة واحدة على الأقل',
            'items.max' => 'لا يمكن إضافة أكثر من 5 مواد',
            'items.*.product_id.exists' => 'المنتج غير موجود',
            'items.*.quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'recurring_frequency.required_if' => 'تردد التكرار مطلوب للطلبات المتكررة',
        ];
    }
}
