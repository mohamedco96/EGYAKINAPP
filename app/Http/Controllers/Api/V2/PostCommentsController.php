<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\PostCommentsController as V1PostCommentsController;
use App\Modules\Posts\Requests\StorePostCommentsRequest;
use App\Modules\Posts\Requests\UpdatePostCommentsRequest;

class PostCommentsController extends Controller
{
    protected $postCommentsController;

    public function __construct(V1PostCommentsController $postCommentsController)
    {
        $this->postCommentsController = $postCommentsController;
    }

    public function index()
    {
        return $this->postCommentsController->index();
    }

    public function store(StorePostCommentsRequest $request)
    {
        return $this->postCommentsController->store($request);
    }

    public function show($id)
    {
        return $this->postCommentsController->show($id);
    }

    public function update(UpdatePostCommentsRequest $request, $id)
    {
        return $this->postCommentsController->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->postCommentsController->destroy($id);
    }
}
