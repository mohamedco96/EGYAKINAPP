<?php

namespace App\Modules\FeedPosts\Services;

use App\Models\Hashtag;
use App\Models\FeedPost;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class HashtagService
{
    /**
     * Extract hashtags from content
     */
    public function extractHashtags(string $content): array
    {
        preg_match_all('/#(\w+)/', $content, $matches);
        return $matches[1]; // Return an array of hashtags
    }

    /**
     * Attach hashtags to a post
     */
    public function attachHashtags(FeedPost $post, string $content): void
    {
        try {
            // Extract hashtags from content
            $hashtags = $this->extractHashtags($content);

            if (empty($hashtags)) {
                return;
            }

            // Get existing hashtags to avoid duplicates
            $existingHashtags = Hashtag::whereIn('tag', $hashtags)->get()->keyBy('tag');
            
            // Get currently attached hashtags for this post
            $attachedHashtagIds = $post->hashtags()->pluck('hashtags.id')->toArray();
            
            foreach ($hashtags as $hashtagName) {
                // Check if hashtag exists
                if (isset($existingHashtags[$hashtagName])) {
                    $hashtag = $existingHashtags[$hashtagName];
                    
                    // Only increment usage count and attach if not already attached to this post
                    if (!in_array($hashtag->id, $attachedHashtagIds)) {
                        $hashtag->increment('usage_count');
                        $post->hashtags()->attach($hashtag->id);
                    }
                } else {
                    // Create new hashtag
                    $hashtag = Hashtag::create([
                        'tag' => $hashtagName,
                        'usage_count' => 1
                    ]);
                    
                    // Attach to post
                    $post->hashtags()->attach($hashtag->id);
                }
            }

            Log::info('Hashtags attached successfully', [
                'post_id' => $post->id,
                'hashtags' => $hashtags
            ]);
        } catch (\Exception $e) {
            Log::error('Error attaching hashtags', [
                'post_id' => $post->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Detach hashtags from a post and update usage counts
     */
    public function detachHashtags(FeedPost $post): void
    {
        try {
            // Get hashtags before detaching
            $hashtags = $post->hashtags;
            
            // Detach hashtags from the post
            $post->hashtags()->detach();
            
            // Decrement usage count for each hashtag
            foreach ($hashtags as $hashtag) {
                $hashtag->decrement('usage_count', 1);
                
                // If usage count reaches 0, delete the hashtag
                if ($hashtag->usage_count <= 0) {
                    $hashtag->delete();
                }
            }

            Log::info('Hashtags detached successfully', [
                'post_id' => $post->id,
                'hashtag_count' => $hashtags->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error detaching hashtags', [
                'post_id' => $post->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get trending hashtags
     */
    public function getTrendingHashtags(): array
    {
        try {
            $trendingHashtags = Hashtag::orderBy('usage_count', 'desc')->paginate(10);

            return [
                'value' => true,
                'data' => $trendingHashtags,
                'message' => 'Trending hashtags retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Error getting trending hashtags: ' . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving trending hashtags'
            ];
        }
    }

    /**
     * Search hashtags
     */
    public function searchHashtags(string $query): array
    {
        if (empty($query)) {
            return [
                'value' => false,
                'data' => [],
                'message' => 'Query parameter is required'
            ];
        }

        try {
            $hashtags = Hashtag::where('tag', 'LIKE', '%' . $query . '%')->paginate(10);

            if ($hashtags->isEmpty()) {
                Log::info("No hashtags found for query: $query");
                return [
                    'value' => true,
                    'data' => [],
                    'message' => 'No hashtags found'
                ];
            }

            Log::info("Hashtags retrieved for query: $query");
            return [
                'value' => true,
                'data' => $hashtags,
                'message' => 'Hashtags retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error searching hashtags for query $query: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while searching for hashtags'
            ];
        }
    }

    /**
     * Get posts by hashtag
     */
    public function getPostsByHashtag(int $hashtagId): array
    {
        try {
            // Find the hashtag by ID
            $hashtag = Hashtag::find($hashtagId);

            if (!$hashtag) {
                Log::info("No posts found for hashtag ID: $hashtagId");
                return [
                    'value' => true,
                    'data' => [],
                    'message' => 'No posts found for this hashtag'
                ];
            }

            $doctorId = Auth::id(); // Get the authenticated doctor's ID

            // Fetch posts with necessary relationships and counts
            $posts = $hashtag->posts()
                ->with([
                    'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                    'poll.options' => function ($query) use ($doctorId) {
                        $query->withCount('votes') // Count votes per option
                            ->with(['votes' => function ($voteQuery) use ($doctorId) {
                                $voteQuery->where('doctor_id', $doctorId); // Check if user voted
                            }]);
                    }
                ])
                ->withCount(['likes', 'comments'])  // Count likes and comments
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is saved by the doctor
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is liked by the doctor
                    }
                ])
                ->paginate(10); // Paginate 10 posts per page

            // Add 'is_saved' and 'is_liked' fields to each post
            $posts->getCollection()->transform(function ($post) use ($doctorId) {
                // Add 'is_saved' field (true if the doctor saved the post)
                $post->isSaved = $post->saves->isNotEmpty();

                // Add 'is_liked' field (true if the doctor liked the post)
                $post->isLiked = $post->likes->isNotEmpty();

                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) use ($doctorId) {
                        $option->is_voted = $option->votes->isNotEmpty(); // If user has voted for this option
                        unset($option->votes); // Remove unnecessary vote data
                        return $option;
                    })->sortByDesc('votes_count')->values();
                }

                // Remove unnecessary data to clean up the response
                unset($post->saves, $post->likes);

                return $post;
            });

            Log::info("Posts retrieved for hashtag ID: $hashtagId by doctor ID: $doctorId");
            return [
                'value' => true,
                'data' => $posts,
                'message' => 'Posts retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching posts for hashtag ID $hashtagId: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving posts'
            ];
        }
    }
}