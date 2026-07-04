<?php

namespace App\Http\Requests\Prescription;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ✅ بيانات المريض (اختيارية)
            'patient_name' => 'sometimes|string|max:255',
            'patient_age' => 'sometimes|integer|min:0|max:150',
            'patient_gender' => 'sometimes|in:male,female',

            // ✅ بيانات الطبيب (اختيارية)
            'doctor_name' => 'sometimes|string|max:255',

            // ✅ الحالة الطبية والملاحظات (اختيارية)
            'medical_condition' => 'sometimes|string|max:2000',
            'notes' => 'sometimes|nullable|string|max:2000',

            // ✅ الأدوية (اختيارية - يمكن تحديثها كلياً)
            'medicines' => 'sometimes|array|min:1',
            'medicines.*.medicine_name' => 'required_with:medicines|string|max:255',
            'medicines.*.dosage' => 'required_with:medicines|string|max:100',
            'medicines.*.quantity' => 'required_with:medicines|integer|min:1|max:10000',
            'medicines.*.unit' => 'required_with:medicines|in:piece,tablet,capsule,bottle,tube,box,ml,mg,g',
            'medicines.*.frequency' => 'required_with:medicines|string|max:255',
            'medicines.*.instructions' => 'sometimes|nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_name.string' => 'اسم المريض يجب أن يكون نصاً',
            'patient_name.max' => 'اسم المريض لا يمكن أن يتجاوز 255 حرف',
            'patient_age.integer' => 'العمر يجب أن يكون رقماً',
            'patient_age.min' => 'العمر يجب أن يكون 0 على الأقل',
            'patient_age.max' => 'العمر غير منطقي',
            'patient_gender.in' => 'الجنس يجب أن يكون male أو female',
            'doctor_name.string' => 'اسم الطبيب يجب أن يكون نصاً',
            'medical_condition.max' => 'الحالة الطبية لا يمكن أن تتجاوز 2000 حرف',
            'notes.max' => 'الملاحظات لا يمكن أن تتجاوز 2000 حرف',
            'medicines.array' => 'الأدوية يجب أن تكون مصفوفة',
            'medicines.min' => 'يجب إضافة دواء واحد على الأقل',
            'medicines.*.medicine_name.required' => 'اسم الدواء مطلوب',
            'medicines.*.medicine_name.max' => 'اسم الدواء لا يمكن أن يتجاوز 255 حرف',
            'medicines.*.dosage.required' => 'الجرعة مطلوبة',
            'medicines.*.dosage.max' => 'الجرعة لا يمكن أن تتجاوز 100 حرف',
            'medicines.*.quantity.required' => 'الكمية مطلوبة',
            'medicines.*.quantity.integer' => 'الكمية يجب أن تكون رقماً',
            'medicines.*.quantity.min' => 'الكمية يجب أن تكون 1 على الأقل',
            'medicines.*.quantity.max' => 'الكمية لا يمكن أن تتجاوز 10000',
            'medicines.*.unit.required' => 'الوحدة مطلوبة',
            'medicines.*.unit.in' => 'الوحدة يجب أن تكون: piece, tablet, capsule, bottle, tube, box, ml, mg, g',
            'medicines.*.frequency.required' => 'التكرار مطلوب',
            'medicines.*.frequency.max' => 'التكرار لا يمكن أن يتجاوز 255 حرف',
            'medicines.*.instructions.max' => 'التعليمات لا يمكن أن تتجاوز 1000 حرف',
        ];
    }
}
