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
            'owner_id' => 'required|string|exists:App\Models\User,id',
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
            'special_habits_of_the_patient' => 'required|string',
            'DM' => 'required|string',
            'DM_duration' => 'interger',
            'HTN' => 'required|string',
            'HTN_duration' => 'interger'
        ];
    }
}
