<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:6',
            ],
            'age' => 'nullable|integer|min:18|max:100',
            'specialty' => 'nullable|string|max:255',
            'workingplace' => 'nullable|string|max:255',
            'phone' => 'nullable|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'job' => 'nullable|string|max:255',
            'highestdegree' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|unique:users',
            'fcmToken' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9:_-]+$/',
            'deviceId' => 'nullable|string',
            'deviceType' => 'nullable|string|in:ios,android,web',
            'appVersion' => 'nullable|string|max:20|regex:/^[0-9.]+$/',
            'user_type' => 'nullable|string|in:normal,medical_statistics',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $exception = new ValidationException($validator);
        $exception->status = 422;
        throw $exception;
    }
}
