<?php

namespace App\Http\Controllers;

use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostCommentLike;
use App\Models\FeedPostLike;
use App\Models\FeedSaveLike;
use App\Models\Group;
use App\Models\Hashtag;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\User;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // If you have a separate PollOption model
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedPostController extends Controller
{
    protected $mainController;

    protected $notificationService;

    public function __construct(MainController $mainController, NotificationService $notificationService)
    {
        $this->mainController = $mainController;
        $this->notificationService = $notificationService;
    }

    // Helper function to extract hashtags from post content
    public function extractHashtags($content)
    {
        preg_match_all('/#([\p{L}\p{N}_]+)/u', $content, $matches);

        return $matches[1]; // Return an array of hashtags
    }

    // Fetch all feed posts with proper response structure and logging
    public function index()
    {
        try {
            $posts = FeedPost::with(['doctor', 'comments', 'likes', 'saves'])->paginate(10);
            if ($posts->isEmpty()) {
                Log::info('No feed posts found');

                return response()->json([
                    'value' => true,
                    'data' => [],
                    'message' => __('api.no_feed_posts_found'),
                ]);
            }
            Log::info('Fetched all feed posts');

            return response()->json([
                'value' => true,
                'data' => $posts,
                'message' => __('api.feed_posts_retrieved_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching feed posts: '.$e->getMessage());

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.error_retrieving_feed_posts'),
            ], 500);
        }
    }

    // Fetch feed posts with likes, comments, and saved status for the authenticated doctor
    public function getFeedPosts()
    {
        try {
            $doctorId = auth()->id();

            // Use eager loading with specific columns to reduce data transfer
            $feedPosts = FeedPost::select([
                'id', 'doctor_id', 'content', 'media_type', 'media_path',
                'visibility', 'group_id', 'created_at', 'updated_at',
            ])
                ->with([
                    'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                    'hashtags:id,tag,usage_count',
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
                    },
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
                    },
                ])
                ->where('group_id', null)
                ->latest('created_at')
                ->paginate(10);

            // Transform posts using collection methods for better performance
            $feedPosts->getCollection()->transform(function ($post) {
                $post->isSaved = $post->saves->isNotEmpty();
                $post->isLiked = $post->likes->isNotEmpty();
                $post->doctor_id = (int) $post->doctor_id;
                $post->comments_count = (int) $post->comments_count;
                $post->likes_count = (int) $post->likes_count;

                if ($post->poll) {
                    // Ensure integer types for specific poll attributes
                    $post->poll->feed_post_id = (int) $post->poll->feed_post_id;

                    $post->poll->options = $post->poll->options
                        ->map(function ($option) {
                            $option->is_voted = $option->votes->isNotEmpty();
                            // Explicitly cast required fields to integers
                            if (isset($option->poll_id)) {
                                $option->poll_id = (int) $option->poll_id;
                            }
                            if (isset($option->votes_count)) {
                                $option->votes_count = (int) $option->votes_count;
                            }
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

            return response()->json([
                'value' => true,
                'data' => $feedPosts,
                'message' => __('api.feed_posts_retrieved_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching feed posts for doctor ID $doctorId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.error_retrieving_feed_posts'),
            ], 500);
        }
    }

    public function getDoctorPosts($doctorId)
    {
        try {
            //$doctorId = auth()->id(); // Get the authenticated doctor's ID

            // Fetch posts with necessary relationships and counts
            $feedPosts = FeedPost::with([
                'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'hashtags:id,tag,usage_count',
                'poll' => function ($query) {
                    $query->select('id', 'feed_post_id', 'question', 'allow_add_options', 'allow_multiple_choice');
                },
                'poll.options' => function ($query) use ($doctorId) {
                    $query->select('id', 'poll_id', 'option_text')
                        ->withCount('votes') // Count votes per option
                        ->with(['votes' => function ($voteQuery) use ($doctorId) {
                            $voteQuery->select('id', 'poll_option_id', 'doctor_id')
                                ->where('doctor_id', $doctorId); // Check if user voted
                        }]);
                },
            ])
                ->withCount(['likes', 'comments'])  // Count likes and comments
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is saved by the doctor
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is liked by the doctor
                    },
                ])
                ->where('doctor_id', $doctorId)
                ->where('group_id', null) // Fetch posts that are not in a group
                ->latest('created_at') // Sort by created_at in descending order
                ->paginate(10); // Paginate 10 posts per page

            // Add 'is_saved' and 'is_liked' fields to each post
            $feedPosts->getCollection()->transform(function ($post) {
                // Add 'is_saved' field (true if the doctor saved the post)
                $post->isSaved = $post->saves->isNotEmpty();

                // Add 'is_liked' field (true if the doctor liked the post)
                $post->isLiked = $post->likes->isNotEmpty();

                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) {
                        $option->is_voted = $option->votes->isNotEmpty(); // If user has voted for this option
                        unset($option->votes); // Remove unnecessary vote data

                        return $option;
                    })->sortByDesc('votes_count')->values();
                }

                // Remove unnecessary data to clean up the response
                unset($post->saves, $post->likes);

                return $post;
            });

            Log::info("Feed posts fetched for doctor ID $doctorId");

            return response()->json([
                'value' => true,
                'data' => $feedPosts,
                'message' => __('api.feed_posts_retrieved_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching Doctor posts for doctor ID $doctorId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.error_retrieving_feed_posts'),
            ], 500);
        }
    }

    public function getDoctorSavedPosts($doctorId = null)
    {
        // Ensure doctorId is not null or undefined
        if (! $doctorId) {
            Log::error('Doctor ID is missing or undefined.');

            return response()->json([
                'value' => false,
                'message' => __('validation.required', ['attribute' => 'Doctor ID']),
            ], 400);
        }

        try {
            Log::info('Fetching saved posts for doctor ID: '.$doctorId);

            // Fetch only saved posts for the given doctor ID
            $feedPosts = FeedPost::whereHas('saves', function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId);
            })
                ->with([
                    'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                    'hashtags:id,tag,usage_count',
                    'poll' => function ($query) {
                        $query->select('id', 'feed_post_id', 'question', 'allow_add_options', 'allow_multiple_choice');
                    },
                    'poll.options' => function ($query) use ($doctorId) {
                        $query->select('id', 'poll_id', 'option_text')
                            ->withCount('votes') // Count votes per option
                            ->with(['votes' => function ($voteQuery) use ($doctorId) {
                                $voteQuery->select('id', 'poll_option_id', 'doctor_id')
                                    ->where('doctor_id', $doctorId); // Check if user voted
                            }]);
                    },
                ])
                ->withCount(['likes', 'comments']) // Count likes and comments
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        Log::info('Inside saves closure - Doctor ID: '.$doctorId);
                        $query->where('doctor_id', $doctorId);
                    },
                    'likes' => function ($query) use ($doctorId) {
                        Log::info('Inside likes closure - Doctor ID: '.$doctorId);
                        $query->where('doctor_id', $doctorId);
                    },
                ])
                ->where('group_id', null) // Fetch posts that are not in a group
                ->orderByDesc(
                    FeedSaveLike::select('created_at')
                        ->whereColumn('feed_post_id', 'feed_posts.id')
                        ->where('doctor_id', $doctorId)
                        ->latest()
                        ->limit(1)
                ) // Order by latest save date
                ->paginate(10); // Paginate 10 posts per page

            // Debug: Check if results exist
            if ($feedPosts->isEmpty()) {
                Log::warning('No saved posts found for doctor ID: '.$doctorId);
            }

            // Transform posts to add `isSaved` and `isLiked` flags
            $feedPosts->getCollection()->transform(function ($post) {
                $post->isSaved = true; // Since we're only fetching saved posts, this is always true
                $post->isLiked = $post->likes->isNotEmpty();

                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) {
                        $option->is_voted = $option->votes->isNotEmpty(); // If user has voted for this option
                        unset($option->votes); // Remove unnecessary vote data

                        return $option;
                    })->sortByDesc('votes_count')->values();
                }
                // Remove unnecessary relationship data
                unset($post->saves, $post->likes);

                return $post;
            });

            Log::info('Saved posts successfully fetched for doctor ID: '.$doctorId);

            return response()->json([
                'value' => true,
                'data' => $feedPosts,
                'message' => __('api.feed_posts_retrieved_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching saved posts for doctor ID $doctorId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.error_retrieving_feed_posts'),
            ], 500);
        }
    }

    // Get post by ID with sorted comments, likes, and saved status
    public function getPostById($id)
    {
        try {
            $doctorId = auth()->id();

            $post = FeedPost::with([
                'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'hashtags:id,tag,usage_count',
                'comments' => function ($query) {
                    $query->orderBy('created_at', 'desc')->paginate(10);
                },
                'comments.doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'poll' => function ($query) {
                    $query->select('id', 'feed_post_id', 'question', 'allow_add_options', 'allow_multiple_choice');
                },
                'poll.options' => function ($query) use ($doctorId) {
                    $query->select('id', 'poll_id', 'option_text')
                        ->withCount('votes') // Load vote count for each option
                        ->with(['votes' => function ($voteQuery) use ($doctorId) {
                            $voteQuery->select('id', 'poll_option_id', 'doctor_id')
                                ->where('doctor_id', $doctorId); // Check if the user voted
                        }]);
                },
            ])->withCount(['likes', 'comments'])
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is saved by the doctor
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is liked by the doctor
                    },
                ])
                ->findOrFail($id);

            $post->isSaved = $post->saves->isNotEmpty();
            $post->isLiked = $post->likes->isNotEmpty();

            // Sort poll options by vote count and add `is_voted`
            if ($post->poll) {
                $post->poll->options = $post->poll->options->map(function ($option) {
                    $option->is_voted = $option->votes->isNotEmpty(); // Check if the user voted
                    unset($option->votes); // Remove unnecessary votes data

                    return $option;
                })->sortByDesc('votes_count')->values();
            }

            unset($post->saves, $post->likes);

            Log::info("Post ID $id retrieved successfully for doctor ID $doctorId");

            return response()->json([
                'value' => true,
                'data' => $post,
                'message' => __('api.feed_posts_retrieved_successfully'),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $id not found");

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.post_not_found'),
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error fetching post ID $id: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.error_retrieving_feed_posts'),
            ], 500);
        }
    }

    // Get likes for a post
    public function getPostLikes($postId)
    {
        try {
            $likes = FeedPostLike::where('feed_post_id', $postId)
                ->with(['doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired'])
                ->paginate(10);

            if ($likes->isEmpty()) {
                Log::info("No likes found for post ID $postId");

                return response()->json([
                    'value' => false,
                    'data' => [],
                    'message' => __('api.no_feed_posts_found'),
                ]);
            }

            // Transform the likes collection to return only the doctor data
            $doctorData = $likes->getCollection()->map(function ($like) {
                return $like->doctor;  // Returning only the doctor object
            });

            // Paginate the doctor data
            $paginatedDoctorData = new \Illuminate\Pagination\LengthAwarePaginator(
                $doctorData,
                $likes->total(),
                $likes->perPage(),
                $likes->currentPage(),
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );

            Log::info("Likes retrieved for post ID $postId");

            return response()->json([
                'value' => true,
                'data' => $paginatedDoctorData,
                'message' => __('api.feed_posts_retrieved_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching likes for post ID $postId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.error_retrieving_feed_posts'),
            ], 500);
        }
    }

    // Get comments for a post
    public function getPostComments($postId)
    {
        try {
            $doctorId = auth()->id(); // Authenticated user ID
            $postOwnerId = FeedPost::find($postId)->doctor_id; // Assuming `doctor_id` is the owner ID in FeedPost
            $perPage = 10; // Number of comments per page
            $page = request()->get('page', 1); // Current page

            // Fetch all comments with the necessary relations
            $comments = FeedPostComment::where('feed_post_id', $postId)
                ->whereNull('parent_id') // Get only top-level comments
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
                                        },
                                    ]);
                            },
                        ])->withCount(['likes as likes_count', 'replies as replies_count']);
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId);
                    },
                ])
                ->withCount(['likes as likes_count', 'replies as replies_count'])
                ->get(); // Fetch all comments to apply custom sorting

            // Reorder comments: Post owner's comments first, followed by auth user's comments
            $comments = $comments->sortByDesc(function ($comment) use ($doctorId, $postOwnerId) {
                return [
                    $comment->doctor_id === $postOwnerId ? 2 : 0, // Post owner's comments first
                    $comment->doctor_id === $doctorId ? 1 : 0,     // Auth user's comments second
                ];
            })->values(); // Reset the keys after sorting

            // Manually paginate the sorted comments
            $paginatedComments = $comments->forPage($page, $perPage);

            // Add 'isLiked' property for comments and replies
            foreach ($paginatedComments as $comment) {
                $comment->isLiked = $comment->likes->isNotEmpty();

                foreach ($comment->replies as $reply) {
                    $reply->isLiked = $reply->likes->isNotEmpty();

                    foreach ($reply->replies as $nestedReply) {
                        $nestedReply->isLiked = $nestedReply->likes->isNotEmpty();
                    }
                }

                // Optionally remove the 'likes' relation
                unset($comment->likes);
                foreach ($comment->replies as $reply) {
                    unset($reply->likes);
                    foreach ($reply->replies as $nestedReply) {
                        unset($nestedReply->likes);
                    }
                }
            }

            // Create a new paginator instance with the transformed data
            $paginatedResponse = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedComments->values(), // Ensure we're passing a collection with sequential keys
                $comments->count(), // Total items count
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );

            Log::info("Comments retrieved for post ID $postId");

            return response()->json([
                'value' => true,
                'data' => $paginatedResponse,
                'message' => __('api.post_comments_retrieved_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching comments for post ID $postId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.error_retrieving_post_comments'),
            ], 500);
        }
    }

    // Create a new post
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate request with more specific rules
            $validatedData = $request->validate([
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
                'poll.options.*' => 'nullable|string|max:255|distinct',
            ]);

            // Handle group validation with proper error handling
            try {
                $this->handleGroupValidation($validatedData);
            } catch (\Exception $e) {
                throw new \Illuminate\Validation\ValidationException(
                    validator(['group_id' => [$e->getMessage()]], ['group_id' => 'required'])
                );
            }

            // Handle media upload with better error handling
            $mediaPaths = [];
            if ($request->hasFile('media_path')) {
                try {
                    $mediaPaths = $this->uploadMultipleImages($request);
                } catch (\Exception $e) {
                    throw new \Illuminate\Validation\ValidationException(
                        validator(['media_path' => ['Failed to upload media: '.$e->getMessage()]], ['media_path' => 'required'])
                    );
                }
            }

            // Create Feed Post with proper error handling
            try {
                $post = $this->createFeedPost($validatedData, $mediaPaths);
            } catch (\Exception $e) {
                throw new \Exception('Failed to create post: '.$e->getMessage());
            }

            // Attach hashtags with error handling
            try {
                $this->attachHashtags($post, $request->input('content'));
            } catch (\Exception $e) {
                Log::error('Hashtag attachment failed: '.$e->getMessage());
                // Continue execution as hashtag failure shouldn't prevent post creation
            }

            // Handle poll creation with validation
            if (isset($validatedData['poll']) && isset($validatedData['poll']['options'])) {
                try {
                    $poll = new Poll([
                        'question' => $validatedData['poll']['question'] ?? null,
                        'allow_add_options' => $validatedData['poll']['allow_add_options'] ?? false,
                        'allow_multiple_choice' => $validatedData['poll']['allow_multiple_choice'] ?? false,
                    ]);

                    $post->poll()->save($poll);

                    foreach ($validatedData['poll']['options'] as $optionText) {
                        if (! empty($optionText)) {
                            $poll->options()->create(['option_text' => $optionText]);
                        }
                    }
                } catch (\Exception $e) {
                    throw new \Exception('Failed to create poll: '.$e->getMessage());
                }
            }

            DB::commit();

            // Create notifications with error handling
            try {
                $notifications = $this->notifyDoctors($post);
            } catch (\Exception $e) {
                Log::error('Notification creation failed: '.$e->getMessage());
                // Continue execution as notification failure shouldn't prevent post creation
            }

            return response()->json([
                'value' => true,
                'data' => $post->load('poll.options'),
                'message' => __('api.post_created_successfully'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'value' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating post: '.$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => __('api.error_creating_post', ['message' => $e->getMessage()]),
            ], 500);
        }
    }

    // Update a post
    public function update(Request $request, $id)
    {
        DB::beginTransaction(); // Start a transaction

        try {
            // Retrieve the post by ID
            $post = FeedPost::findOrFail($id);

            // Get the authenticated user
            $user = Auth::user();

            // Validate the incoming request data
            $validatedData = $request->validate($this->validationRules());

            // Handle group validation and permissions
            $this->handleGroupValidation($validatedData);

            // Initialize media variables with existing values
            $mediaType = $post->media_type;
            $existingMediaPaths = $post->media_path ?? [];

            // If media_type is sent in the request
            if ($request->has('media_type')) {
                $mediaType = $request->media_type;

                // If media_type is 'text', remove media
                if ($mediaType === 'text') {
                    $mediaType = null;
                    $existingMediaPaths = [];
                }
            }

            // Get existing media paths from request
            $existingMediaFromRequest = is_array($request->input('existing_media_path'))
                ? $request->input('existing_media_path')
                : [];

            // Get current media paths from database
            $currentMediaPaths = $post->media_path ?? [];

            // Remove any media paths from current that are not in the request
            $finalMediaPaths = array_intersect($currentMediaPaths, $existingMediaFromRequest);

            // Upload new images (if any)
            $newMediaPaths = [];
            if ($request->hasFile('media_path')) {
                $newMediaPaths = $this->uploadMultipleImages($request->file('media_path'));
            }

            // Merge existing and new media paths
            $finalMediaPaths = array_merge($finalMediaPaths, $newMediaPaths);

            // Update the post with validated data
            $post->update([
                'content' => $validatedData['content'],
                'media_type' => $mediaType,
                'media_path' => $finalMediaPaths,
                'visibility' => $validatedData['visibility'] ?? 'Public',
                'group_id' => $validatedData['group_id'] ?? null,
            ]);

            // Handle hashtag cleanup and update only if content changed
            if ($post->wasChanged('content')) {
                DB::beginTransaction();
                try {
                    // Get old hashtags before detaching
                    $oldHashtags = $post->hashtags;

                    // Detach old hashtags
                    $post->hashtags()->detach();

                    // Decrement usage count for old hashtags
                    foreach ($oldHashtags as $hashtag) {
                        $hashtag->decrement('usage_count', 1);

                        // If usage count reaches 0, delete the hashtag
                        if ($hashtag->usage_count <= 0) {
                            $hashtag->delete();
                        }
                    }

                    // Attach new hashtags from updated content
                    $this->attachHashtags($post, $request->input('content'));

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            // Handle poll update or creation
            if (isset($validatedData['poll']) && isset($validatedData['poll']['options'])) {
                // Check if post already has a poll
                if ($post->poll) {
                    // Update existing poll
                    $post->poll->update([
                        'question' => $validatedData['poll']['question'] ?? $post->poll->question,
                        'allow_add_options' => $validatedData['poll']['allow_add_options'] ?? $post->poll->allow_add_options,
                        'allow_multiple_choice' => $validatedData['poll']['allow_multiple_choice'] ?? $post->poll->allow_multiple_choice,
                    ]);

                    // Handle options update
                    if (isset($validatedData['poll']['options']) && is_array($validatedData['poll']['options'])) {
                        // Delete existing options
                        $post->poll->options()->delete();

                        // Create new options
                        foreach ($validatedData['poll']['options'] as $optionText) {
                            if (! empty($optionText)) {
                                $post->poll->options()->create(['option_text' => $optionText]);
                            }
                        }
                    }
                } else {
                    // Create new poll
                    $poll = new Poll([
                        'question' => $validatedData['poll']['question'] ?? null,
                        'allow_add_options' => $validatedData['poll']['allow_add_options'] ?? false,
                        'allow_multiple_choice' => $validatedData['poll']['allow_multiple_choice'] ?? false,
                    ]);

                    // Associate poll with the post
                    $post->poll()->save($poll);

                    // Create options if they exist
                    if (isset($validatedData['poll']['options']) && is_array($validatedData['poll']['options'])) {
                        foreach ($validatedData['poll']['options'] as $optionText) {
                            if (! empty($optionText)) {
                                $poll->options()->create(['option_text' => $optionText]);
                            }
                        }
                    }
                }
            }

            // Commit the transaction
            DB::commit();

            // Log the successful update
            Log::info("Post ID $id updated by doctor ".$user->id);

            // Return a success response with updated post and poll data
            return response()->json([
                'value' => true,
                'data' => $post->load('poll.options'),
                'message' => __('api.post_updated_successfully'),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::warning("Post ID $id not found for update");

            return response()->json([
                'value' => false,
                'message' => __('api.post_not_found'),
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating post ID $id: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => __('api.error_updating_post', ['message' => $e->getMessage()]),
            ], 500);
        }
    }

    private function validationRules()
    {
        return [
            'content' => 'nullable|string',
            'media_type' => 'nullable|string|in:image,video,text',
            'media_path' => 'nullable|array|max:10', // Allow up to 10 files
            'media_path.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mkv|max:20480', // Validate each file
            'visibility' => 'nullable|string|in:Public,Friends,Only Me',
            'group_id' => 'nullable|exists:groups,id',
            'poll' => 'nullable|array',
            'poll.question' => 'nullable|string|max:255',
            'poll.allow_add_options' => 'nullable|boolean',
            'poll.allow_multiple_choice' => 'nullable|boolean',
            'poll.options' => 'nullable|array',
            'poll.options.*' => 'nullable|string|max:255',
        ];
    }

    private function createPollForPost(FeedPost $post, array $data)
    {
        $poll = $post->poll()->create([
            'question' => $data['poll']['question'],
            'allow_add_options' => $data['poll']['allow_add_options'] ?? false,
            'allow_multiple_choice' => $data['poll']['allow_multiple_choice'] ?? false,
        ]);

        foreach ($validatedData['poll']['options'] as $optionText) {
            $poll->options()->create(['option_text' => $optionText]);
        }
    }

    private function handleGroupValidation(array &$validatedData)
    {
        if (! empty($validatedData['group_id'])) {
            $group = Group::with('doctors')->find($validatedData['group_id']);

            if (! $group) {
                throw new \Exception(__('api.group_not_found'), 404);
            }

            if ($group->privacy === 'private' && ! $group->doctors->contains(Auth::id())) {
                throw new \Exception('You cannot post in this private group', 403);
            }

            $validatedData['group_name'] = $group->name;
        }
    }

    private function handleMediaUpload(Request $request, $mediaType)
    {
        if ($request->hasFile('media_path')) {
            $mediaPaths = [];
            foreach ($request->file('media_path') as $media) {
                $path = ($mediaType === 'image') ? 'media_images' : 'media_videos';
                $uploadResponse = $this->mainController->uploadImageAndVideo($media, $path);

                if ($uploadResponse->getData()->value) {
                    if (! in_array($uploadResponse->getData()->image, $mediaPaths)) {
                        $mediaPaths[] = $uploadResponse->getData()->image;
                        Log::info('Media Paths: ', $mediaPaths);
                    }
                } else {
                    throw new \Exception(__('api.media_upload_failed'), 500);
                }
            }

            return $mediaPaths; // Return array of URLs
        }

        return null;
    }

    private function uploadMultipleImages($requestOrFiles)
    {
        $uploadedImages = [];
        $files = [];

        // Handle both Request object and array of files
        if ($requestOrFiles instanceof \Illuminate\Http\Request) {
            if (! $requestOrFiles->hasFile('media_path')) {
                return [];
            }
            $files = $requestOrFiles->file('media_path');
        } else {
            $files = $requestOrFiles;
        }

        if (! empty($files)) {
            foreach ($files as $media) {
                // Get the authenticated user's name
                $name = auth()->user()->name;

                // Generate a unique timestamp with microtime
                $timestamp = time().'_'.uniqid();

                // Create a unique file name using the user's name and timestamp
                $fileName = "{$name}_media_{$timestamp}.".$media->getClientOriginalExtension();

                // Store the media in the specified path (image or video directory)
                $storedPath = $media->storeAs('media_images', $fileName, 'public');

                // Construct the full URL for the uploaded media
                $mediaUrl = config('app.url').'/storage/'.$storedPath;

                // Get the public URL of the uploaded file
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
            'media_path' => $mediaPaths, // Store as JSON array
            'visibility' => $validatedData['visibility'] ?? 'Public',
            'group_id' => $validatedData['group_id'] ?? null,
        ]);

        if (! $post) {
            throw new \Exception(__('api.post_creation_failed'));
        }

        return $post;
    }

    private function attachHashtags(FeedPost $post, $content)
    {
        try {
            // Extract hashtags from content
            preg_match_all('/#([\p{L}\p{N}_]+)/u', $content, $matches);
            $hashtags = $matches[1];

            // Remove duplicate hashtags from the same post content
            $hashtags = array_unique($hashtags);

            if (empty($hashtags)) {
                return;
            }

            // Get currently attached hashtag tags for this post in one query
            $attachedHashtagTags = $post->hashtags()->pluck('tag')->toArray();

            // Filter out hashtags that are already attached to this post
            $newHashtags = array_diff($hashtags, $attachedHashtagTags);

            if (empty($newHashtags)) {
                return;
            }

            // Get existing hashtags in one query
            $existingHashtags = Hashtag::whereIn('tag', $newHashtags)->get()->keyBy('tag');

            $hashtagsToCreate = [];
            $hashtagsToUpdate = [];
            $hashtagsToAttach = [];

            foreach ($newHashtags as $hashtagName) {
                if (isset($existingHashtags[$hashtagName])) {
                    // Hashtag exists, prepare for update and attach
                    $hashtag = $existingHashtags[$hashtagName];
                    $hashtagsToUpdate[] = $hashtag->id;
                    $hashtagsToAttach[] = $hashtag->id;
                } else {
                    // Hashtag doesn't exist, prepare for creation
                    $hashtagsToCreate[] = [
                        'tag' => $hashtagName,
                        'usage_count' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Create new hashtags one by one using firstOrCreate to handle race conditions
            foreach ($hashtagsToCreate as $hashtagData) {
                $hashtag = Hashtag::firstOrCreate(
                    ['tag' => $hashtagData['tag']],
                    ['usage_count' => 1]
                );
                $hashtagsToAttach[] = $hashtag->id;

                // If hashtag was just created (not found), it already has usage_count = 1
                // If it already existed, increment its usage count
                if ($hashtag->wasRecentlyCreated === false) {
                    $hashtagsToUpdate[] = $hashtag->id;
                }
            }

            // Bulk increment usage count for existing hashtags
            if (! empty($hashtagsToUpdate)) {
                Hashtag::whereIn('id', $hashtagsToUpdate)->increment('usage_count');
            }

            // Bulk attach hashtags to post
            if (! empty($hashtagsToAttach)) {
                $post->hashtags()->attach($hashtagsToAttach);
            }

            Log::info('Hashtags attached successfully', [
                'post_id' => $post->id,
                'hashtags' => $hashtags,
                'new_hashtags_count' => count($newHashtags),
                'created_count' => count($hashtagsToCreate),
                'updated_count' => count($hashtagsToUpdate),
            ]);
        } catch (\Exception $e) {
            Log::error('Error attaching hashtags', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function notifyDoctors(FeedPost $post)
    {
        $user = Auth::user();
        $doctorName = $user->name.' '.$user->lname;

        // Determine who to notify based on post type
        if ($post->group_id) {
            // For group posts, notify only group members (excluding post creator)
            $doctors = DB::table('group_user')
                ->join('users', 'group_user.doctor_id', '=', 'users.id')
                ->where('group_user.group_id', $post->group_id)
                ->where('group_user.status', 'joined')
                ->where('group_user.doctor_id', '!=', Auth::id())
                ->pluck('users.id');

            $title = 'New Group Post 👥';
            $body = 'Dr. '.ucfirst($user->name).' posted in your group';
        } else {
            // For public posts, notify all verified doctors (excluding post creator)
            $doctors = User::where('id', '!=', Auth::id())
                ->pluck('id');

            $title = 'New Post was created 📣';
            $body = 'Dr. '.ucfirst($user->name).' added a new post';
        }

        if ($doctors->isEmpty()) {
            Log::info('No users to notify for post ID: '.$post->id);

            return [];
        }

        // Create localized notifications for each doctor
        foreach ($doctors as $doctorId) {
            AppNotification::createLocalized([
                'doctor_id' => $doctorId,
                'type' => $post->group_id ? 'GroupPost' : 'Post',
                'type_id' => $post->id,
                'localization_key' => $post->group_id
                    ? 'api.notification_group_post_created'
                    : 'api.notification_post_created',
                'localization_params' => [
                    'name' => $doctorName,
                ],
                'type_doctor_id' => $user->id,
            ]);
        }

        $tokens = FcmToken::whereIn('doctor_id', $doctors)
            ->pluck('token')
            ->toArray();

        $this->notificationService->sendPushNotification($title, $body, $tokens);

        Log::info('Notifications inserted successfully for post ID: '.$post->id, [
            'post_type' => $post->group_id ? 'group' : 'public',
            'recipients_count' => count($doctors),
        ]);

        return $notifications;
    }

    // Delete a post
    public function destroy($id)
    {
        try {
            $post = FeedPost::with('hashtags')->findOrFail($id);

            $user = Auth::user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Allow only the post owner or Admin/Tester
            if ($post->doctor_id !== $user->id && ! $isAdminOrTester) {
                Log::warning('Unauthorized deletion attempt by doctor '.Auth::id());

                return response()->json([
                    'value' => false,
                    'message' => __('api.unauthorized'),
                ], 403);
            }

            // Start transaction for hashtag cleanup
            DB::beginTransaction();
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

                // Delete the post
                $post->delete();

                // Remove the associated AppNotification
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
                Log::info("Post ID $id and its hashtags deleted by doctor ".Auth::id());

                return response()->json([
                    'value' => true,
                    'message' => __('api.post_deleted_successfully'),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $id not found for deletion");

            return response()->json([
                'value' => false,
                'message' => __('api.post_not_found'),
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting post ID $id: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => __('api.error_deleting_post'),
            ], 500);
        }
    }

    public function likeOrUnlikePost(Request $request, $postId)
    {
        try {
            // Validate input
            $validatedData = $request->validate([
                'status' => 'required|string|in:like,unlike',
            ]);

            $doctor_id = Auth::id();
            $status = $validatedData['status'];

            // Check if the post exists and is accessible
            $post = FeedPost::select(['id', 'doctor_id'])
                ->where('id', $postId)
                ->where(function ($query) {
                    $query->where('visibility', 'Public')
                        ->orWhere('doctor_id', Auth::id());
                })
                ->firstOrFail();

            // Find if the like already exists
            $like = FeedPostLike::where('feed_post_id', $postId)
                ->where('doctor_id', $doctor_id)
                ->first();

            // Handle Like
            if ($status === 'like') {
                if ($like) {
                    Log::warning("Post already liked PostID: $postId UserID: $doctor_id");

                    return response()->json([
                        'value' => false,
                        'message' => __('api.post_liked_successfully'),
                    ], 400);
                }

                DB::beginTransaction();
                try {
                    // Create a new like entry
                    $newLike = FeedPostLike::create([
                        'feed_post_id' => $postId,
                        'doctor_id' => $doctor_id,
                    ]);

                    $postOwner = User::select(['id', 'name', 'lname'])
                        ->find($post->doctor_id);

                    // Create notification for post owner if not the same user
                    if ($postOwner->id !== Auth::id()) {
                        AppNotification::createLocalized([
                            'doctor_id' => $postOwner->id,
                            'type' => 'PostLike',
                            'type_id' => $post->id,
                            'localization_key' => 'api.notification_post_liked',
                            'localization_params' => ['name' => Auth::user()->name.' '.Auth::user()->lname],
                            'type_doctor_id' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Get FCM tokens for push notification
                        $tokens = FcmToken::where('doctor_id', $postOwner->id)
                            ->pluck('token')
                            ->toArray();

                        if (! empty($tokens)) {
                            $this->notificationService->sendPushNotification(
                                __('api.post_was_liked'),
                                __('api.doctor_liked_post', ['name' => ucfirst(Auth::user()->name)]),
                                $tokens
                            );
                        }
                    }

                    DB::commit();
                    Log::info("Post ID $postId liked by doctor ".$doctor_id);

                    return response()->json([
                        'value' => true,
                        'data' => $newLike,
                        'message' => __('api.post_liked_successfully'),
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }

                // Handle Unlike
            } elseif ($status === 'unlike') {
                if ($like) {
                    $like->delete();
                    Log::info("Post ID $postId unliked by doctor ".$doctor_id);

                    return response()->json([
                        'value' => true,
                        'message' => __('api.post_unliked_successfully'),
                    ]);
                }

                Log::warning("Like not found for post ID $postId");

                return response()->json([
                    'value' => false,
                    'message' => __('api.resource_not_found'),
                ], 404);
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $postId not found for like/unlike");

            return response()->json([
                'value' => false,
                'message' => __('api.post_not_found'),
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Invalid input for like/unlike: '.json_encode($e->errors()));

            return response()->json([
                'value' => false,
                'message' => __('api.validation_failed'),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error processing like/unlike for post ID $postId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => __('api.server_error'),
            ], 500);
        }
    }

    public function saveOrUnsavePost(Request $request, $postId)
    {
        try {
            $doctor_id = Auth::id();
            $status = $request->input('status'); // 'save' or 'unsave'

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

                    return response()->json([
                        'value' => false,
                        'message' => __('api.post_saved_successfully'),
                    ], 400);
                }

                // Create a new save entry
                $newSave = FeedSaveLike::create([
                    'feed_post_id' => $postId,
                    'doctor_id' => $doctor_id,
                ]);

                Log::info("Post ID $postId saved by doctor ".$doctor_id);

                return response()->json([
                    'value' => true,
                    'data' => $newSave,
                    'message' => __('api.post_saved_successfully'),
                ]);

                // Handle Unsave
            } elseif ($status === 'unsave') {
                if ($save) {
                    $save->delete();
                    Log::info("Post ID $postId unsaved by doctor ".$doctor_id);

                    return response()->json([
                        'value' => true,
                        'message' => __('api.post_unsaved_successfully'),
                    ]);
                }

                Log::warning("Save not found for post ID $postId");

                return response()->json([
                    'value' => false,
                    'message' => __('api.resource_not_found'),
                ], 404);
            } else {
                Log::warning("Invalid status for post save/unsave: $status");

                return response()->json([
                    'value' => false,
                    'message' => __('api.validation_failed'),
                ], 400);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $postId not found for save/unsave");

            return response()->json([
                'value' => false,
                'message' => __('api.post_not_found'),
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error processing save/unsave for post ID $postId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => __('api.server_error'),
            ], 500);
        }
    }

    // Add a comment to a post
    public function addComment(Request $request, $postId)
    {
        try {
            $validatedData = $request->validate([
                'comment' => 'required|string|max:500',
                'parent_id' => 'nullable|exists:feed_post_comments,id',
            ]);

            $comment = FeedPostComment::create([
                'feed_post_id' => $postId,
                'doctor_id' => Auth::id(),
                'comment' => $validatedData['comment'],
                'parent_id' => $validatedData['parent_id'] ?? null,
            ]);

            Log::info("Comment added to post ID $postId by doctor ".Auth::id());

            $post = FeedPost::findOrFail($postId);
            $postOwner = $post->doctor;

            if ($postOwner->id !== Auth::id()) {
                AppNotification::createLocalized([
                    'doctor_id' => $postOwner->id,
                    'type' => 'PostComment',
                    'type_id' => $post->id,
                    'localization_key' => 'api.notification_post_commented',
                    'localization_params' => ['name' => Auth::user()->name.' '.Auth::user()->lname],
                    'type_doctor_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('Notification sent to post owner ID: '.$postOwner->id.' for post ID: '.$post->id);

                // Get FCM tokens for push notification
                $tokens = FcmToken::where('doctor_id', $postOwner->id)
                    ->pluck('token')
                    ->toArray();

                if (! empty($tokens)) {
                    $this->notificationService->sendPushNotification(
                        __('api.new_comment_added'),
                        __('api.doctor_commented_on_post', ['name' => ucfirst(Auth::user()->name)]),
                        $tokens
                    );
                }
            }

            return response()->json([
                'value' => true,
                'data' => $comment,
                'message' => __('api.comment_added_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error adding comment to post ID $postId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => __('api.error_adding_comment'),
            ], 500);
        }
    }

    // Delete a comment
    public function deleteComment($commentId)
    {
        try {
            $comment = FeedPostComment::findOrFail($commentId);

            $user = Auth::user();
            $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

            // Allow only the post owner or Admin/Tester
            if ($comment->doctor_id !== $user->id && ! $isAdminOrTester) {
                Log::warning('Unauthorized deletion attempt by doctor '.Auth::id());

                return response()->json([
                    'value' => false,
                    'message' => __('api.unauthorized'),
                ], 403);
            }

            $comment->delete();
            Log::info("Comment ID $commentId deleted by doctor ".Auth::id());

            return response()->json([
                'value' => true,
                'message' => __('api.comment_added_successfully'),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Comment ID $commentId not found for deletion");

            return response()->json([
                'value' => false,
                'message' => __('api.resource_not_found'),
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting comment ID $commentId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => __('api.error_adding_comment'),
            ], 500);
        }
    }

    public function likeOrUnlikeComment(Request $request, $commentId)
    {
        try {
            $doctor_id = Auth::id();
            $status = $request->input('status'); // 'like' or 'unlike'

            // Find if the comment exists
            $comment = FeedPostComment::find($commentId);

            if (! $comment) {
                Log::error("No comment was found with ID: $commentId");

                return response()->json([
                    'value' => false,
                    'message' => __('api.resource_not_found'),
                ], 404);
            }

            // Find if the comment is already liked
            $like = FeedPostCommentLike::where('post_comment_id', $commentId)
                ->where('doctor_id', $doctor_id)
                ->first();

            // Handle Like
            if ($status === 'like') {
                if ($like) {
                    Log::warning("Comment already liked CommentID: $commentId UserID: $doctor_id");

                    return response()->json([
                        'value' => false,
                        'message' => __('api.comment_liked_successfully'),
                    ], 400);
                }

                // Create a new like entry
                $newLike = FeedPostCommentLike::create([
                    'post_comment_id' => $commentId,
                    'doctor_id' => $doctor_id,
                ]);

                Log::info("Comment ID $commentId liked by doctor $doctor_id");

                $comment = FeedPostComment::findOrFail($commentId);
                $commentOwner = $comment->doctor;

                if ($commentOwner->id !== Auth::id()) {
                    AppNotification::createLocalized([
                        'doctor_id' => $commentOwner->id,
                        'type' => 'CommentLike',
                        'type_id' => $comment->feed_post_id,
                        'localization_key' => 'api.notification_comment_liked',
                        'localization_params' => ['name' => Auth::user()->name.' '.Auth::user()->lname],
                        'type_doctor_id' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Get FCM tokens for push notification
                    $tokens = FcmToken::where('doctor_id', $commentOwner->id)
                        ->pluck('token')
                        ->toArray();

                    if (! empty($tokens)) {
                        $this->notificationService->sendPushNotification(
                            __('api.comment_was_liked'),
                            __('api.doctor_liked_comment', ['name' => ucfirst(Auth::user()->name)]),
                            $tokens
                        );
                    }

                    Log::info('Notification sent to comment owner ID: '.$commentOwner->id.' for comment ID: '.$comment->id);
                }

                return response()->json([
                    'value' => true,
                    'data' => $newLike,
                    'message' => __('api.comment_liked_successfully'),
                ]);

                // Handle Unlike
            } elseif ($status === 'unlike') {
                if ($like) {
                    $like->delete();
                    Log::info("Comment ID $commentId unliked by doctor $doctor_id");

                    return response()->json([
                        'value' => true,
                        'message' => __('api.comment_unliked_successfully'),
                    ]);
                }

                Log::warning("Like not found for comment ID $commentId");

                return response()->json([
                    'value' => false,
                    'message' => __('api.resource_not_found'),
                ], 404);
            } else {
                Log::warning("Invalid status for comment like/unlike: $status");

                return response()->json([
                    'value' => false,
                    'message' => __('api.validation_failed'),
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error("Error processing like/unlike for comment ID $commentId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'message' => __('api.server_error'),
            ], 500);
        }
    }

    public function trending()
    {
        // Query the hashtags sorted by usage count, limit to top 10
        $trendingHashtags = Hashtag::orderBy('usage_count', 'desc')->paginate(10);

        return response()->json($trendingHashtags);
    }

    // Search for hashtags
    public function searchHashtags(Request $request)
    {
        $query = $request->input('query');

        if (! $query) {
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('validation.required', ['attribute' => 'Query parameter']),
            ], 400);
        }

        try {
            $hashtags = Hashtag::where('tag', 'LIKE', '%'.$query.'%')->paginate(10);

            if ($hashtags->isEmpty()) {
                Log::info("No hashtags found for query: $query");

                return response()->json([
                    'value' => true,
                    'data' => [],
                    'message' => __('api.no_feed_posts_found'),
                ]);
            }

            Log::info("Hashtags retrieved for query: $query");

            return response()->json([
                'value' => true,
                'data' => $hashtags,
                'message' => __('api.feed_posts_retrieved_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error searching hashtags for query $query: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.error_retrieving_feed_posts'),
            ], 500);
        }
    }

    // Get posts by hashtag
    public function getPostsByHashtag($hashtagId)
    {
        try {
            // Find the hashtag by ID
            $hashtag = Hashtag::find($hashtagId);

            if (! $hashtag) {
                Log::info("No posts found for hashtag ID: $hashtagId");

                return response()->json([
                    'value' => true,
                    'data' => [],
                    'message' => __('api.no_feed_posts_found'),
                ]);
            }

            $doctorId = auth()->id(); // Get the authenticated doctor's ID

            // Fetch posts with necessary relationships and counts
            $posts = $hashtag->posts()
                ->with([
                    'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                    'hashtags:id,tag,usage_count',
                    'poll' => function ($query) {
                        $query->select('id', 'feed_post_id', 'question', 'allow_add_options', 'allow_multiple_choice');
                    },
                    'poll.options' => function ($query) use ($doctorId) {
                        $query->select('id', 'poll_id', 'option_text')
                            ->withCount('votes') // Count votes per option
                            ->with(['votes' => function ($voteQuery) use ($doctorId) {
                                $voteQuery->select('id', 'poll_option_id', 'doctor_id')
                                    ->where('doctor_id', $doctorId); // Check if user voted
                            }]);
                    },
                ])
                ->withCount(['likes', 'comments'])  // Count likes and comments
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is saved by the doctor
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is liked by the doctor
                    },
                ])
                ->paginate(10); // Paginate 10 posts per page

            // Add 'is_saved' and 'is_liked' fields to each post
            $posts->getCollection()->transform(function ($post) {
                // Add 'is_saved' field (true if the doctor saved the post)
                $post->isSaved = $post->saves->isNotEmpty();

                // Add 'is_liked' field (true if the doctor liked the post)
                $post->isLiked = $post->likes->isNotEmpty();

                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) {
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

            return response()->json([
                'value' => true,
                'data' => $posts,
                'message' => __('api.feed_posts_retrieved_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching posts for hashtag ID $hashtagId: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.error_retrieving_feed_posts'),
            ], 500);
        }
    }

    // Search for posts
    public function searchPosts(Request $request)
    {
        $query = $request->input('query');

        if (! $query) {
            // Create an empty paginated response with the same structure
            $emptyPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                [], // Empty data array
                0,  // Total items
                10, // Items per page
                1,  // Current page
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );

            return response()->json([
                'value' => true,
                'data' => $emptyPaginator,
                'message' => __('validation.required', ['attribute' => 'Query']),
            ]);
        }

        try {
            $doctorId = auth()->id(); // Get the authenticated doctor's ID

            // Get user's joined group IDs for privacy filtering
            $userJoinedGroupIds = DB::table('group_user')
                ->where('doctor_id', $doctorId)
                ->where('status', 'joined')
                ->pluck('group_id')
                ->toArray();

            // Search posts by content with group privacy filtering
            $posts = FeedPost::where('content', 'LIKE', '%'.$query.'%')
                ->where(function ($query) use ($userJoinedGroupIds) {
                    // Include posts that are not in any group
                    $query->whereNull('group_id')
                        // OR posts from public groups
                        ->orWhereHas('group', function ($groupQuery) {
                            $groupQuery->where('privacy', 'public');
                        })
                        // OR posts from private groups where user is a member
                        ->orWhereIn('group_id', $userJoinedGroupIds);
                })
                ->with([
                    'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                    'hashtags:id,tag,usage_count',
                    'group:id,name,privacy', // Include group info for debugging
                    'poll' => function ($query) {
                        $query->select('id', 'feed_post_id', 'question', 'allow_add_options', 'allow_multiple_choice');
                    },
                    'poll.options' => function ($query) use ($doctorId) {
                        $query->select('id', 'poll_id', 'option_text')
                            ->withCount('votes') // Count votes per option
                            ->with(['votes' => function ($voteQuery) use ($doctorId) {
                                $voteQuery->select('id', 'poll_option_id', 'doctor_id')
                                    ->where('doctor_id', $doctorId); // Check if user voted
                            }]);
                    },
                ])
                ->withCount(['likes', 'comments'])  // Count likes and comments
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is saved by the doctor
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is liked by the doctor
                    },
                ])
                ->latest('updated_at')
                ->paginate(10); // Paginate 10 posts per page

            // Add 'is_saved' and 'is_liked' fields to each post
            $posts->getCollection()->transform(function ($post) {
                // Add 'is_saved' field (true if the doctor saved the post)
                $post->isSaved = $post->saves->isNotEmpty();

                // Add 'is_liked' field (true if the doctor liked the post)
                $post->isLiked = $post->likes->isNotEmpty();

                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) {
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

                return response()->json([
                    'value' => true,
                    'data' => $posts,
                    'message' => __('api.no_feed_posts_found'),
                ]);
            }

            Log::info("Posts retrieved for query: $query");

            return response()->json([
                'value' => true,
                'data' => $posts,
                'message' => __('api.feed_posts_retrieved_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::error("Error searching posts for query $query: ".$e->getMessage());

            return response()->json([
                'value' => false,
                'data' => [],
                'message' => __('api.error_retrieving_feed_posts'),
            ], 500);
        }
    }
}
