<?php

namespace App\Modules\DirectChat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManageParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.*.exists' => 'One or more selected users do not exist.',
        ];
    }
}
