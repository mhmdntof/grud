<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StockInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'batch_number' => 'required|string|unique:batches,batch_number',
            'quantity' => 'required|integer|min:1',
            'expire_date' => 'nullable|date|after:today',
            'purchase_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'المنتج مطلوب',
            'batch_number.unique' => 'رقم الدفعة مستخدم مسبقاً',
            'quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'expire_date.after' => 'تاريخ الصلاحية يجب أن يكون بعد اليوم',
        ];
    }
}
