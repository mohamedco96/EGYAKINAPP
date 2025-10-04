<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MainController as BaseMainController;
use Illuminate\Http\Request;

class MainController extends Controller
{
    protected $mainController;

    public function __construct(BaseMainController $mainController)
    {
        $this->mainController = $mainController;
    }

    public function uploadImage(Request $request)
    {
        return $this->mainController->uploadImage($request);
    }

    public function uploadVideo(Request $request)
    {
        return $this->mainController->uploadVideo($request);
    }
}
