<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'name' => 'required|string|max:255',

            'code' => 'required|string|unique:products,code',

           'type' => 'required|string|in:fixed,consumable,medicine',

            'minimum_stock' => 'nullable|integer|min:0',

            'unit' => 'required|in:piece,liter,kilogram,dozen',
            
            'description' => 'nullable|string',
            'maximum_stock' => 'required',
            'brand' => 'nullable',
            'storage_location'=>'nullable'
        ];
    }
}