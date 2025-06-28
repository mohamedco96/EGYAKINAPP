<?php

namespace App\Modules\FeedPosts\Services;

use App\Models\FeedPost;
use App\Models\Poll;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PollService
{
    /**
     * Create a poll for a post
     */
    public function createPoll(FeedPost $post, array $pollData): void
    {
        try {
            $poll = new Poll([
                'question' => $pollData['question'] ?? null,
                'allow_add_options' => $pollData['allow_add_options'] ?? false,
                'allow_multiple_choice' => $pollData['allow_multiple_choice'] ?? false
            ]);

            $post->poll()->save($poll);

            foreach ($pollData['options'] as $optionText) {
                if (!empty($optionText)) {
                    $poll->options()->create(['option_text' => $optionText]);
                }
            }

            Log::info('Poll created successfully for post', ['post_id' => $post->id]);
        } catch (\Exception $e) {
            Log::error('Failed to create poll', ['post_id' => $post->id, 'error' => $e->getMessage()]);
            throw new \Exception('Failed to create poll: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing poll
     */
    public function updatePoll(FeedPost $post, array $pollData): void
    {
        if ($post->poll) {
            // Update existing poll
            $post->poll->update([
                'question' => $pollData['question'] ?? $post->poll->question,
                'allow_add_options' => $pollData['allow_add_options'] ?? $post->poll->allow_add_options,
                'allow_multiple_choice' => $pollData['allow_multiple_choice'] ?? $post->poll->allow_multiple_choice
            ]);

            // Handle options update
            if (isset($pollData['options']) && is_array($pollData['options'])) {
                // Delete existing options
                $post->poll->options()->delete();
                
                // Create new options
                foreach ($pollData['options'] as $optionText) {
                    if (!empty($optionText)) {
                        $post->poll->options()->create(['option_text' => $optionText]);
                    }
                }
            }
        } else {
            // Create new poll
            $this->createPoll($post, $pollData);
        }
    }

    /**
     * Delete a poll and its options
     */
    public function deletePoll(FeedPost $post): void
    {
        if ($post->poll) {
            // Delete poll options first (due to foreign key constraints)
            $post->poll->options()->delete();
            
            // Delete the poll
            $post->poll()->delete();
            
            Log::info('Poll deleted successfully for post', ['post_id' => $post->id]);
        }
    }

    /**
     * Validate poll data
     */
    public function validatePollData(array $pollData): bool
    {
        // Check if poll has required data
        if (!isset($pollData['options']) || !is_array($pollData['options'])) {
            return false;
        }

        // Check if poll has at least 2 options
        $validOptions = array_filter($pollData['options'], function($option) {
            return !empty(trim($option));
        });

        return count($validOptions) >= 2;
    }

    /**
     * Get poll validation rules
     */
    public function getPollValidationRules(): array
    {
        return [
            'poll' => 'nullable|array',
            'poll.question' => 'nullable|string|max:255',
            'poll.allow_add_options' => 'nullable|boolean',
            'poll.allow_multiple_choice' => 'nullable|boolean',
            'poll.options' => 'nullable|array|min:2|max:10',
            'poll.options.*' => 'nullable|string|max:255|distinct'
        ];
    }
}