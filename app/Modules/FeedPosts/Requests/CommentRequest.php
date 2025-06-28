<?php

namespace App\Modules\FeedPosts\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
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
            'comment' => 'required|string|max:500',
            'parent_id' => 'nullable|exists:feed_post_comments,id',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'comment.required' => 'Comment content is required.',
            'comment.max' => 'Comment cannot exceed 500 characters.',
            'parent_id.exists' => 'The parent comment does not exist.',
        ];
    }
}