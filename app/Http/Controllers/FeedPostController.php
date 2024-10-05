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
    // Fetch all feed posts
    public function index()
    {
        $posts = FeedPost::with(['doctor', 'comments', 'likes', 'saves'])->get();
        Log::info('Fetched all feed posts');
        return response()->json($posts);
    }

    public function show($id)
    {
        $post = FeedPost::with(['comments.doctor', 'likes', 'saves'])->findOrFail($id);
        return response()->json($post);
    }

    public function getFeedPosts()
    {
        $doctorId = auth()->id(); // Get the authenticated doctor's ID

        $feedPosts = FeedPost::with(['doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired']) // Load related doctor, comments, and likes
        ->withCount(['likes', 'comments']) // Count likes and comments
        ->with([
            'saves' => function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId); // Filter to check if the post is saved by the current doctor
            }
        ])
            ->paginate(10); // Paginate by 10 posts

        // Map through the paginated results and add a custom 'is_saved' field
        $feedPosts->getCollection()->transform(function ($post) use ($doctorId) {
            $post->is_saved = $post->saves->isNotEmpty(); // Check if the authenticated doctor has saved this post
            unset($post->saves); // Remove the saves relationship after checking
            return $post;
        });

        return response()->json($feedPosts);
    }

    public function getPostById($id)
    {
        $doctorId = auth()->id();

        // Fetch the post by its ID along with comments sorted by created_at in descending order
        $post = FeedPost::with([
            'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',          // Include the author of the post
            'comments' => function($query) {
                $query->orderBy('created_at', 'desc')->paginate(10);
            },
            'comments.doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired', // Include the doctor who made the comments
        ])->withCount(['likes', 'comments']) // Count likes and comments
            ->with([
                'saves' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId); // Filter to check if the post is saved by the current doctor
                }
            ])->findOrFail($id);  // Use findOrFail to return 404 if not found

        return response()->json($post);
    }


    public function getPostLikes($postId)
    {
        // Retrieve the post by ID and paginate likes with the related doctor who liked the post
        $likes = FeedPostLike::where('feed_post_id', $postId)
            ->with(['doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired']) // Eager load doctor details
            ->paginate(10); // Paginate the results (10 likes per page)

        return response()->json($likes);
    }

    public function getPostComments($postId)
    {
        // Retrieve the post by ID and paginate comments with the related doctor who commented
        $comments = FeedPostComment::where('feed_post_id', $postId)
            ->with(['doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired']) // Eager load doctor details
            ->paginate(10); // Paginate the results (10 comments per page)

        return response()->json($comments);
    }


    // Create a new post
    public function store(Request $request)
    {
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
        return response()->json($post, 201);
    }

    // Update a post
    public function update(Request $request, $id)
    {
        $post = FeedPost::findOrFail($id);

        if ($post->doctor_id !== Auth::id()) {
            Log::warning("Unauthorized update attempt by doctor " . Auth::id());
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'content' => 'required|string|max:1000',
            'media_type' => 'nullable|string',
            'media_path' => 'nullable|string',
            'visibility' => 'required|string|in:Public,Friends,Only Me',
        ]);

        $post->update($validatedData);
        Log::info("Post updated by doctor " . Auth::id());

        return response()->json($post);
    }

    // Delete a post
    public function destroy($id)
    {
        $post = FeedPost::findOrFail($id);

        if ($post->doctor_id !== Auth::id()) {
            Log::warning("Unauthorized delete attempt by doctor " . Auth::id());
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $post->delete();
        Log::info("Post deleted by doctor " . Auth::id());

        return response()->json(['message' => 'Post deleted successfully']);
    }

    // Like a post
    public function likePost($postId)
    {
        $post = FeedPost::findOrFail($postId);

        $like = FeedPostLike::firstOrCreate([
            'feed_post_id' => $postId,
            'doctor_id' => Auth::id(),
        ]);

        Log::info("Post liked by doctor " . Auth::id());

        return response()->json($like);
    }

    // Unlike a post
    public function unlikePost($postId)
    {
        $post = FeedPost::findOrFail($postId);

        $like = FeedPostLike::where('feed_post_id', $postId)
            ->where('doctor_id', Auth::id())
            ->first();

        if ($like) {
            $like->delete();
            Log::info("Post unliked by doctor " . Auth::id());
            return response()->json(['message' => 'Post unliked successfully']);
        }

        return response()->json(['error' => 'Like not found'], 404);
    }

    // Save a post
    public function savePost($postId)
    {
        $post = FeedPost::findOrFail($postId);

        $save = FeedSaveLike::firstOrCreate([
            'feed_post_id' => $postId,
            'doctor_id' => Auth::id(),
        ]);

        Log::info("Post saved by doctor " . Auth::id());

        return response()->json($save);
    }

    // Remove saved post
    public function unsavePost($postId)
    {
        $save = FeedSaveLike::where('feed_post_id', $postId)
            ->where('doctor_id', Auth::id())
            ->first();

        if ($save) {
            $save->delete();
            Log::info("Post unsaved by doctor " . Auth::id());
            return response()->json(['message' => 'Post unsaved successfully']);
        }

        return response()->json(['error' => 'Save not found'], 404);
    }

    // Add a comment to a post
    public function addComment(Request $request, $postId)
    {
        $validatedData = $request->validate([
            'comment' => 'required|string|max:500',
        ]);

        $comment = FeedPostComment::create([
            'feed_post_id' => $postId,
            'doctor_id' => Auth::id(),
            'comment' => $validatedData['comment'],
        ]);

        Log::info("Comment added to post by doctor " . Auth::id());

        return response()->json($comment);
    }

    // Delete a comment
    public function deleteComment($commentId)
    {
        $comment = FeedPostComment::findOrFail($commentId);

        if ($comment->doctor_id !== Auth::id()) {
            Log::warning("Unauthorized comment delete attempt by doctor " . Auth::id());
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();
        Log::info("Comment deleted by doctor " . Auth::id());

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
