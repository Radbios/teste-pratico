<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCreateUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {

        $rules = [
            "name" => ["required"],
            "email" => ["email", "required"],
            "password" => ["required", "confirmed", 'min:8'],
        ];

        if($this->method() === 'PUT' || $this->method() === 'PATCH'){
            $rules['password'] = ['nullable', 'confirmed', 'min:8'];
        }
        return $rules;
    }
}
