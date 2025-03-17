<?php

namespace App\Http\Controllers;

use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostCommentLike;
use App\Models\FeedPostLike;
use App\Models\FeedSaveLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\MainController;
use App\Models\Hashtag;
use App\Models\Group;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\Poll;
use App\Models\PollOption; // If you have a separate PollOption model
use App\Models\PollVote;


class FeedPostController extends Controller
{
    protected $mainController;

    public function __construct(MainController $mainController)
    {
        $this->mainController = $mainController;
    }

    // Helper function to extract hashtags from post content
    public function extractHashtags($content)
    {
        preg_match_all('/#(\w+)/', $content, $matches);
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
                    'message' => 'No feed posts found'
                ]);
            }
            Log::info('Fetched all feed posts');
            return response()->json([
                'value' => true,
                'data' => $posts,
                'message' => 'Feed posts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching feed posts: ' . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving feed posts'
            ], 500);
        }
    }

    // Fetch feed posts with likes, comments, and saved status for the authenticated doctor
    public function getFeedPosts()
    {
        try {
            $doctorId = auth()->id(); // Get the authenticated doctor's ID
    
            // Fetch posts with necessary relationships and counts
            $feedPosts = FeedPost::with([
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
                ->where('group_id', null) // Fetch posts that are not in a group
                ->latest('created_at') // Sort by created_at in descending order
                ->paginate(10); // Paginate 10 posts per page
    
            // Process each post
            $feedPosts->getCollection()->transform(function ($post) use ($doctorId) {
                // Add 'is_saved' and 'is_liked' fields
                $post->isSaved = $post->saves->isNotEmpty();
                $post->isLiked = $post->likes->isNotEmpty();
    
                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) use ($doctorId) {
                        $option->is_voted = $option->votes->isNotEmpty(); // If user has voted for this option
                        unset($option->votes); // Remove unnecessary vote data
                        return $option;
                    })->sortByDesc('votes_count')->values();
                }
    
                // Remove unnecessary data
                unset($post->saves, $post->likes);
    
                return $post;
            });
    
            Log::info("Feed posts fetched for doctor ID $doctorId");
    
            return response()->json([
                'value' => true,
                'data' => $feedPosts,
                'message' => 'Feed posts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching feed posts for doctor ID $doctorId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving feed posts'
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
                ->where('doctor_id', $doctorId)
                ->where('group_id', null) // Fetch posts that are not in a group
                ->latest('created_at') // Sort by created_at in descending order
                ->paginate(10); // Paginate 10 posts per page

            // Add 'is_saved' and 'is_liked' fields to each post
            $feedPosts->getCollection()->transform(function ($post) use ($doctorId) {
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

            Log::info("Feed posts fetched for doctor ID $doctorId");

            return response()->json([
                'value' => true,
                'data' => $feedPosts,
                'message' => 'Doctor Posts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching Doctor posts for doctor ID $doctorId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving feed posts'
            ], 500);
        }
    }

    public function getDoctorSavedPosts($doctorId)
    {
        try {
            // Fetch only saved posts for the given doctor ID
            $feedPosts = FeedPost::whereHas('saves', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId);
                })
                ->with([
                    'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                    'poll.options' => function ($query) use ($doctorId) { 
                        $query->withCount('votes') // Count votes per option
                              ->with(['votes' => function ($voteQuery) use ($doctorId) {
                                  $voteQuery->where('doctor_id', $doctorId); // Check if user voted
                              }]);
                    }
                ])
                ->withCount(['likes', 'comments']) // Count likes and comments
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId);
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId);
                    }
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
    
            // Transform posts to add `isSaved` and `isLiked` flags
            $feedPosts->getCollection()->transform(function ($post) {
                $post->isSaved = true; // Since we're only fetching saved posts, this is always true
                $post->isLiked = $post->likes->isNotEmpty();
                
                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) use ($doctorId) {
                        $option->is_voted = $option->votes->isNotEmpty(); // If user has voted for this option
                        unset($option->votes); // Remove unnecessary vote data
                        return $option;
                    })->sortByDesc('votes_count')->values();
                }
                // Remove unnecessary relationship data
                unset($post->saves, $post->likes);
    
                return $post;
            });
    
            Log::info("Saved posts fetched for doctor ID $doctorId");
    
            return response()->json([
                'value' => true,
                'data' => $feedPosts,
                'message' => 'Doctor saved posts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching saved posts for doctor ID $doctorId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving saved posts'
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
                'comments' => function ($query) {
                    $query->orderBy('created_at', 'desc')->paginate(10);
                },
                'comments.doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'poll.options' => function ($query) use ($doctorId) { 
                    $query->withCount('votes') // Load vote count for each option
                          ->with(['votes' => function ($voteQuery) use ($doctorId) {
                              $voteQuery->where('doctor_id', $doctorId); // Check if the user voted
                          }]);
                }
            ])->withCount(['likes', 'comments'])
            ->with([
                'saves' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId); // Check if the post is saved by the doctor
                },
                'likes' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId); // Check if the post is liked by the doctor
                }
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
                'message' => 'Post retrieved successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $id not found");
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error fetching post ID $id: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving the post'
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
                    'message' => 'No likes found for this post'
                ]);
            }

            // Transform the likes collection to return only the doctor data
            $doctorData = $likes->getCollection()->map(function($like) {
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
                'message' => 'Post likes retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching likes for post ID $postId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving post likes'
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
                ->get(); // Fetch all comments to apply custom sorting
    
            // Reorder comments: Post owner's comments first, followed by auth user's comments
            $comments = $comments->sortByDesc(function ($comment) use ($doctorId, $postOwnerId) {
                return [
                    $comment->doctor_id === $postOwnerId ? 2 : 0, // Post owner's comments first
                    $comment->doctor_id === $doctorId ? 1 : 0     // Auth user's comments second
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
    
            // Prepare the paginated response
            $paginatedResponse = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedComments,
                $comments->count(), // Total items count
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
    
            Log::info("Comments retrieved for post ID $postId");
            return response()->json([
                'value' => true,
                'data' => $paginatedResponse,
                'message' => 'Post comments retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching comments for post ID $postId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving post comments'
            ], 500);
        }
    }
    

// Create a new post
public function store(Request $request)
{
    DB::beginTransaction();

    try {
        // Validate request
        $validatedData = $request->validate($this->validationRules());

        // Handle group validation
        $this->handleGroupValidation($validatedData);

        // Handle media upload
        $mediaPath = $this->handleMediaUpload($request, $validatedData['media_type'] ?? null);

        // Create Feed Post
        $post = $this->createFeedPost($validatedData, $mediaPath);

        // Attach hashtags
        $this->attachHashtags($post, $request->input('content'));

        // Handle poll creation
        if (!empty($validatedData['poll']) && isset($validatedData['poll']['question'])) {
            $poll = new Poll([
                'question' => $validatedData['poll']['question'],
                'allow_add_options' => $validatedData['poll']['allow_add_options'] ?? false,
                'allow_multiple_choice' => $validatedData['poll']['allow_multiple_choice'] ?? false
            ]);

            // Explicitly associate poll with the post
            $post->poll()->save($poll);

            foreach ($validatedData['poll']['options'] as $optionText) {
                $poll->options()->create(['option_text' => $optionText]);
            }
        }


        Log::info("Poll Data:", $validatedData['poll']);

        DB::commit();

        // Notify doctors
        $this->notifyDoctors($post);

        return response()->json([
            'value' => true,
            'data' => $post->load('poll.options'), 
            'message' => 'Post created successfully'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error creating post: " . $e->getMessage());
        return response()->json(['value' => false, 'message' => "Error creating post: " . $e->getMessage()], 500);
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
        $mediaPath = $post->media_path;
        $mediaType = $post->media_type;

        // If media_type is sent in the request
        if ($request->has('media_type')) {
            $mediaType = $request->media_type;

            // If media_type is 'text', remove media
            if ($mediaType === "text") {
                $mediaType = null;
                $mediaPath = null;
            } else{
                // Handle media upload if a new media file is uploaded
                $mediaPath = $this->handleMediaUpload($request, $mediaType);
            }
        }

        // Update the post with validated data
        $post->update([
            'content' => $validatedData['content'],
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
            'visibility' => $validatedData['visibility'] ?? 'Public',
            'group_id' => $validatedData['group_id'] ?? null,
        ]);

        // Attach hashtags to the post
        $this->attachHashtags($post, $request->input('content'));

        // Commit the transaction
        DB::commit();

        // Log the successful update
        Log::info("Post ID $id updated by doctor " . $user->id);

        // Return a success response
        return response()->json([
            'value' => true,
            'data' => $post,
            'message' => 'Post updated successfully'
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        DB::rollBack();
        Log::warning("Post ID $id not found for update");

        return response()->json([
            'value' => false,
            'message' => 'Post not found'
        ], 404);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Error updating post ID $id: " . $e->getMessage());

        return response()->json([
            'value' => false,
            'message' => 'An error occurred while updating the post: ' . $e->getMessage()
        ], 500);
    }
}
    
    

private function validationRules()
{
    return [
        'content' => 'nullable|string',
        'media_type' => 'nullable|string|in:image,video,text',
        'media_path' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mkv|max:20480',
        'visibility' => 'nullable|string|in:Public,Friends,Only Me',
        'group_id' => 'nullable|exists:groups,id',
        'poll' => 'nullable|array',  // ✅ Allow `poll` to be an array
        'poll.question' => 'nullable|string|max:255',
        'poll.allow_add_options' => 'nullable|boolean',
        'poll.allow_multiple_choice' => 'nullable|boolean',
        'poll.options' => 'nullable|array',
        'poll.options.*' => 'nullable|string|max:255'
    ];
}


private function createPollForPost(FeedPost $post, array $data)
{
    $poll = $post->poll()->create([
        'question' => $validatedData['poll']['question'],
        'allow_add_options' => $validatedData['poll']['allow_add_options'] ?? false,
        'allow_multiple_choice' => $validatedData['poll']['allow_multiple_choice'] ?? false
    ]);

    foreach ($validatedData['poll']['options'] as $optionText) {
        $poll->options()->create(['option_text' => $optionText]);
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

private function handleMediaUpload(Request $request, $mediaType)
{
    if ($request->hasFile('media_path')) {
        $media = $request->file('media_path');
        $path = ($mediaType === 'image') ? 'media_images' : 'media_videos';

        $uploadResponse = $this->mainController->uploadImageAndVideo($media, $path);

        if ($uploadResponse->getData()->value) {
            return $uploadResponse->getData()->image;
        } else {
            throw new \Exception('Media upload failed.', 500);
        }
    }

    return null;
}

private function createFeedPost(array $validatedData, $mediaPath)
{
    $post = FeedPost::create([
        'doctor_id' => Auth::id(),
        'content' => $validatedData['content'],
        'media_type' => $validatedData['media_type'] ?? null,
        'media_path' => $mediaPath,
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
    $hashtags = $this->extractHashtags($content);

    foreach ($hashtags as $tag) {
        $hashtag = Hashtag::firstOrCreate(['tag' => $tag], ['usage_count' => 0]);
        $hashtag->increment('usage_count');
        $post->hashtags()->attach($hashtag->id);
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
            'type' => 'Other',
            'type_id' => $post->id,
            'content' => sprintf('Dr. %s added a new post', $doctorName),
            'type_doctor_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ];
    })->toArray();

    AppNotification::insert($notifications);

    Log::info("Notifications inserted successfully for post ID: " . $post->id);
}

// Delete a post
public function destroy($id)
{
    try {
        $post = FeedPost::findOrFail($id);

        $user = Auth::user();
        $isAdminOrTester = $user->hasRole('Admin') || $user->hasRole('Tester');

        // Allow only the post owner or Admin/Tester
        if ($post->doctor_id !== $user->id && !$isAdminOrTester) {
            Log::warning("Unauthorized deletion attempt by doctor " . Auth::id());
            return response()->json([
                'value' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        $post->delete();

        Log::info("Post ID $id deleted by doctor " . Auth::id());
        return response()->json([
            'value' => true,
            'message' => 'Post deleted successfully'
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::warning("Post ID $id not found for deletion");
        return response()->json([
            'value' => false,
            'message' => 'Post not found'
        ], 404);
    } catch (\Exception $e) {
        Log::error("Error deleting post ID $id: " . $e->getMessage());
        return response()->json([
            'value' => false,
            'message' => 'An error occurred while deleting the post'
        ], 500);
    }
}


public function likeOrUnlikePost(Request $request, $postId)
{
    try {
        $doctor_id = Auth::id();
        $status = $request->input('status'); // 'like' or 'unlike'

        // Check if the post exists
        $post = FeedPost::findOrFail($postId);

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
                    'message' => 'Post already liked'
                ], 400);
            }

            // Create a new like entry
            $newLike = FeedPostLike::create([
                'feed_post_id' => $postId,
                'doctor_id' => $doctor_id,
            ]);

            $postOwner = $post->doctor;

            // Check if the post owner is not the one liking the post
            if ($postOwner->id !== Auth::id()) {
                $notification = AppNotification::create([
                    'doctor_id' => $postOwner->id,
                    'type' => 'Other',
                    'type_id' => $post->id,
                    'content' => sprintf('Dr. %s liked your post', Auth::user()->name . ' ' . Auth::user()->lname),
                    'type_doctor_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info("Notification sent to post owner ID: " . $postOwner->id . " for post ID: " . $post->id);
            }



            Log::info("Post ID $postId liked by doctor " . $doctor_id);
            return response()->json([
                'value' => true,
                'data' => $newLike,
                'message' => 'Post liked successfully'
            ]);

            // Handle Unlike
        } elseif ($status === 'unlike') {
            if ($like) {
                $like->delete();
                Log::info("Post ID $postId unliked by doctor " . $doctor_id);
                return response()->json([
                    'value' => true,
                    'message' => 'Post unliked successfully'
                ]);
            }

            Log::warning("Like not found for post ID $postId");
            return response()->json([
                'value' => false,
                'message' => 'Like not found'
            ], 404);
        } else {
            Log::warning("Invalid status for post like/unlike: $status");
            return response()->json([
                'value' => false,
                'message' => 'Invalid status. Use "like" or "unlike".'
            ], 400);
        }

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::warning("Post ID $postId not found for like/unlike");
        return response()->json([
            'value' => false,
            'message' => 'Post not found'
        ], 404);
    } catch (\Exception $e) {
        Log::error("Error processing like/unlike for post ID $postId: " . $e->getMessage());
        return response()->json([
            'value' => false,
            'message' => 'An error occurred while processing the request'
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
                    'message' => 'Post already saved'
                ], 400);
            }

            // Create a new save entry
            $newSave = FeedSaveLike::create([
                'feed_post_id' => $postId,
                'doctor_id' => $doctor_id,
            ]);

            Log::info("Post ID $postId saved by doctor " . $doctor_id);
            return response()->json([
                'value' => true,
                'data' => $newSave,
                'message' => 'Post saved successfully'
            ]);

            // Handle Unsave
        } elseif ($status === 'unsave') {
            if ($save) {
                $save->delete();
                Log::info("Post ID $postId unsaved by doctor " . $doctor_id);
                return response()->json([
                    'value' => true,
                    'message' => 'Post unsaved successfully'
                ]);
            }

            Log::warning("Save not found for post ID $postId");
            return response()->json([
                'value' => false,
                'message' => 'Save not found'
            ], 404);
        } else {
            Log::warning("Invalid status for post save/unsave: $status");
            return response()->json([
                'value' => false,
                'message' => 'Invalid status. Use "save" or "unsave".'
            ], 400);
        }

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::warning("Post ID $postId not found for save/unsave");
        return response()->json([
            'value' => false,
            'message' => 'Post not found'
        ], 404);
    } catch (\Exception $e) {
        Log::error("Error processing save/unsave for post ID $postId: " . $e->getMessage());
        return response()->json([
            'value' => false,
            'message' => 'An error occurred while processing the request'
        ], 500);
    }
}

    // Add a comment to a post
    public function addComment(Request $request, $postId)
    {
        try {
            $validatedData = $request->validate([
                'comment' => 'required|string|max:500',
                'parent_id' => 'nullable|exists:feed_post_comments,id',  // Validate parent_id if provided
            ]);

            $comment = FeedPostComment::create([
                'feed_post_id' => $postId,
                'doctor_id' => Auth::id(),
                'comment' => $validatedData['comment'],
                'parent_id' => $validatedData['parent_id'] ?? null,  // Set parent_id if it's a reply
            ]);

            Log::info("Comment added to post ID $postId by doctor " . Auth::id());

            $post = FeedPost::findOrFail($postId);
            $postOwner = $post->doctor;

            // Check if the post owner is not the one commenting on the post
            if ($postOwner->id !== Auth::id()) {
                $notification = AppNotification::create([
                    'doctor_id' => $postOwner->id,
                    'type' => 'Other',
                    'type_id' => $post->id,
                    'content' => sprintf('Dr. %s commented on your post', Auth::user()->name . ' ' . Auth::user()->lname),
                    'type_doctor_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info("Notification sent to post owner ID: " . $postOwner->id . " for post ID: " . $post->id);
            }


            return response()->json([
                'value' => true,
                'data' => $comment,
                'message' => 'Comment added successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error adding comment to post ID $postId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while adding the comment'
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
            if ($comment->doctor_id !== $user->id && !$isAdminOrTester) {
                Log::warning("Unauthorized deletion attempt by doctor " . Auth::id());
                return response()->json([
                    'value' => false,
                    'message' => 'Unauthorized action'
                ], 403);
            }

            $comment->delete();
            Log::info("Comment ID $commentId deleted by doctor " . Auth::id());

            return response()->json([
                'value' => true,
                'message' => 'Comment deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Comment ID $commentId not found for deletion");
            return response()->json([
                'value' => false,
                'message' => 'Comment not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting comment ID $commentId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while deleting the comment'
            ], 500);
        }
    }

    public function likeOrUnlikeComment(Request $request, $commentId)
    {
        try {
            $doctor_id = Auth::id();
            $status = $request->input('status'); // 'like' or 'unlike'

            // Find if the comment exists
            $comment = FeedPostComment::find($commentId);  // use find instead of findOrFail

            if (!$comment) {
                Log::error("No comment was found with ID: $commentId");
                return response()->json([
                    'value' => false,
                    'message' => 'No comment was found with ID: '.$commentId
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
                        'message' => 'Comment already liked'
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

                // Check if the comment owner is not the one liking the comment
                if ($commentOwner->id !== Auth::id()) {
                    $notification = AppNotification::create([
                        'doctor_id' => $commentOwner->id,
                        'type' => 'Other',
                        'type_id' => $comment->id,
                        'content' => sprintf('Dr. %s liked your comment', Auth::user()->name . ' ' . Auth::user()->lname),
                        'type_doctor_id' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    Log::info("Notification sent to comment owner ID: " . $commentOwner->id . " for comment ID: " . $comment->id);
                }

                return response()->json([
                    'value' => true,
                    'data' => $newLike,
                    'message' => 'Comment liked successfully'
                ]);

                // Handle Unlike
            } elseif ($status === 'unlike') {
                if ($like) {
                    $like->delete();
                    Log::info("Comment ID $commentId unliked by doctor $doctor_id");
                    return response()->json([
                        'value' => true,
                        'message' => 'Comment unliked successfully'
                    ]);
                }

                Log::warning("Like not found for comment ID $commentId");
                return response()->json([
                    'value' => false,
                    'message' => 'Like not found'
                ], 404);

            } else {
                Log::warning("Invalid status for comment like/unlike: $status");
                return response()->json([
                    'value' => false,
                    'message' => 'Invalid status. Use "like" or "unlike".'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error("Error processing like/unlike for comment ID $commentId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while processing the request'
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

        if (!$query) {
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'Query parameter is required'
            ], 400);
        }

        try {
            $hashtags = Hashtag::where('tag', 'LIKE', '%' . $query . '%')->paginate(10);

            if ($hashtags->isEmpty()) {
                Log::info("No hashtags found for query: $query");
                return response()->json([
                    'value' => true,
                    'data' => [],
                    'message' => 'No hashtags found'
                ]);
            }

            Log::info("Hashtags retrieved for query: $query");
            return response()->json([
                'value' => true,
                'data' => $hashtags,
                'message' => 'Hashtags retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error searching hashtags for query $query: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while searching for hashtags'
            ], 500);
        }
    }

    // Get posts by hashtag
    public function getPostsByHashtag($hashtagId)
    {
        try {
            // Find the hashtag by ID
            $hashtag = Hashtag::find($hashtagId);

            if (!$hashtag) {
                Log::info("No posts found for hashtag ID: $hashtagId");
                return response()->json([
                    'value' => true,
                    'data' => [],
                    'message' => 'No posts found for this hashtag'
                ]);
            }

            $doctorId = auth()->id(); // Get the authenticated doctor's ID

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
            return response()->json([
                'value' => true,
                'data' => $posts,
                'message' => 'Posts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching posts for hashtag ID $hashtagId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while retrieving posts'
            ], 500);
        }
    }

    // Search for posts
    public function searchPosts(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'Query parameter is required'
            ], 400);
        }

        try {
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
                return response()->json([
                    'value' => true,
                    'data' => [],
                    'message' => 'No posts found'
                ]);
            }

            Log::info("Posts retrieved for query: $query");
            return response()->json([
                'value' => true,
                'data' => $posts,
                'message' => 'Posts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error searching posts for query $query: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while searching for posts'
            ], 500);
        }
    }


}
