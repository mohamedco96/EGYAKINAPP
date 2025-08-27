<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FeedPostController as BaseFeedPostController;
use Illuminate\Http\Request;

class FeedPostController extends Controller
{
    protected $feedPostController;

    public function __construct(BaseFeedPostController $feedPostController)
    {
        $this->feedPostController = $feedPostController;
    }

    public function getFeedPosts()
    {
        return $this->feedPostController->getFeedPosts();
    }

    public function store(Request $request)
    {
        return $this->feedPostController->store($request);
    }

    public function update(Request $request, $id)
    {
        return $this->feedPostController->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->feedPostController->destroy($id);
    }

    public function likeOrUnlikePost(Request $request, $id)
    {
        return $this->feedPostController->likeOrUnlikePost($request, $id);
    }

    public function saveOrUnsavePost(Request $request, $id)
    {
        return $this->feedPostController->saveOrUnsavePost($request, $id);
    }

    public function addComment(Request $request, $id)
    {
        return $this->feedPostController->addComment($request, $id);
    }

    public function deleteComment($id)
    {
        return $this->feedPostController->deleteComment($id);
    }

    public function getPostLikes($postId)
    {
        return $this->feedPostController->getPostLikes($postId);
    }

    public function getPostComments($postId)
    {
        return $this->feedPostController->getPostComments($postId);
    }

    public function getPostById($id)
    {
        return $this->feedPostController->getPostById($id);
    }

    public function likeOrUnlikeComment(Request $request, $commentId)
    {
        return $this->feedPostController->likeOrUnlikeComment($request, $commentId);
    }

    public function trending()
    {
        return $this->feedPostController->trending();
    }

    public function searchHashtags(Request $request)
    {
        return $this->feedPostController->searchHashtags($request);
    }

    public function getPostsByHashtag($hashtag)
    {
        return $this->feedPostController->getPostsByHashtag($hashtag);
    }

    public function searchPosts(Request $request)
    {
        return $this->feedPostController->searchPosts($request);
    }

    public function getDoctorPosts($doctorId)
    {
        return $this->feedPostController->getDoctorPosts($doctorId);
    }

    public function getDoctorSavedPosts($doctorId)
    {
        return $this->feedPostController->getDoctorSavedPosts($doctorId);
    }
}
