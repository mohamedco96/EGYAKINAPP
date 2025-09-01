<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Posts\Controllers\PostsController as ModulePostsController;
use App\Modules\Posts\Requests\StorePostsRequest;
use App\Modules\Posts\Requests\UpdatePostsRequest;

class PostsController extends Controller
{
    protected $postsController;

    public function __construct(ModulePostsController $postsController)
    {
        $this->postsController = $postsController;
    }

    public function index()
    {
        return $this->postsController->index();
    }

    public function store(StorePostsRequest $request)
    {
        return $this->postsController->store($request);
    }

    public function show($id)
    {
        return $this->postsController->show($id);
    }

    public function update(UpdatePostsRequest $request, $id)
    {
        return $this->postsController->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->postsController->destroy($id);
    }
}
