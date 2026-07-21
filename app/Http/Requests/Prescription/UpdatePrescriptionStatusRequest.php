<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:processed,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'الحالة مطلوبة',
            'status.in' => 'الحالة يجب أن تكون: approved, processed, أو rejected',
            'rejection_reason.required_if' => 'سبب الرفض مطلوب عند رفض الوصفة',
        ];
    }
}
