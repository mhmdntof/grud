<?php

namespace App\Http\Requests\DepartmentHeader;

use Illuminate\Foundation\Http\FormRequest;

class ReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1|max:10',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'يجب تحديد المواد المراد إعادتها',
            'items.min' => 'يجب تحديد مادة واحدة على الأقل',
            'items.max' => 'لا يمكن إعادة أكثر من 10 مواد',
            'items.*.product_id.exists' => 'المنتج غير موجود',
            'items.*.quantity.required' => 'الكمية مطلوبة',
            'items.*.quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'reason.required' => 'سبب الإعادة مطلوب',
            'reason.max' => 'السبب لا يمكن أن يتجاوز 1000 حرف',
        ];
    }
}
