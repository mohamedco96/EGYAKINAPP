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
            'audio'      => 'required_without:image|prohibits:image|file|mimes:mp3,wav,m4a,ogg,webm|max:25600',
            'image'      => 'required_without:audio|prohibits:audio|file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
            'section_id' => 'required|integer|exists:sections_infos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'audio.required_without' => 'Either an audio or an image file is required.',
            'audio.prohibits'        => 'Audio and image cannot be sent together. Send one or the other.',
            'audio.mimes'            => 'Audio must be mp3, wav, m4a, ogg, or webm.',
            'audio.max'              => 'Audio file must not exceed 25MB.',
            'image.required_without' => 'Either an audio or an image file is required.',
            'image.prohibits'        => 'Audio and image cannot be sent together. Send one or the other.',
            'image.mimes'            => 'Image must be jpg, jpeg, png, webp, or pdf.',
            'image.max'              => 'Image file must not exceed 20MB.',
            'section_id.required'    => 'Section ID is required.',
            'section_id.integer'     => 'Section ID must be an integer.',
            'section_id.exists'      => 'Section not found.',
        ];
    }
}
