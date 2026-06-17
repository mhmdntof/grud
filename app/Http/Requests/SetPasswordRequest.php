<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'verification_token' => 'required|string',

            'password' => 'required|string|min:6|confirmed',
'phone'=>'nullable'
        ];
    }
}