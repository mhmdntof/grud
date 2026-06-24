<?php

namespace App\Http\Requests\DepartmentHeader;


use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => 'sometimes|in:normal,urgent,recurring',
            'items' => 'sometimes|array|min:1|max:10',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1|max:1000',
            'notes' => 'nullable|string|max:1000',
            'recurring_frequency' => 'nullable|required_if:request_type,recurring|in:daily,weekly,monthly',
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.in' => 'نوع الطلب يجب أن يكون: عادي، مستعجل، أو متكرر',
            'items.min' => 'يجب إضافة مادة واحدة على الأقل',
            'items.max' => 'لا يمكن إضافة أكثر من 10 مواد',
            'items.*.product_id.exists' => 'المنتج غير موجود',
            'items.*.quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'items.*.quantity.max' => 'الكمية لا يمكن أن تتجاوز 1000',
            'recurring_frequency.required_if' => 'تردد التكرار مطلوب للطلبات المتكررة',
        ];
    }
}
