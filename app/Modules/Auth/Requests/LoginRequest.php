<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'fcmToken' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9:_-]+$/',
            'deviceId' => 'nullable|string',
            'deviceType' => 'nullable|string|in:ios,android,web',
            'appVersion' => 'nullable|string|max:20|regex:/^[0-9.]+$/',
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
