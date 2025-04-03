<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeedPostRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
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
} 