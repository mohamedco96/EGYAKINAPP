<?php

namespace App\Modules\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingsRequest extends FormRequest
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
            'app_freeze' => 'required|boolean',
            'force_update' => 'required|boolean',
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
            'app_freeze.required' => 'The app freeze field is required.',
            'app_freeze.boolean' => 'The app freeze field must be true or false.',
            'force_update.required' => 'The force update field is required.',
            'force_update.boolean' => 'The force update field must be true or false.',
        ];
    }
}
