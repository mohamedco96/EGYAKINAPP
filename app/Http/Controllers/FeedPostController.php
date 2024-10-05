<?php

namespace App\Http\Controllers;

use App\Models\FeedPost;
use App\Models\FeedPostComment;
use App\Models\FeedPostLike;
use App\Models\FeedSaveLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FeedPostController extends Controller
{
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

            $feedPosts = FeedPost::with(['doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired'])
                ->withCount(['likes', 'comments'])
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if post is saved by the doctor
                    }
                ])
                ->paginate(10); // Paginate 10 posts per page

            // Add 'is_saved' field to each post
            $feedPosts->getCollection()->transform(function ($post) use ($doctorId) {
                $post->is_saved = $post->saves->isNotEmpty();
                unset($post->saves); // Remove unnecessary data
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
                        $query->where('doctor_id', $doctorId); // Check if post is saved
                    }
                ])->findOrFail($id);

            $post->is_saved = $post->saves->isNotEmpty();
            unset($post->saves);

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
                    'value' => true,
                    'data' => [],
                    'message' => 'No likes found for this post'
                ]);
            }

            Log::info("Likes retrieved for post ID $postId");
            return response()->json([
                'value' => true,
                'data' => $likes,
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
            $comments = FeedPostComment::where('feed_post_id', $postId)
                ->with(['doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired'])
                ->paginate(10);

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
            $validatedData = $request->validate([
                'content' => 'required|string|max:1000',
                'media_type' => 'nullable|string',
                'media_path' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mkv',
                'visibility' => 'required|string|in:Public,Friends,Only Me',
            ]);

            if ($request->hasFile('media')) {
                $path = $request->file('media')->store('media', 'public');
                $validatedData['media_path'] = $path;
            }

            $post = FeedPost::create([
                'doctor_id' => Auth::id(),
                'content' => $validatedData['content'],
                'media_type' => $validatedData['media_type'] ?? null,
                'media_path' => $validatedData['media_path'] ?? null,
                'visibility' => $validatedData['visibility'] ?? 'Public',
            ]);

            Log::info("Post created by doctor " . Auth::id());
            return response()->json([
                'value' => true,
                'data' => $post,
                'message' => 'Post created successfully'
            ]);
        } catch (\Exception $e) {
            Log::error("Error creating post: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'data' => [],
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

    // Like a post
    public function likePost($postId)
    {
        try {
            $post = FeedPost::findOrFail($postId);

            $like = FeedPostLike::firstOrCreate([
                'feed_post_id' => $postId,
                'doctor_id' => Auth::id(),
            ]);

            Log::info("Post ID $postId liked by doctor " . Auth::id());

            return response()->json([
                'value' => true,
                'data' => $like,
                'message' => 'Post liked successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $postId not found for like");
            return response()->json([
                'value' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error liking post ID $postId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while liking the post'
            ], 500);
        }
    }

    // Unlike a post
    public function unlikePost($postId)
    {
        try {
            $post = FeedPost::findOrFail($postId);

            $like = FeedPostLike::where('feed_post_id', $postId)
                ->where('doctor_id', Auth::id())
                ->first();

            if ($like) {
                $like->delete();
                Log::info("Post ID $postId unliked by doctor " . Auth::id());

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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $postId not found for unlike");
            return response()->json([
                'value' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error unliking post ID $postId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while unliking the post'
            ], 500);
        }
    }

    // Save a post
    public function savePost($postId)
    {
        try {
            $post = FeedPost::findOrFail($postId);

            $save = FeedSaveLike::firstOrCreate([
                'feed_post_id' => $postId,
                'doctor_id' => Auth::id(),
            ]);

            Log::info("Post ID $postId saved by doctor " . Auth::id());

            return response()->json([
                'value' => true,
                'data' => $save,
                'message' => 'Post saved successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $postId not found for save");
            return response()->json([
                'value' => false,
                'message' => 'Post not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error saving post ID $postId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while saving the post'
            ], 500);
        }
    }

    // Remove saved post
    public function unsavePost($postId)
    {
        try {
            $save = FeedSaveLike::where('feed_post_id', $postId)
                ->where('doctor_id', Auth::id())
                ->first();

            if ($save) {
                $save->delete();
                Log::info("Post ID $postId unsaved by doctor " . Auth::id());

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
        } catch (\Exception $e) {
            Log::error("Error unsaving post ID $postId: " . $e->getMessage());
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while unsaving the post'
            ], 500);
        }
    }

    // Add a comment to a post
    public function addComment(Request $request, $postId)
    {
        try {
            $validatedData = $request->validate([
                'comment' => 'required|string|max:500',
            ]);

            $comment = FeedPostComment::create([
                'feed_post_id' => $postId,
                'doctor_id' => Auth::id(),
                'comment' => $validatedData['comment'],
            ]);

            Log::info("Comment added to post ID $postId by doctor " . Auth::id());

            return response()->json([
                'value' => true,
                'data' => $comment,
                'message' => 'Comment added successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Post ID $postId not found for comment");
            return response()->json([
                'value' => false,
                'message' => 'Post not found'
            ], 404);
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
}
