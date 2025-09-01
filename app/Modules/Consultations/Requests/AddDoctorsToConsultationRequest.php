<?php

namespace App\Modules\Consultations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddDoctorsToConsultationRequest extends FormRequest
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
            'consult_doctor_ids' => 'required|array|min:1',
            'consult_doctor_ids.*' => 'exists:users,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'consult_doctor_ids.required' => 'At least one consulting doctor is required.',
            'consult_doctor_ids.array' => 'Consulting doctors must be provided as an array.',
            'consult_doctor_ids.min' => 'At least one consulting doctor is required.',
            'consult_doctor_ids.*.exists' => 'One or more selected doctors do not exist.',
        ];
    }
}
