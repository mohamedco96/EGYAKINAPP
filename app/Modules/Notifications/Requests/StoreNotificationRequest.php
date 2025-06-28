<?php

namespace App\Modules\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
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
            'content' => 'required|string',
            'type' => 'nullable|string|max:255',
            'type_id' => 'nullable|integer',
            'patient_id' => 'nullable|integer|exists:patients,id',
            'doctor_id' => 'required|integer|exists:users,id',
            'type_doctor_id' => 'nullable|integer|exists:users,id',
            'read' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'The notification content is required.',
            'doctor_id.required' => 'The doctor ID is required.',
            'doctor_id.exists' => 'The specified doctor does not exist.',
            'patient_id.exists' => 'The specified patient does not exist.',
            'type_doctor_id.exists' => 'The specified type doctor does not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'read' => $this->input('read', false),
            'doctor_id' => $this->input('doctor_id', auth()->id()),
        ]);
    }
}
