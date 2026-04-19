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
            'audio'          => 'required_without:images|prohibits:images|file|mimes:mp3,wav,m4a,ogg,webm|max:25600',
            'images'         => 'required_without:audio|prohibits:audio|array|min:1|max:5',
            'images.*'       => 'file|mimes:jpg,jpeg,png,webp,pdf|max:20480',
            'section_id'     => 'required|integer|exists:sections_infos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'audio.required_without'   => 'Either an audio or at least one image file is required.',
            'audio.prohibits'          => 'Audio and images cannot be sent together. Send one or the other.',
            'audio.mimes'              => 'Audio must be mp3, wav, m4a, ogg, or webm.',
            'audio.max'                => 'Audio file must not exceed 25MB.',
            'images.required_without'  => 'Either an audio or at least one image file is required.',
            'images.prohibits'         => 'Audio and images cannot be sent together. Send one or the other.',
            'images.min'               => 'At least one image file is required.',
            'images.max'               => 'You can upload a maximum of 5 images per request.',
            'images.*.mimes'           => 'Each image must be jpg, jpeg, png, webp, or pdf.',
            'images.*.max'             => 'Each image file must not exceed 20MB.',
            'section_id.required'      => 'Section ID is required.',
            'section_id.integer'       => 'Section ID must be an integer.',
            'section_id.exists'        => 'Section not found.',
        ];
    }
}
