<?php

namespace App\Modules\Consultations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:patients,id',
            'consult_message' => 'required|string',
            'consult_doctor_ids' => 'required|array',
            'consult_doctor_ids.*' => 'exists:users,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'Patient ID is required.',
            'patient_id.exists' => 'The selected patient does not exist.',
            'consult_message.required' => 'Consultation message is required.',
            'consult_message.string' => 'Consultation message must be a string.',
            'consult_doctor_ids.required' => 'At least one consulting doctor is required.',
            'consult_doctor_ids.array' => 'Consulting doctors must be provided as an array.',
            'consult_doctor_ids.*.exists' => 'One or more selected doctors do not exist.',
        ];
    }
}
