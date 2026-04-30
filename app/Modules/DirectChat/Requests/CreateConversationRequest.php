<?php

namespace App\Modules\DirectChat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:private,case_group,social_group',
            'name' => 'required_unless:type,private|nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_unless' => 'A group name is required for case groups and social groups.',
            'participant_ids.required' => 'At least one participant is required.',
            'participant_ids.*.exists' => 'One or more selected users do not exist.',
        ];
    }
}
