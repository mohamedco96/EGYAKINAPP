<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

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
            'patient_id' => 'required|exists:users,id',
            'doctor_id' => 'required|exists:users,id',
            'NID' => 'required|string|size:14|regex:/^[0-9]{14}$/',
            'phone' => 'required|string|size:11|regex:/^[0-9]{11}$/',
            'email' => 'required|email|max:255',
            'medical_history' => 'required|array',
            'medical_history.*.condition' => 'required|string|max:255',
            'medical_history.*.diagnosis_date' => 'required|date',
            'medical_history.*.treatment' => 'required|string|max:1000',
            'medical_history.*.status' => 'required|in:active,resolved,chronic',
            'allergies' => 'nullable|array',
            'allergies.*.allergen' => 'required|string|max:255',
            'allergies.*.reaction' => 'required|string|max:1000',
            'allergies.*.severity' => 'required|in:mild,moderate,severe',
            'family_history' => 'nullable|array',
            'family_history.*.condition' => 'required|string|max:255',
            'family_history.*.relation' => 'required|string|max:100',
            'lifestyle' => 'nullable|array',
            'lifestyle.smoking' => 'nullable|boolean',
            'lifestyle.alcohol' => 'nullable|boolean',
            'lifestyle.exercise' => 'nullable|string|max:255',
            'lifestyle.diet' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'NID.required' => 'National ID is required',
            'NID.size' => 'National ID must be exactly 14 digits',
            'NID.regex' => 'National ID must contain only numbers',
            'phone.required' => 'Phone number is required',
            'phone.size' => 'Phone number must be exactly 11 digits',
            'phone.regex' => 'Phone number must contain only numbers',
            'email.required' => 'Email is required',
            'email.email' => 'Please provide a valid email address',
            'medical_history.required' => 'Medical history is required',
            'medical_history.*.condition.required' => 'Medical condition is required',
            'medical_history.*.diagnosis_date.required' => 'Diagnosis date is required',
            'medical_history.*.diagnosis_date.date' => 'Invalid diagnosis date format',
            'medical_history.*.treatment.required' => 'Treatment information is required',
            'medical_history.*.status.required' => 'Status is required',
            'medical_history.*.status.in' => 'Invalid status value',
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
        throw new ValidationException($validator, response()->json([
            'value' => false,
            'message' => array_values($validator->errors()->toArray())[0][0],
            'errors' => $validator->errors(),
        ], 422));
    }
}
