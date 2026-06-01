<?php
// app/Http/Requests/Warehouse/MovementFilterRequest.php
namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class MovementFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'nullable|exists:products,id',
            'department_id' => 'nullable|exists:departments,id',
            'type' => 'nullable|in:in,out,damage,adjustment',
            'user_id' => 'nullable|exists:users,id',
            'from_date' => 'nullable|date|before_or_equal:to_date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'from_date.before_or_equal' => 'تاريخ البداية يجب أن يكون قبل أو يساوي تاريخ النهاية',
            'to_date.after_or_equal' => 'تاريخ النهاية يجب أن يكون بعد أو يساوي تاريخ البداية',
        ];
    }
}
