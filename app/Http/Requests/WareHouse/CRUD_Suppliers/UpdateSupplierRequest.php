<?php

namespace App\Http\Requests\Warehouse\CRUD_Suppliers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('id');

        return [
            'name' => 'nullable|string|max:255|unique:suppliers,name,' . $supplierId,
            'email' => 'nullable|email|unique:suppliers,email,' . $supplierId,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المورد مطلوب',
            'name.unique' => 'اسم المورد مستخدم مسبقاً',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',
        ];
    }
}
