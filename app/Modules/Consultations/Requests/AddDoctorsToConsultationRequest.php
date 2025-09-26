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
            'consult_doctor_ids.required' => __('api.consult_doctors_required'),
            'consult_doctor_ids.array' => __('api.consult_doctors_array'),
            'consult_doctor_ids.min' => __('api.consult_doctors_min'),
            'consult_doctor_ids.*.exists' => __('api.consult_doctors_not_exist'),
        ];
    }
}
