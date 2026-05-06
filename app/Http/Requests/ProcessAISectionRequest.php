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
            'images' => 'nullable|array|max:10',
            'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:20480',
            'files' => 'nullable|array|max:10',
            'files.*' => 'file|mimes:pdf|max:20480',
            'section_id' => 'required|integer|exists:sections_infos,id',
            'patient_id' => 'nullable|integer|exists:patients,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasAudio = $this->hasFile('audio');
            $hasImages = $this->hasFile('images');
            $hasFiles = $this->hasFile('files');
            $hasMedia = $hasImages || $hasFiles;

            if (! $hasAudio && ! $hasMedia) {
                $validator->errors()->add('audio', 'Either an audio or at least one image/PDF file is required.');
                $validator->errors()->add('images', 'Either an audio or at least one image/PDF file is required.');
            }

            if ($hasAudio && $hasMedia) {
                $validator->errors()->add('audio', 'Audio and image/PDF files cannot be sent together. Send one or the other.');
            }

            $totalFiles = count((array) $this->file('images')) + count((array) $this->file('files'));
            if ($totalFiles > 10) {
                $validator->errors()->add('images', 'You can upload a maximum of 10 files per request.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'audio.mimes' => 'Audio must be mp3, wav, m4a, ogg, or webm.',
            'audio.max' => 'Audio file must not exceed 25MB.',
            'images.max' => 'You can upload a maximum of 10 files per request.',
            'images.*.mimes' => 'Each image must be jpg, jpeg, png, or webp.',
            'images.*.max' => 'Each image file must not exceed 20MB.',
            'files.max' => 'You can upload a maximum of 10 files per request.',
            'files.*.mimes' => 'Each file must be a PDF.',
            'files.*.max' => 'Each PDF file must not exceed 20MB.',
            'section_id.required' => 'Section ID is required.',
            'section_id.integer' => 'Section ID must be an integer.',
            'section_id.exists' => 'Section not found.',
            'patient_id.integer' => 'Patient ID must be an integer.',
            'patient_id.exists' => 'Patient not found.',
        ];
    }
}
