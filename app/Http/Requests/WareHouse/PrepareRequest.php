<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class PrepareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_id' => 'required|exists:requests,id',
        ];
    }

    public function messages(): array
    {
        return [
            'request_id.required' => 'الطلب مطلوب',
            'request_id.exists' => 'الطلب غير موجود',
        ];
    }
}
