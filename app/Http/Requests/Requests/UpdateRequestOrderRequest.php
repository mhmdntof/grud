<?php

namespace App\Http\Requests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => 'sometimes|in:normal,urgent',
            'items' => 'sometimes|array|min:1',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.in' => 'نوع الطلب يجب أن يكون normal أو urgent',
            'items.array' => 'يجب أن تكون المواد مصفوفة',
            'items.min' => 'يجب إضافة مادة واحدة على الأقل',
            'items.*.product_id.exists' => 'المنتج غير موجود',
            'items.*.quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
        ];
    }
}
