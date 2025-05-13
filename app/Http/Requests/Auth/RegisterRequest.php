<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:6'
            ],
            'age' => 'nullable|integer|min:18|max:100',
            'specialty' => 'nullable|string|max:255',
            'workingplace' => 'nullable|string|max:255',
            'phone' => 'nullable|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'job' => 'nullable|string|max:255',
            'highestdegree' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|unique:users',
            'fcmToken' => 'nullable|string|max:255'
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'value' => false,
            'message' => array_values($validator->errors()->toArray())[0][0],
            'errors' => $validator->errors()
        ], 422));
    }
} 