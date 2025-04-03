<?php

namespace App\Services;

use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostCommentLike;
use App\Models\FeedPostLike;
use App\Models\FeedSaveLike;
use App\Models\Hashtag;
use App\Models\Group;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\NotificationController;

class FeedPostService
{
    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }

    public function extractHashtags($content)
    {
        preg_match_all('/#(\w+)/', $content, $matches);
        return $matches[1];
    }

    public function getFeedPosts()
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

            $feedPosts->getCollection()->transform(function ($post) use ($doctorId) {
                $post->isSaved = $post->saves->isNotEmpty();
                $post->isLiked = $post->likes->isNotEmpty();

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

            return [
                'value' => true,
                'data' => $feedPosts,
                'message' => 'Feed posts retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching feed posts for doctor ID $doctorId: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving feed posts'
            ];
        }
    }

    public function getDoctorPosts($doctorId)
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

    public function getDoctorSavedPosts($doctorId)
    {
        if (!$doctorId) {
            Log::error("Doctor ID is missing or undefined.");
            return [
                'value' => false,
                'message' => 'Doctor ID is required'
            ];
        }

        try {
            $feedPosts = FeedPost::whereHas('saves', function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId);
            })
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
            ->where('group_id', null)
            ->orderByDesc(
                FeedSaveLike::select('created_at')
                    ->whereColumn('feed_post_id', 'feed_posts.id')
                    ->where('doctor_id', $doctorId)
                    ->latest()
                    ->limit(1)
            )
            ->paginate(10);

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

    public function getPostById($id)
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

    public function getPostLikes($postId)
    {
        try {
            $likes = FeedPostLike::where('feed_post_id', $postId)
                ->with(['doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired'])
                ->paginate(10);

            if ($likes->isEmpty()) {
                Log::info("No likes found for post ID $postId");
                return [
                    'value' => false,
                    'data' => [],
                    'message' => 'No likes found for this post'
                ];
            }

            $doctorData = $likes->getCollection()->map(function ($like) {
                return $like->doctor;
            });

            $paginatedDoctorData = new \Illuminate\Pagination\LengthAwarePaginator(
                $doctorData,
                $likes->total(),
                $likes->perPage(),
                $likes->currentPage(),
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );

            return [
                'value' => true,
                'data' => $paginatedDoctorData,
                'message' => 'Post likes retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching likes for post ID $postId: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving post likes'
            ];
        }
    }

    public function getPostComments($postId)
    {
        try {
            $doctorId = auth()->id();
            $postOwnerId = FeedPost::find($postId)->doctor_id;
            $perPage = 10;
            $page = request()->get('page', 1);

            $comments = FeedPostComment::where('feed_post_id', $postId)
                ->whereNull('parent_id')
                ->with([
                    'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                    'replies' => function ($query) use ($doctorId) {
                        $query->with([
                            'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                            'likes' => function ($query) use ($doctorId) {
                                $query->where('doctor_id', $doctorId);
                            },
                            'replies' => function ($query) use ($doctorId) {
                                $query->withCount(['likes as likes_count', 'replies as replies_count'])
                                    ->with([
                                        'likes' => function ($query) use ($doctorId) {
                                            $query->where('doctor_id', $doctorId);
                                        }
                                    ]);
                            }
                        ])->withCount(['likes as likes_count', 'replies as replies_count']);
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId);
                    }
                ])
                ->withCount(['likes as likes_count', 'replies as replies_count'])
                ->get();

            $comments = $comments->sortByDesc(function ($comment) use ($doctorId, $postOwnerId) {
                return [
                    $comment->doctor_id === $postOwnerId ? 2 : 0,
                    $comment->doctor_id === $doctorId ? 1 : 0
                ];
            })->values();

            $paginatedComments = $comments->forPage($page, $perPage);

            foreach ($paginatedComments as $comment) {
                $comment->isLiked = $comment->likes->isNotEmpty();

                foreach ($comment->replies as $reply) {
                    $reply->isLiked = $reply->likes->isNotEmpty();

                    foreach ($reply->replies as $nestedReply) {
                        $nestedReply->isLiked = $nestedReply->likes->isNotEmpty();
                    }
                }

                unset($comment->likes);
                foreach ($comment->replies as $reply) {
                    unset($reply->likes);
                    foreach ($reply->replies as $nestedReply) {
                        unset($nestedReply->likes);
                    }
                }
            }

            $paginatedResponse = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedComments,
                $comments->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return [
                'value' => true,
                'data' => $paginatedResponse,
                'message' => 'Post comments retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching comments for post ID $postId: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving post comments'
            ];
        }
    }

    public function store($validatedData, $mediaPaths)
    {
        DB::beginTransaction();

        try {
            $this->handleGroupValidation($validatedData);

            $post = $this->createFeedPost($validatedData, $mediaPaths);
            $this->attachHashtags($post, $validatedData['content']);

            if (isset($validatedData['poll']) && isset($validatedData['poll']['options'])) {
                $poll = new Poll([
                    'question' => $validatedData['poll']['question'] ?? null,
                    'allow_add_options' => $validatedData['poll']['allow_add_options'] ?? false,
                    'allow_multiple_choice' => $validatedData['poll']['allow_multiple_choice'] ?? false
                ]);

                $post->poll()->save($poll);

                foreach ($validatedData['poll']['options'] as $optionText) {
                    if (!empty($optionText)) {
                        $poll->options()->create(['option_text' => $optionText]);
                    }
                }
            }

            DB::commit();

            $this->notifyDoctors($post);

            return [
                'value' => true,
                'data' => $post->load('poll.options'),
                'message' => 'Post created successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating post: " . $e->getMessage());
            return [
                'value' => false,
                'message' => "Error creating post: " . $e->getMessage()
            ];
        }
    }

    public function update($id, $validatedData)
    {
        DB::beginTransaction();

        try {
            $post = FeedPost::findOrFail($id);
            $user = Auth::user();

            $this->handleGroupValidation($validatedData);

            $mediaType = $post->media_type;
            $existingMediaPaths = $post->media_path ?? [];

            if (isset($validatedData['media_type'])) {
                $mediaType = $validatedData['media_type'];
                if ($mediaType === "text") {
                    $mediaType = null;
                    $existingMediaPaths = [];
                }
            }

            $existingMediaFromRequest = is_array($validatedData['existing_media_path'] ?? []) 
                ? $validatedData['existing_media_path'] 
                : [];

            $currentMediaPaths = $post->media_path ?? [];
            $finalMediaPaths = array_intersect($currentMediaPaths, $existingMediaFromRequest);

            $newMediaPaths = [];
            if (isset($validatedData['media_path'])) {
                $newMediaPaths = $this->uploadMultipleImages($validatedData['media_path']);
            }

            $finalMediaPaths = array_merge($finalMediaPaths, $newMediaPaths);

            $post->update([
                'content' => $validatedData['content'],
                'media_type' => $mediaType,
                'media_path' => $finalMediaPaths,
                'visibility' => $validatedData['visibility'] ?? 'Public',
                'group_id' => $validatedData['group_id'] ?? null,
            ]);

            if ($post->wasChanged('content')) {
                $this->handleHashtagUpdate($post, $validatedData['content']);
            }

            if (isset($validatedData['poll']) && isset($validatedData['poll']['options'])) {
                $this->handlePollUpdate($post, $validatedData['poll']);
            }

            DB::commit();

            return [
                'value' => true,
                'data' => $post->load('poll.options'),
                'message' => 'Post updated successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating post ID $id: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while updating the post: ' . $e->getMessage()
            ];
        }
    }

    public function destroy($id)
    {
        try {
            $post = FeedPost::with('hashtags')->findOrFail($id);
            $user = Auth::user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            if ($post->doctor_id !== $user->id && !$isAdminOrTester) {
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

                DB::commit();
                return [
                    'value' => true,
                    'message' => 'Post deleted successfully'
                ];
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error("Error deleting post ID $id: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while deleting the post'
            ];
        }
    }

    private function handleGroupValidation(array &$validatedData)
    {
        if (!empty($validatedData['group_id'])) {
            $group = Group::with('doctors')->find($validatedData['group_id']);

            if (!$group) {
                throw new \Exception('Group not found', 404);
            }

            if ($group->privacy === 'private' && !$group->doctors->contains(Auth::id())) {
                throw new \Exception('You cannot post in this private group', 403);
            }

            $validatedData['group_name'] = $group->name;
        }
    }

    private function uploadMultipleImages($files)
    {
        $uploadedImages = [];

        if (!empty($files)) {
            foreach ($files as $media) {
                $name = auth()->user()->name;
                $timestamp = time() . '_' . uniqid();
                $fileName = "{$name}_media_{$timestamp}." . $media->getClientOriginalExtension();
                $storedPath = $media->storeAs('media_images', $fileName, 'public');
                $mediaUrl = config('app.url') . '/storage/' . $storedPath;
                $uploadedImages[] = $mediaUrl;
            }
        }

        return $uploadedImages;
    }

    private function createFeedPost(array $validatedData, $mediaPaths)
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

        return $post;
    }

    private function attachHashtags(FeedPost $post, $content)
    {
        try {
            preg_match_all('/#(\w+)/', $content, $matches);
            $hashtags = $matches[1];
            $existingHashtags = Hashtag::whereIn('tag', $hashtags)->get()->keyBy('tag');
            $attachedHashtagIds = $post->hashtags()->pluck('hashtags.id')->toArray();

            foreach ($hashtags as $hashtagName) {
                if (isset($existingHashtags[$hashtagName])) {
                    $hashtag = $existingHashtags[$hashtagName];
                    if (!in_array($hashtag->id, $attachedHashtagIds)) {
                        $hashtag->increment('usage_count');
                        $post->hashtags()->attach($hashtag->id);
                    }
                } else {
                    $hashtag = Hashtag::create([
                        'tag' => $hashtagName,
                        'usage_count' => 1
                    ]);
                    $post->hashtags()->attach($hashtag->id);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error attaching hashtags', [
                'post_id' => $post->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleHashtagUpdate(FeedPost $post, $content)
    {
        DB::beginTransaction();
        try {
            $oldHashtags = $post->hashtags;
            $post->hashtags()->detach();

            foreach ($oldHashtags as $hashtag) {
                $hashtag->decrement('usage_count', 1);
                if ($hashtag->usage_count <= 0) {
                    $hashtag->delete();
                }
            }

            $this->attachHashtags($post, $content);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function handlePollUpdate(FeedPost $post, $pollData)
    {
        if ($post->poll) {
            $post->poll->update([
                'question' => $pollData['question'] ?? $post->poll->question,
                'allow_add_options' => $pollData['allow_add_options'] ?? $post->poll->allow_add_options,
                'allow_multiple_choice' => $pollData['allow_multiple_choice'] ?? $post->poll->allow_multiple_choice
            ]);

            if (isset($pollData['options']) && is_array($pollData['options'])) {
                $post->poll->options()->delete();
                foreach ($pollData['options'] as $optionText) {
                    if (!empty($optionText)) {
                        $post->poll->options()->create(['option_text' => $optionText]);
                    }
                }
            }
        } else {
            $poll = new Poll([
                'question' => $pollData['question'] ?? null,
                'allow_add_options' => $pollData['allow_add_options'] ?? false,
                'allow_multiple_choice' => $pollData['allow_multiple_choice'] ?? false
            ]);

            $post->poll()->save($poll);

            if (isset($pollData['options']) && is_array($pollData['options'])) {
                foreach ($pollData['options'] as $optionText) {
                    if (!empty($optionText)) {
                        $poll->options()->create(['option_text' => $optionText]);
                    }
                }
            }
        }
    }

    private function notifyDoctors(FeedPost $post)
    {
        $doctors = User::role(['Admin', 'Tester'])
            ->where('id', '!=', Auth::id())
            ->pluck('id');

        $user = Auth::user();
        $doctorName = $user->name . ' ' . $user->lname;

        $notifications = $doctors->map(function ($doctorId) use ($post, $doctorName, $user) {
            return [
                'doctor_id' => $doctorId,
                'type' => 'Post',
                'type_id' => $post->id,
                'content' => sprintf('Dr. %s added a new post', $doctorName),
                'type_doctor_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ];
        })->toArray();

        AppNotification::insert($notifications);

        $title = 'New Post was created 📣';
        $body = 'Dr. ' . ucfirst($user->name) . ' added a new post ';
        $tokens = FcmToken::whereIn('doctor_id', $doctors)
            ->pluck('token')
            ->toArray();

        $this->notificationController->sendPushNotification($title, $body, $tokens);

        return $notifications;
    }

    public function searchPosts($query)
    {
        try {
            if (!$query) {
                // Create an empty paginated response with the same structure
                $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                    [], // Empty data array
                    0,  // Total items
                    10, // Items per page
                    1,  // Current page
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

            $doctorId = auth()->id(); // Get the authenticated doctor's ID

            // Search posts by content
            $posts = FeedPost::where('content', 'LIKE', '%' . $query . '%')
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

    public function trending()
    {
        try {
            // Query the hashtags sorted by usage count, limit to top 10
            $trendingHashtags = Hashtag::orderBy('usage_count', 'desc')->paginate(10);

            return [
                'value' => true,
                'data' => $trendingHashtags,
                'message' => 'Trending hashtags retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching trending hashtags: " . $e->getMessage());
            return [
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while fetching trending hashtags'
            ];
        }
    }

    public function saveOrUnsavePost($postId, $status)
    {
        try {
            $doctor_id = auth()->id();

            // Check if the post exists
            $post = FeedPost::findOrFail($postId);

            // Find if the post is already saved
            $save = FeedSaveLike::where('feed_post_id', $postId)
                ->where('doctor_id', $doctor_id)
                ->first();

            // Handle Save
            if ($status === 'save') {
                if ($save) {
                    Log::warning("Post already saved PostID: $postId UserID: $doctor_id");
                    return [
                        'value' => false,
                        'message' => 'Post already saved'
                    ];
                }

                // Create a new save entry
                $newSave = FeedSaveLike::create([
                    'feed_post_id' => $postId,
                    'doctor_id' => $doctor_id,
                ]);

                Log::info("Post ID $postId saved by doctor " . $doctor_id);
                return [
                    'value' => true,
                    'data' => $newSave,
                    'message' => 'Post saved successfully'
                ];

            // Handle Unsave
            } elseif ($status === 'unsave') {
                if ($save) {
                    $save->delete();
                    Log::info("Post ID $postId unsaved by doctor " . $doctor_id);
                    return [
                        'value' => true,
                        'message' => 'Post unsaved successfully'
                    ];
                }

                Log::warning("Save not found for post ID $postId");
                return [
                    'value' => false,
                    'message' => 'Save not found'
                ];
            } else {
                Log::warning("Invalid status for post save/unsave: $status");
                return [
                    'value' => false,
                    'message' => 'Invalid status. Use "save" or "unsave".'
                ];
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $postId not found for save/unsave");
            return [
                'value' => false,
                'message' => 'Post not found'
            ];
        } catch (\Exception $e) {
            Log::error("Error processing save/unsave for post ID $postId: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while processing the request'
            ];
        }
    }

    public function addComment($postId, $validatedData)
    {
        try {
            $comment = FeedPostComment::create([
                'feed_post_id' => $postId,
                'doctor_id' => auth()->id(),
                'comment' => $validatedData['comment'],
                'parent_id' => $validatedData['parent_id'] ?? null,
            ]);

            Log::info("Comment added to post ID $postId by doctor " . auth()->id());

            $post = FeedPost::findOrFail($postId);
            $postOwner = $post->doctor;

            // Create notification for post owner if not the same user
            if ($postOwner->id !== auth()->id()) {
                AppNotification::create([
                    'doctor_id' => $postOwner->id,
                    'type' => 'PostComment',
                    'type_id' => $post->id,
                    'content' => sprintf('Dr. %s commented on your post', auth()->user()->name . ' ' . auth()->user()->lname),
                    'type_doctor_id' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info("Notification sent to post owner ID: " . $postOwner->id . " for post ID: " . $post->id);
            }

            // Notifying other doctors
            $doctors = User::role(['Admin', 'Tester'])
                ->where('id', '!=', auth()->id())
                ->pluck('id');

            $title = 'New Comment was added 📣';
            $body = 'Dr. ' . ucfirst(auth()->user()->name) . ' commented on your post ';
            $tokens = FcmToken::whereIn('doctor_id', $doctors)
                ->pluck('token')
                ->toArray();

            $this->notificationController->sendPushNotification($title, $body, $tokens);

            return [
                'value' => true,
                'data' => $comment,
                'message' => 'Comment added successfully'
            ];
        } catch (\Exception $e) {
            Log::error("Error adding comment to post ID $postId: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while adding the comment'
            ];
        }
    }

    public function deleteComment($commentId)
    {
        try {
            $comment = FeedPostComment::findOrFail($commentId);

            $user = auth()->user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Allow only the post owner or Admin/Tester
            if ($comment->doctor_id !== $user->id && !$isAdminOrTester) {
                Log::warning("Unauthorized deletion attempt by doctor " . auth()->id());
                return [
                    'value' => false,
                    'message' => 'Unauthorized action'
                ];
            }

            $comment->delete();
            Log::info("Comment ID $commentId deleted by doctor " . auth()->id());

            return [
                'value' => true,
                'message' => 'Comment deleted successfully'
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Comment ID $commentId not found for deletion");
            return [
                'value' => false,
                'message' => 'Comment not found'
            ];
        } catch (\Exception $e) {
            Log::error("Error deleting comment ID $commentId: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while deleting the comment'
            ];
        }
    }

    public function likeOrUnlikeComment($commentId, $status)
    {
        try {
            $doctor_id = auth()->id();

            // Find if the comment exists
            $comment = FeedPostComment::find($commentId);

            if (!$comment) {
                Log::error("No comment was found with ID: $commentId");
                return [
                    'value' => false,
                    'message' => 'No comment was found with ID: ' . $commentId
                ];
            }

            // Find if the comment is already liked
            $like = FeedPostCommentLike::where('post_comment_id', $commentId)
                ->where('doctor_id', $doctor_id)
                ->first();

            // Handle Like
            if ($status === 'like') {
                if ($like) {
                    Log::warning("Comment already liked CommentID: $commentId UserID: $doctor_id");
                    return [
                        'value' => false,
                        'message' => 'Comment already liked'
                    ];
                }

                // Create a new like entry
                $newLike = FeedPostCommentLike::create([
                    'post_comment_id' => $commentId,
                    'doctor_id' => $doctor_id,
                ]);

                Log::info("Comment ID $commentId liked by doctor $doctor_id");

                $comment = FeedPostComment::findOrFail($commentId);
                $commentOwner = $comment->doctor;

                // Create notification for comment owner if not the same user
                if ($commentOwner->id !== auth()->id()) {
                    AppNotification::create([
                        'doctor_id' => $commentOwner->id,
                        'type' => 'CommentLike',
                        'type_id' => $comment->feed_post_id,
                        'content' => sprintf('Dr. %s liked your comment', auth()->user()->name . ' ' . auth()->user()->lname),
                        'type_doctor_id' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    Log::info("Notification sent to comment owner ID: " . $commentOwner->id . " for comment ID: " . $comment->id);
                }

                // Notifying other doctors
                $doctors = User::role(['Admin', 'Tester'])
                    ->where('id', '!=', auth()->id())
                    ->pluck('id');

                $title = 'New Comment was liked 📣';
                $body = 'Dr. ' . ucfirst(auth()->user()->name) . ' liked your comment ';
                $tokens = FcmToken::whereIn('doctor_id', $doctors)
                    ->pluck('token')
                    ->toArray();

                $this->notificationController->sendPushNotification($title, $body, $tokens);

                return [
                    'value' => true,
                    'data' => $newLike,
                    'message' => 'Comment liked successfully'
                ];

            // Handle Unlike
            } elseif ($status === 'unlike') {
                if ($like) {
                    $like->delete();
                    Log::info("Comment ID $commentId unliked by doctor $doctor_id");
                    return [
                        'value' => true,
                        'message' => 'Comment unliked successfully'
                    ];
                }

                Log::warning("Like not found for comment ID $commentId");
                return [
                    'value' => false,
                    'message' => 'Like not found'
                ];
            } else {
                Log::warning("Invalid status for comment like/unlike: $status");
                return [
                    'value' => false,
                    'message' => 'Invalid status. Use "like" or "unlike".'
                ];
            }
        } catch (\Exception $e) {
            Log::error("Error processing like/unlike for comment ID $commentId: " . $e->getMessage());
            return [
                'value' => false,
                'message' => 'An error occurred while processing the request'
            ];
        }
    }
} 