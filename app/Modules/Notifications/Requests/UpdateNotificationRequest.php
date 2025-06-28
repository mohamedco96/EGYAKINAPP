<?php

namespace App\Modules\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationRequest extends FormRequest
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
            'title' => 'sometimes|required|string|max:255',
            'message' => 'sometimes|required|string|max:1000',
            'type' => 'sometimes|required|string|in:info,warning,success,error',
            'is_read' => 'sometimes|boolean',
            'read_at' => 'sometimes|nullable|date',
            'data' => 'sometimes|nullable|array',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The notification title is required.',
            'title.string' => 'The notification title must be a string.',
            'title.max' => 'The notification title may not be greater than 255 characters.',
            'message.required' => 'The notification message is required.',
            'message.string' => 'The notification message must be a string.',
            'message.max' => 'The notification message may not be greater than 1000 characters.',
            'type.required' => 'The notification type is required.',
            'type.in' => 'The notification type must be one of: info, warning, success, error.',
            'is_read.boolean' => 'The is_read field must be true or false.',
            'read_at.date' => 'The read_at field must be a valid date.',
            'data.array' => 'The data field must be an array.',
        ];
    }
}
