<?php

namespace App\Modules\DirectChat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:5120|mimes:jpg,jpeg,png,webp',
        ];
    }
}
