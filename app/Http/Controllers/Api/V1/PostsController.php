<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Posts\Controllers\PostsController as ModulePostsController;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        return $this->postsController->store($request);
    }

    public function show($id)
    {
        return $this->postsController->show($id);
    }

    public function update(Request $request, $id)
    {
        return $this->postsController->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->postsController->destroy($id);
    }
}
