<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
           'name' => 'required|string|max:255',

            'email' => 'required|email|unique:users,email',

            'role' => 'required|exists:roles,name',

           'department' => 'required|exists:departments,name',
        ];
    }
}