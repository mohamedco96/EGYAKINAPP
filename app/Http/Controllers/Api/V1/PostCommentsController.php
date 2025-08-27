<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\Posts\Controllers\PostCommentsController as ModulePostCommentsController;
use Illuminate\Http\Request;

class PostCommentsController extends Controller
{
    protected $postCommentsController;

    public function __construct(ModulePostCommentsController $postCommentsController)
    {
        $this->postCommentsController = $postCommentsController;
    }

    public function index()
    {
        return $this->postCommentsController->index();
    }

    public function store(Request $request)
    {
        return $this->postCommentsController->store($request);
    }

    public function show($id)
    {
        return $this->postCommentsController->show($id);
    }

    public function update(Request $request, $id)
    {
        return $this->postCommentsController->update($request, $id);
    }

    public function destroy($id)
    {
        return $this->postCommentsController->destroy($id);
    }
}
