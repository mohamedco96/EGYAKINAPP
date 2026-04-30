<?php

namespace App\Modules\DirectChat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type', 'text');

        $fileRules = match ($type) {
            'image' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp',
            'voice' => 'required|file|max:20480|mimes:mp3,wav,ogg,m4a,aac',
            'file' => 'required|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,txt,csv',
            default => 'nullable',
        };

        return [
            'type' => 'required|in:text,image,voice,file',
            'content' => 'required_if:type,text|nullable|string|max:5000',
            'file' => $fileRules,
            'reply_to_id' => 'nullable|integer|exists:messages,id',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required_if' => 'Message content is required for text messages.',
            'file.required' => 'A file is required for this message type.',
        ];
    }
}
