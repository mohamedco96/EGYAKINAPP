<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
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
        $userId = Auth::id();
        
        return [
            'name' => 'sometimes|string|max:255',
            'lname' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'age' => 'sometimes|integer|min:18|max:100',
            'specialty' => 'sometimes|string|max:255',
            'workingplace' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'job' => 'sometimes|string|max:255',
            'highestdegree' => 'sometimes|string|max:255',
            'registration_number' => 'sometimes|string|unique:users,registration_number,' . $userId,
            'version' => 'sometimes|string|max:50',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
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
