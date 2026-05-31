<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class RejectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_id' => 'required|exists:requests,id',
            'rejection_reason' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'request_id.required' => 'الطلب مطلوب',
            'request_id.exists' => 'الطلب غير موجود',
            'rejection_reason.required' => 'سبب الرفض مطلوب',
            'rejection_reason.max' => 'سبب الرفض يجب أن لا يتجاوز 1000 حرف',
        ];
    }
}
