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
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'password' => 'nullable|string|min:8',
            'user_type' => 'nullable|string|in:normal,medical_statistics',
            'lname' => 'nullable|string|max:255',
            'age' => 'nullable|integer|min:0|max:150',
            'specialty' => 'nullable|string|max:255',
            'workingplace' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'job' => 'nullable|string|max:255',
            'highestdegree' => 'nullable|string|max:255',
            'gender' => 'nullable|string|in:male,female,other',
            'birth_date' => 'nullable|date',
            'registration_number' => 'nullable|string|max:255|unique:users,registration_number,'.$userId,
            'locale' => 'nullable|string|in:en,ar',
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
