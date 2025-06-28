<?php

namespace App\Modules\Recommendations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRecommendationRequest extends FormRequest
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
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'IDs array is required.',
            'ids.array' => 'IDs must be an array.',
            'ids.*.integer' => 'Each ID must be an integer.',
        ];
    }
}
