<?php

namespace App\Modules\Consultations\Requests;

use App\Modules\Patients\Models\Patients;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
            'patient_id' => [
                'required',
                'exists:patients,id',
                function ($attribute, $value, $fail) {
                    $patient = Patients::find($value);
                    if ($patient && $patient->doctor_id !== Auth::id()) {
                        $fail(__('api.consultation_unauthorized_patient'));
                    }
                },
            ],
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
            'patient_id.required' => __('api.patient_id_required'),
            'patient_id.exists' => __('api.patient_not_exist'),
            'consult_message.required' => __('api.consult_message_required'),
            'consult_message.string' => __('api.consult_message_string'),
            'consult_doctor_ids.required' => __('api.consult_doctors_required'),
            'consult_doctor_ids.array' => __('api.consult_doctors_array'),
            'consult_doctor_ids.*.exists' => __('api.consult_doctors_not_exist'),
        ];
    }
}
