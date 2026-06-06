<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class DamageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_id' => 'required|exists:batches,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'batch_id.required' => 'الدفعة مطلوبة',
            'batch_id.exists' => 'الدفعة غير موجودة',
            'quantity.required' => 'الكمية المُتلفة مطلوبة',
            'quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'notes.required' => 'سبب الاتلاف مطلوب',
        ];
    }
}
