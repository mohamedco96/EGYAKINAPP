<?php

namespace App\Modules\FeedPosts\Services;

use App\Models\FeedPost;
use App\Models\FeedPostLike;
use App\Models\FeedSaveLike;
use App\Models\Hashtag;
use App\Models\Group;
use App\Models\User;
use App\Models\Poll;
use App\Modules\Notifications\Models\AppNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class FeedPostService
{
    /**
     * Get all feed posts with pagination
     */
    public function getAllPosts(int $perPage = 10): array
    {
        try {
            $posts = FeedPost::with(['doctor', 'comments', 'likes', 'saves'])->paginate($perPage);
            
            if ($posts->isEmpty()) {
                Log::info('No feed posts found');
                return [
                    'value' => true,
                    'data' => [],
                    'message' => 'No feed posts found'
                ];
            }
            
            Log::info('Fetched all feed posts');
            return [
                'value' => true,
                'data' => $posts,
                'message' => 'Feed posts retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching feed posts: ' . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving feed posts'
            ];
        }
    }

    /**
     * Get feed posts for authenticated user
     */
    public function getFeedPosts(): array
    {
        try {
            $doctorId = auth()->id();

            $feedPosts = FeedPost::select([
                'id', 'doctor_id', 'content', 'media_type', 'media_path',
                'visibility', 'group_id', 'created_at', 'updated_at'
            ])
            ->with([
                'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'poll' => function ($query) {
                    $query->select('id', 'feed_post_id', 'question', 'allow_add_options', 'allow_multiple_choice');
                },
                'poll.options' => function ($query) use ($doctorId) {
                    $query->select('id', 'poll_id', 'option_text')
                        ->withCount('votes')
                        ->with(['votes' => function ($voteQuery) use ($doctorId) {
                            $voteQuery->select('id', 'poll_option_id', 'doctor_id')
                                ->where('doctor_id', $doctorId);
                        }]);
                }
            ])
            ->withCount(['likes', 'comments'])
            ->with([
                'saves' => function ($query) use ($doctorId) {
                    $query->select('id', 'feed_post_id', 'doctor_id')
                        ->where('doctor_id', $doctorId);
                },
                'likes' => function ($query) use ($doctorId) {
                    $query->select('id', 'feed_post_id', 'doctor_id')
                        ->where('doctor_id', $doctorId);
                }
            ])
            ->where('group_id', null)
            ->latest('created_at')
            ->paginate(10);

            // Transform posts using collection methods for better performance
            $feedPosts->getCollection()->transform(function ($post) use ($doctorId) {
                $post->isSaved = $post->saves->isNotEmpty();
                $post->isLiked = $post->likes->isNotEmpty();
                $post->doctor_id = (int)$post->doctor_id;
                $post->comments_count = (int)$post->comments_count;
                $post->likes_count = (int)$post->likes_count;

                if ($post->poll) {
                    $post->poll->options = $post->poll->options
                        ->map(function ($option) use ($doctorId) {
                            $option->is_voted = $option->votes->isNotEmpty();
                            unset($option->votes);
                            return $option;
                        })
                        ->sortByDesc('votes_count')
                        ->values();
                }

                unset($post->saves, $post->likes);
                return $post;
            });

            Log::info("Feed posts fetched for doctor ID $doctorId");

            return [
                'value' => true,
                'data' => $feedPosts,
                'message' => 'Feed posts retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching feed posts for doctor ID " . auth()->id() . ": " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving feed posts'
            ];
        }
    }

    /**
     * Get posts by doctor ID
     */
    public function getDoctorPosts(int $doctorId): array
    {
        try {
            $feedPosts = FeedPost::with([
                'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'poll.options' => function ($query) use ($doctorId) {
                    $query->withCount('votes')
                        ->with(['votes' => function ($voteQuery) use ($doctorId) {
                            $voteQuery->where('doctor_id', $doctorId);
                        }]);
                }
            ])
            ->withCount(['likes', 'comments'])
            ->with([
                'saves' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                },
                'likes' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                }
            ])
            ->where('doctor_id', $doctorId)
            ->where('group_id', null)
            ->latest('created_at')
            ->paginate(10);

            $feedPosts->getCollection()->transform(function ($post) use ($doctorId) {
                $post->isSaved = $post->saves->isNotEmpty();
                $post->isLiked = $post->likes->isNotEmpty();

                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) use ($doctorId) {
                        $option->is_voted = $option->votes->isNotEmpty();
                        unset($option->votes);
                        return $option;
                    })->sortByDesc('votes_count')->values();
                }

                unset($post->saves, $post->likes);
                return $post;
            });

            Log::info("Feed posts fetched for doctor ID $doctorId");

            return [
                'value' => true,
                'data' => $feedPosts,
                'message' => 'Doctor Posts retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching Doctor posts for doctor ID $doctorId: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving feed posts'
            ];
        }
    }

    /**
     * Get saved posts for doctor
     */
    public function getDoctorSavedPosts(int $doctorId): array
    {
        if (!$doctorId) {
            Log::error("Doctor ID is missing or undefined.");
            return [
                'value' => false,
                'message' => 'Doctor ID is required'
            ];
        }

        try {
            Log::info("Fetching saved posts for doctor ID: " . $doctorId);

            $feedPosts = FeedPost::whereHas('saves', function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId);
            })
            ->with([
                'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'poll.options' => function ($query) use ($doctorId) {
                    Log::info("Inside poll.options closure - Doctor ID: " . $doctorId);
                    $query->withCount('votes')
                        ->with(['votes' => function ($voteQuery) use ($doctorId) {
                            $voteQuery->where('doctor_id', $doctorId);
                        }]);
                }
            ])
            ->withCount(['likes', 'comments'])
            ->with([
                'saves' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                },
                'likes' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                }
            ])
            ->where('group_id', null)
            ->orderByDesc(
                FeedSaveLike::select('created_at')
                    ->whereColumn('feed_post_id', 'feed_posts.id')
                    ->where('doctor_id', $doctorId)
                    ->latest()
                    ->limit(1)
            )
            ->paginate(10);

            if ($feedPosts->isEmpty()) {
                Log::warning("No saved posts found for doctor ID: " . $doctorId);
            }

            $feedPosts->getCollection()->transform(function ($post) use ($doctorId) {
                $post->isSaved = true;
                $post->isLiked = $post->likes->isNotEmpty();

                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) use ($doctorId) {
                        $option->is_voted = $option->votes->isNotEmpty();
                        unset($option->votes);
                        return $option;
                    })->sortByDesc('votes_count')->values();
                }

                unset($post->saves, $post->likes);
                return $post;
            });

            Log::info("Saved posts successfully fetched for doctor ID: " . $doctorId);

            return [
                'value' => true,
                'data' => $feedPosts,
                'message' => 'Doctor saved posts retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching saved posts for doctor ID $doctorId: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving saved posts'
            ];
        }
    }

    /**
     * Get post by ID
     */
    public function getPostById(int $id): array
    {
        try {
            $doctorId = auth()->id();

            $post = FeedPost::with([
                'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'comments' => function ($query) {
                    $query->orderBy('created_at', 'desc')->paginate(10);
                },
                'comments.doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'poll.options' => function ($query) use ($doctorId) {
                    $query->withCount('votes')
                        ->with(['votes' => function ($voteQuery) use ($doctorId) {
                            $voteQuery->where('doctor_id', $doctorId);
                        }]);
                }
            ])->withCount(['likes', 'comments'])
            ->with([
                'saves' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                },
                'likes' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                }
            ])
            ->findOrFail($id);

            $post->isSaved = $post->saves->isNotEmpty();
            $post->isLiked = $post->likes->isNotEmpty();

            if ($post->poll) {
                $post->poll->options = $post->poll->options->map(function ($option) {
                    $option->is_voted = $option->votes->isNotEmpty();
                    unset($option->votes);
                    return $option;
                })->sortByDesc('votes_count')->values();
            }

            unset($post->saves, $post->likes);

            Log::info("Post ID $id retrieved successfully for doctor ID $doctorId");

            return [
                'value' => true,
                'data' => $post,
                'message' => 'Post retrieved successfully'
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $id not found");
            return [
                'value' => false,
                'data' => [],
                'message' => 'Post not found'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching post ID $id: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving the post'
            ];
        }
    }

    /**
     * Create a new feed post
     */
    public function createPost(array $validatedData, array $mediaPaths = []): array
    {
        $post = FeedPost::create([
            'doctor_id' => Auth::id(),
            'content' => $validatedData['content'],
            'media_type' => $validatedData['media_type'] ?? null,
            'media_path' => $mediaPaths,
            'visibility' => $validatedData['visibility'] ?? 'Public',
            'group_id' => $validatedData['group_id'] ?? null,
        ]);

        if (!$post) {
            throw new \Exception('Post creation failed');
        }

        return [
            'post' => $post,
            'success' => true
        ];
    }

    /**
     * Update a feed post
     */
    public function updatePost(int $id, array $validatedData, array $finalMediaPaths = []): array
    {
        $post = FeedPost::findOrFail($id);
        
        $user = Auth::user();
        $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

        if ($post->doctor_id !== $user->id && !$isAdminOrTester) {
            throw new \Exception('Unauthorized action', 403);
        }

        $post->update([
            'content' => $validatedData['content'],
            'media_type' => $validatedData['media_type'] ?? $post->media_type,
            'media_path' => $finalMediaPaths,
            'visibility' => $validatedData['visibility'] ?? 'Public',
            'group_id' => $validatedData['group_id'] ?? null,
        ]);

        return [
            'post' => $post,
            'success' => true
        ];
    }

    /**
     * Delete a feed post
     */
    public function deletePost(int $id): array
    {
        try {
            $post = FeedPost::with('hashtags')->findOrFail($id);

            $user = Auth::user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            if ($post->doctor_id !== $user->id && !$isAdminOrTester) {
                Log::warning("Unauthorized deletion attempt by doctor " . Auth::id());
                return [
                    'value' => false,
                    'message' => 'Unauthorized action'
                ];
            }

            DB::beginTransaction();
            try {
                $hashtags = $post->hashtags;
                $post->hashtags()->detach();
                
                foreach ($hashtags as $hashtag) {
                    $hashtag->decrement('usage_count', 1);
                    
                    if ($hashtag->usage_count <= 0) {
                        $hashtag->delete();
                    }
                }

                $post->delete();

                $deletedCount = AppNotification::where(function ($query) {
                    $query->where('type', 'PostLike')
                          ->orWhere('type', 'PostComment')
                          ->orWhere('type', 'CommentLike')
                          ->orWhere('type', 'Post');
                })
                ->where('type_id', $id)
                ->delete();
            
                Log::info("Deleted $deletedCount notifications for post ID $id.");
                    
                DB::commit();
                Log::info("Post ID $id and its hashtags deleted by doctor " . Auth::id());
                
                return [
                    'value' => true,
                    'message' => 'Post deleted successfully'
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $id not found for deletion");
            return [
                'value' => false,
                'message' => 'Post not found'
            ];
        } catch (\Exception $e) {
            Log::error("Error deleting post ID $id: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while deleting the post'
            ];
        }
    }

    /**
     * Search posts by query
     */
    public function searchPosts(string $query): array
    {
        if (!$query) {
            $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                10,
                1,
                [
                    'path' => request()->url(),
                    'query' => request()->query()
                ]
            );

            return [
                'value' => true,
                'data' => $emptyPaginator,
                'message' => 'No query provided'
            ];
        }

        try {
            $doctorId = auth()->id();

            $posts = FeedPost::where('content', 'LIKE', '%' . $query . '%')
                ->with([
                    'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                    'poll.options' => function ($query) use ($doctorId) {
                        $query->withCount('votes')
                            ->with(['votes' => function ($voteQuery) use ($doctorId) {
                                $voteQuery->where('doctor_id', $doctorId);
                            }]);
                    }
                ])
                ->withCount(['likes', 'comments'])
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId);
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId);
                    }
                ])
                ->latest('updated_at')
                ->paginate(10);

            $posts->getCollection()->transform(function ($post) use ($doctorId) {
                $post->isSaved = $post->saves->isNotEmpty();
                $post->isLiked = $post->likes->isNotEmpty();

                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) use ($doctorId) {
                        $option->is_voted = $option->votes->isNotEmpty();
                        unset($option->votes);
                        return $option;
                    })->sortByDesc('votes_count')->values();
                }
                
                unset($post->saves, $post->likes);
                return $post;
            });

            if ($posts->isEmpty()) {
                Log::info("No posts found for query: $query");
                return [
                    'value' => true,
                    'data' => $posts,
                    'message' => 'No posts found'
                ];
            }

            Log::info("Posts retrieved for query: $query");
            return [
                'value' => true,
                'data' => $posts,
                'message' => 'Posts retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error searching posts for query $query: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while searching for posts'
            ];
        }
    }

    /**
     * Extract hashtags from content
     */
    public function extractHashtags(string $content): array
    {
        preg_match_all('/#(\w+)/', $content, $matches);
        return $matches[1];
    }

    /**
     * Validate group permissions
     */
    public function validateGroupAccess(?int $groupId): void
    {
        if ($groupId) {
            $group = Group::find($groupId);
            
            if (!$group) {
                throw new \Exception('Group not found', 404);
            }

            if ($group->privacy === 'private' && !$group->doctors->contains(Auth::id())) {
                throw new \Exception('You cannot post in this private group', 403);
            }
        }
    }
}
