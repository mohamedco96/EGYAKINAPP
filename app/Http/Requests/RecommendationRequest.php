<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecommendationRequest extends FormRequest
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
            'recommendations.*.dose_name' => 'required|string|max:255',
            'recommendations.*.dose' => 'required|string|max:255',
            'recommendations.*.route' => 'required|string|max:100',
            'recommendations.*.frequency' => 'required|string|max:100',
            'recommendations.*.duration' => 'required|string|max:100',
        ];
    }
}
