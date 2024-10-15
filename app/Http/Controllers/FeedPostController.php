<?php

namespace App\Http\Controllers;

use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostCommentLike;
use App\Models\FeedPostLike;
use App\Models\FeedSaveLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\MainController;

class FeedPostController extends Controller
{
    protected $mainController;

    public function __construct(MainController $mainController)
    {
        $this->mainController = $mainController;
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

    // Fetch a specific post by ID with its related comments, likes, and saves
    public function show($id)
    {
        try {
            $post = FeedPost::with(['comments.doctor', 'likes', 'saves'])->findOrFail($id);
            Log::info("Post ID $id retrieved successfully");
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

    // Fetch feed posts with likes, comments, and saved status for the authenticated doctor
    public function getFeedPosts()
    {
        try {
            $doctorId = auth()->id(); // Get the authenticated doctor's ID

            // Fetch posts with necessary relationships and counts
            $feedPosts = FeedPost::with(['doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired'])
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
            $feedPosts->getCollection()->transform(function ($post) use ($doctorId) {
                // Add 'is_saved' field (true if the doctor saved the post)
                $post->isSaved = $post->saves->isNotEmpty();

                // Add 'is_liked' field (true if the doctor liked the post)
                $post->isLiked = $post->likes->isNotEmpty();

                // Remove unnecessary data to clean up the response
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

            // Add 'is_liked' field (true if the doctor liked the post)
            $post->isLiked = $post->likes->isNotEmpty();

            // Remove unnecessary data to clean up the response
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
            $doctorData = $likes->map(function($like) {
                return $like->doctor;  // Returning only the doctor object
            });

            Log::info("Likes retrieved for post ID $postId");
            return response()->json([
                'value' => true,
                'data' => $doctorData,
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
            $doctorId = auth()->id();

            // Get the comments with like and reply counts, including nested replies
            $comments = FeedPostComment::where('feed_post_id', $postId)
                ->whereNull('parent_id')  // Get only top-level comments
                ->with([
                    'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',  // Include doctor's details
                    'replies' => function($query) use ($doctorId) {
                        // For each reply, include doctor, likes, and recursively load nested replies
                        $query->with([
                            'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                            'likes' => function ($query) use ($doctorId) {
                                $query->where('doctor_id', $doctorId);  // Check if reply is liked by the doctor
                            },
                            'replies' => function($query) use ($doctorId) {  // Nested replies
                                $query->withCount(['likes as likes_count', 'replies as replies_count'])
                                    ->with([
                                        'likes' => function ($query) use ($doctorId) {
                                            $query->where('doctor_id', $doctorId);  // Check if nested reply is liked by the doctor
                                        }
                                    ]);
                            }
                        ])->withCount(['likes as likes_count', 'replies as replies_count']);
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId);  // Check if the comment is liked by the doctor
                    }
                ])
                ->withCount(['likes as likes_count', 'replies as replies_count'])  // Count likes and replies for each comment
                ->paginate(10);

            // Add 'isLiked' for comments and replies
            foreach ($comments as $comment) {
                $comment->isLiked = $comment->likes->isNotEmpty();  // Check if the current doctor liked the comment

                // Process replies to add 'isLiked'
                foreach ($comment->replies as $reply) {
                    $reply->isLiked = $reply->likes->isNotEmpty();  // Check if the current doctor liked the reply

                    // Process nested replies (if any)
                    foreach ($reply->replies as $nestedReply) {
                        $nestedReply->isLiked = $nestedReply->likes->isNotEmpty();  // Check if the doctor liked the nested reply
                    }
                }

                // Optionally remove the 'likes' relation to clean up the response
                unset($comment->likes);
                foreach ($comment->replies as $reply) {
                    unset($reply->likes);
                    foreach ($reply->replies as $nestedReply) {
                        unset($nestedReply->likes);
                    }
                }
            }

            if ($comments->isEmpty()) {
                Log::info("No comments found for post ID $postId");
                return response()->json([
                    'value' => true,
                    'data' => [],
                    'message' => 'No comments found for this post'
                ]);
            }

            Log::info("Comments retrieved for post ID $postId");
            return response()->json([
                'value' => true,
                'data' => $comments,
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
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'content' => 'required|string|max:1000',
                'media_type' => 'nullable|string|in:image,video',
                'media_path' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mkv|max:20480',
                'visibility' => 'nullable|string|in:Public,Friends,Only Me',
            ]);

            // Initialize mediaPath as null
            $mediaPath = null;

            // Check if media_path is provided and the file is valid
            if ($request->hasFile('media_path')) {
                $media = $request->file('media_path');
                $path = ($validatedData['media_type'] === 'image') ? 'media_images' : 'media_videos';

                // Call the upload function to store the media and get the media URL
                $uploadResponse = $this->mainController->uploadImageAndVideo($media, $path);

                // Check if the upload was successful
                if ($uploadResponse->getData()->value) {
                    $mediaPath = $uploadResponse->getData()->image;  // Store the URL of the uploaded media
                } else {
                    // Handle upload error
                    return response()->json([
                        'value' => false,
                        'message' => 'Media upload failed.'
                    ], 500);
                }
            }

            // Create a new feed post
            $post = FeedPost::create([
                'doctor_id' => Auth::id(),
                'content' => $validatedData['content'],
                'media_type' => $validatedData['media_type'] ?? null,
                'media_path' => $mediaPath,  // Save the media URL
                'visibility' => $validatedData['visibility'] ?? 'Public',
            ]);

            // Log the post creation
            Log::info("Post created by doctor " . Auth::id());

            // Return success response
            return response()->json([
                'value' => true,
                'data' => $post,
                'message' => 'Post created successfully'
            ]);
        } catch (\Exception $e) {
            // Log error
            Log::error("Error creating post: " . $e->getMessage());

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while creating the post'
            ], 500);
        }
    }
    // Delete a post
    public function destroy($id)
    {
        try {
            $post = FeedPost::findOrFail($id);

            if ($post->doctor_id != Auth::id()) {
                Log::warning("Unauthorized deletion attempt by doctor " . Auth::id());
                return response()->json([
                    'value' => false,
                    'data' => [],
                    'message' => 'Unauthorized action'
                ], 403);
            }

            $post->delete();

            Log::info("Post ID $id deleted by doctor " . Auth::id());
            return response()->json([
                'value' => true,
                'data' => [],
                'message' => 'Post deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $id not found for deletion");
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error deleting post ID $id: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
                'message' => 'An error occurred while deleting the post'
            ], 500);
        }
    }

    // Update a post
    public function update(Request $request, $id)
    {
        try {
            $post = FeedPost::findOrFail($id);

            if ($post->doctor_id !== Auth::id()) {
                Log::warning("Unauthorized update attempt by doctor " . Auth::id());
                return response()->json([
                    'value' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validatedData = $request->validate([
                'content' => 'required|string|max:1000',
                'media_type' => 'nullable|string',
                'media_path' => 'nullable|string',
                'visibility' => 'required|string|in:Public,Friends,Only Me',
            ]);

            $post->update($validatedData);
            Log::info("Post ID $id updated by doctor " . Auth::id());

            return response()->json([
                'value' => true,
                'data' => $post,
                'message' => 'Post updated successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $id not found for update");
            return response()->json([
                'value' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error updating post ID $id: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while updating the post'
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

            if ($comment->doctor_id !== Auth::id()) {
                Log::warning("Unauthorized comment delete attempt by doctor " . Auth::id());
                return response()->json([
                    'value' => false,
                    'message' => 'Unauthorized'
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


}
