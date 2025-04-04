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
} 