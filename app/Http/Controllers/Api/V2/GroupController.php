<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GroupController as BaseGroupController;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    protected $groupController;

    public function __construct(BaseGroupController $groupController)
    {
        $this->groupController = $groupController;
    }

    public function create(Request $request)
    {
        return $this->groupController->create($request);
    }

    public function update(Request $request, $id)
    {
        return $this->groupController->update($request, $id);
    }

    public function delete($id)
    {
        return $this->groupController->delete($id);
    }

    public function inviteMember(Request $request, $groupId)
    {
        return $this->groupController->inviteMember($request, $groupId);
    }

    public function handleInvitation(Request $request, $groupId)
    {
        return $this->groupController->handleInvitation($request, $groupId);
    }

    public function show($id)
    {
        return $this->groupController->show($id);
    }

    public function removeMember(Request $request, $groupId)
    {
        return $this->groupController->removeMember($request, $groupId);
    }

    public function searchMembers(Request $request, $groupId)
    {
        return $this->groupController->searchMembers($request, $groupId);
    }

    public function fetchMembers($groupId)
    {
        return $this->groupController->fetchMembers($groupId);
    }

    public function fetchGroupDetailsWithPosts($groupId)
    {
        return $this->groupController->fetchGroupDetailsWithPosts($groupId);
    }

    public function joinGroup(Request $request, $groupId)
    {
        return $this->groupController->joinGroup($groupId);
    }

    public function leaveGroup(Request $request, $groupId)
    {
        return $this->groupController->leaveGroup($groupId);
    }

    public function fetchMyGroups()
    {
        return $this->groupController->fetchMyGroups();
    }

    public function fetchAllGroups()
    {
        return $this->groupController->fetchAllGroups();
    }

    public function fetchLatestGroupsWithRandomPosts()
    {
        return $this->groupController->fetchLatestGroupsWithRandomPosts();
    }

    public function getDoctorInvitations($doctorId)
    {
        return $this->groupController->getDoctorInvitations($doctorId);
    }

    public function getGroupInvitations($groupId)
    {
        return $this->groupController->getGroupInvitations($groupId);
    }
}
