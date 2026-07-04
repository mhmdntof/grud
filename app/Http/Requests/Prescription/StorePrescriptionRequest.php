<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_name' => 'required|string|max:255',
            'patient_age' => 'nullable|integer|min:0|max:150',
            'patient_gender' => 'nullable|in:male,female',
            'doctor_name' => 'required|string|max:255',
            'medical_condition' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:2000',

            'medicines' => 'required|array|min:1|max:10',
            'medicines.*.medicine_name' => 'required|string|max:255',
            'medicines.*.dosage' => 'nullable|string|max:100',
            'medicines.*.quantity' => 'required|integer|min:1',
            'medicines.*.unit' => 'required|in:piece,liter,kilogram,dozen',
            'medicines.*.frequency' => 'nullable|string|max:100',
            'medicines.*.instructions' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_name.required' => 'اسم المريض مطلوب',
            'doctor_name.required' => 'اسم الطبيب مطلوب',
            'medical_condition.required' => 'الحالة الطبية مطلوبة',
            'medicines.required' => 'يجب إضافة دواء واحد على الأقل',
            'medicines.min' => 'يجب إضافة دواء واحد على الأقل',
            'medicines.max' => 'الحد الأقصى 10 أدوية في الوصفة الواحدة',
            'medicines.*.medicine_name.required' => 'اسم الدواء مطلوب',
            'medicines.*.quantity.required' => 'الكمية مطلوبة',
            'medicines.*.quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'medicines.*.unit.required' => 'الوحدة مطلوبة',
        ];
    }
}
