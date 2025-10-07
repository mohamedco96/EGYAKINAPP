<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\NotificationController as V1NotificationController;
use App\Modules\Notifications\Requests\StoreNotificationRequest;
use App\Modules\Notifications\Requests\UpdateNotificationRequest;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationController;

    public function __construct(V1NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }

    public function store(StoreNotificationRequest $request)
    {
        return $this->notificationController->store($request);
    }

    public function index()
    {
        return $this->notificationController->index();
    }

    public function showNew(Request $request)
    {
        return $this->notificationController->showNew($request);
    }

    public function update(UpdateNotificationRequest $request, $id)
    {
        return $this->notificationController->update($request, $id);
    }

    public function markAllAsRead(Request $request)
    {
        return $this->notificationController->markAllAsRead($request);
    }

    public function destroy($id)
    {
        return $this->notificationController->destroy($id);
    }

    public function storeFCM(Request $request)
    {
        return $this->notificationController->storeFCM($request);
    }

    public function sendAllPushNotification(Request $request)
    {
        return $this->notificationController->sendAllPushNotification($request);
    }
}
