<?php

namespace App\Modules\Consultations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultationRequest extends FormRequest
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
            'reply' => 'required|string',
            'patient_id' => 'nullable|exists:patients,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'reply.required' => __('api.reply_required'),
            'reply.string' => __('api.reply_string'),
            'patient_id.exists' => __('api.patient_not_exist'),
        ];
    }
}
