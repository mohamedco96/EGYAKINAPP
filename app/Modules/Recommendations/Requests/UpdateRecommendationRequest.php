<?php

namespace App\Modules\Recommendations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecommendationRequest extends FormRequest
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
            'recommendations' => 'required|array',
            'recommendations.*.type' => 'nullable|string|in:note,rec',
            'recommendations.*.content' => 'nullable|string|required_if:recommendations.*.type,note',
            'recommendations.*.dose_name' => 'nullable|string|max:255|required_if:recommendations.*.type,rec',
            'recommendations.*.dose' => 'nullable|string|max:255|required_if:recommendations.*.type,rec',
            'recommendations.*.route' => 'nullable|string|max:100|required_if:recommendations.*.type,rec',
            'recommendations.*.frequency' => 'nullable|string|max:100|required_if:recommendations.*.type,rec',
            'recommendations.*.duration' => 'nullable|string|max:100|required_if:recommendations.*.type,rec',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'recommendations.required' => 'Recommendations array is required.',
            'recommendations.array' => 'Recommendations must be an array.',
            'recommendations.*.dose_name.required' => 'Dose name is required for each recommendation.',
            'recommendations.*.dose_name.string' => 'Dose name must be a string.',
            'recommendations.*.dose_name.max' => 'Dose name cannot exceed 255 characters.',
            'recommendations.*.dose.required' => 'Dose is required for each recommendation.',
            'recommendations.*.dose.string' => 'Dose must be a string.',
            'recommendations.*.dose.max' => 'Dose cannot exceed 255 characters.',
            'recommendations.*.route.required' => 'Route is required for each recommendation.',
            'recommendations.*.route.string' => 'Route must be a string.',
            'recommendations.*.route.max' => 'Route cannot exceed 100 characters.',
            'recommendations.*.frequency.required' => 'Frequency is required for each recommendation.',
            'recommendations.*.frequency.string' => 'Frequency must be a string.',
            'recommendations.*.frequency.max' => 'Frequency cannot exceed 100 characters.',
            'recommendations.*.duration.required' => 'Duration is required for each recommendation.',
            'recommendations.*.duration.string' => 'Duration must be a string.',
            'recommendations.*.duration.max' => 'Duration cannot exceed 100 characters.',
        ];
    }
}
