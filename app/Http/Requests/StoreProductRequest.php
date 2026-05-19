<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * تحديد ما إذا كان المستخدم مصرحاً له بإجراء هذا الطلب.
     */
    public function authorize(): bool
    {
        // جعلناها true بشكل صريح لضمان عبور الطلب من بوابة الـ Request
        return true; 
    }

    /**
     * شروط التحقق التي سيتم تطبيقها على البيانات المرسلة.
     */
    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|max:255|unique:products,code',
            'type'          => 'required|in:fixed,consumable', // fixed للأجهزة والمعدات، consumable للمستلزمات الطبية اليومية
            'minimum_stock' => 'nullable|integer|min:0',
            'unit'          => 'nullable|string|max:100', // مثلاً: علبة، قطعة، ليتر
            'description'   => 'nullable|string',
        ];
    }
}