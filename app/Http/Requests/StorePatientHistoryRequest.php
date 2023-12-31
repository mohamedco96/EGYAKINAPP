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
            //'0' => 'string',
            //'1' => 'string',
            //'2' => 'string',
           // '3' => 'string|size:14',
           // '4' => 'string|size:11',
            /* 
            'doctor_id' => 'required|string|exists:App\Models\User,id',
            'name' => 'required|string',
            'hospital' => 'required|string',
            'collected_data_from' => 'required|string',
            'NID' => 'string|size:14',
            'phone' => 'string|size:11',
            'email' => 'email|string',
            'age' => 'required|string',
            'gender' => 'required|string',
            'occupation' => 'required|string',
            'residency' => 'required|string',
            'governorate' => 'required|string',
            'marital_status' => 'required|string',
            'educational_level' => 'required|string',
            'special_habits_of_the_patient' => 'required|array',
            'DM' => 'required|string',
            'HTN' => 'required|string',*/
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
