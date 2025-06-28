<?php

namespace App\Modules\Questions\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionsRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'section_id' => 'required|integer|exists:sections_infos,id',
            'section_name' => 'required|string|max:255',
            'question' => 'required|string',
            'values' => 'nullable|string',
            'type' => 'required|string|in:text,number,select,multiple,checkbox,radio,date,time,datetime',
            'keyboard_type' => 'nullable|string|max:255',
            'mandatory' => 'boolean',
            'hidden' => 'boolean',
            'skip' => 'boolean',
            'sort' => 'integer|min:0',
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
            'section_id.required' => 'The section ID is required.',
            'section_id.integer' => 'The section ID must be an integer.',
            'section_id.exists' => 'The selected section does not exist.',
            'section_name.required' => 'The section name is required.',
            'question.required' => 'The question text is required.',
            'type.required' => 'The question type is required.',
            'type.in' => 'The question type must be one of: text, number, select, multiple, checkbox, radio, date, time, datetime.',
        ];
    }
}
