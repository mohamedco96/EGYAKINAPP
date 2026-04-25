<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessAISectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'audio' => 'nullable|file|mimes:mp3,wav,m4a,ogg,webm|max:25600',
            'images' => 'nullable|array|min:1|max:10',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
            'section_id' => 'required|integer|exists:sections_infos,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasAudio = $this->hasFile('audio');
            $hasImages = $this->hasFile('images');

            if (! $hasAudio && ! $hasImages) {
                $validator->errors()->add('audio', 'Either an audio or at least one image file is required.');
                $validator->errors()->add('images', 'Either an audio or at least one image file is required.');
            }

            if ($hasAudio && $hasImages) {
                $validator->errors()->add('audio', 'Audio and images cannot be sent together. Send one or the other.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'audio.mimes' => 'Audio must be mp3, wav, m4a, ogg, or webm.',
            'audio.max' => 'Audio file must not exceed 25MB.',
            'images.min' => 'At least one image file is required.',
            'images.max' => 'You can upload a maximum of 10 files per request.',
            'images.*.mimes' => 'Each image must be jpg, jpeg, png, webp, or pdf.',
            'images.*.max' => 'Each image file must not exceed 20MB.',
            'section_id.required' => 'Section ID is required.',
            'section_id.integer' => 'Section ID must be an integer.',
            'section_id.exists' => 'Section not found.',
        ];
    }
}
