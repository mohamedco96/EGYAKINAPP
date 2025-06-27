<?php

namespace App\Modules\Chat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendConsultationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // The patient ID is validated through route model binding
            // No additional request body validation needed for this endpoint
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            // Custom messages if needed
        ];
    }
}
