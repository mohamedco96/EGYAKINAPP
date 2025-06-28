<?php

namespace App\Modules\FeedPosts\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => 'nullable|string',
            'media_type' => 'nullable|string|in:image,video,text',
            'media_path' => 'nullable|array|max:10',
            'media_path.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mkv|max:20480',
            'visibility' => 'nullable|string|in:Public,Friends,Only Me',
            'group_id' => 'nullable|exists:groups,id',
            'poll' => 'nullable|array',
            'poll.question' => 'nullable|string|max:255',
            'poll.allow_add_options' => 'nullable|boolean',
            'poll.allow_multiple_choice' => 'nullable|boolean',
            'poll.options' => 'nullable|array|min:2|max:10',
            'poll.options.*' => 'nullable|string|max:255|distinct'
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'media_path.*.max' => 'Each media file must not exceed 20MB.',
            'media_path.*.mimes' => 'Media files must be of type: jpeg, png, jpg, gif, mp4, mkv.',
            'media_path.max' => 'You can upload a maximum of 10 media files.',
            'poll.options.min' => 'Poll must have at least 2 options.',
            'poll.options.max' => 'Poll can have a maximum of 10 options.',
            'poll.options.*.distinct' => 'Poll options must be unique.',
            'group_id.exists' => 'The selected group does not exist.',
            'visibility.in' => 'Visibility must be one of: Public, Friends, Only Me.'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: ensure content or media is provided
            if (empty($this->input('content')) && !$this->hasFile('media_path')) {
                $validator->errors()->add('content', 'Either content or media must be provided.');
            }

            // Custom validation: validate poll options if poll is provided
            if ($this->has('poll') && $this->has('poll.options')) {
                $options = array_filter($this->input('poll.options'), function($option) {
                    return !empty(trim($option));
                });
                
                if (count($options) < 2) {
                    $validator->errors()->add('poll.options', 'Poll must have at least 2 non-empty options.');
                }
            }
        });
    }
}