<?php

namespace App\Modules\DirectChat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReactToMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_id' => 'required|integer|exists:messages,id',
            'reaction' => 'required|string|max:20',
        ];
    }
}
