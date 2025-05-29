<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientHistoryRequest extends FormRequest
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
            //'3' => 'required|size:13',
            //'4' => 'required|digits:11',
           // '5' => 'required|email',
        ];
    }

    public function messages()
    {
    return [
        //'3' => 'The NID field must be 14 numbers.',
        //'4' => 'The phone field must be 11 numbers.',
        //'3.required' => 'Custom error message for key 3',
        // Add custom error messages for other keys and rules
    ];
    }
}
