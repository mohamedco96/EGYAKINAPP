<?php
/**
 * GroupController handles the management of groups including creation, updating, deletion,
 * member management, and fetching group details.
 * 
 * Methods:
 * - __construct(MainController $mainController): Initialize the controller with a MainController instance.
 * - authorizeOwner(Group $group): Authorize the owner of the group.
 * - create(Request $request): Create a new group.
 * - update(Request $request, int $id): Update an existing group.
 * - delete(int $id): Delete a group.
 * - inviteMember(Request $request, int $groupId): Invite a member to the group.
 * - handleInvitation(Request $request, int $groupId): Accept or decline a group invitation.
 * - show(int $id): Get group details, including members and privacy settings.
 * - removeMember(Request $request, int $groupId): Remove a member from the group.
 * - searchMembers(Request $request, int $groupId): Search for members in a group.
 * - fetchMembers(int $groupId): Fetch community members.
 * - fetchGroupDetailsWithPosts(int $groupId): Fetch group details along with posts.
 * - joinGroup(int $groupId): Join a group.
 * - leaveGroup(int $groupId): Leave a group.
 * - fetchMyGroups(): Fetch groups owned by the authenticated user.
 * - fetchAllGroups(): Fetch all groups.
 */

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\MainController;
use App\Models\FeedPost;

class GroupController extends Controller
{
    protected $mainController;

    public function __construct(MainController $mainController)
    {
        $this->mainController = $mainController;
    }

        /**
     * Authorize the owner of the group.
     * 
     * @param \App\Models\Group $group
     * @throws \App\Exceptions\UnauthorizedException
     */
    protected function authorizeOwner(Group $group)
    {
        if (Auth::id() !== $group->owner_id) {
            Log::warning('Unauthorized action attempt', [
                'user_id' => Auth::id(),
                'group_id' => $group->id
            ]);
            throw new UnauthorizedException('Unauthorized');
        }
    }

    /**
     * Create a new group.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'header_picture' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:20480',  // Validate file input for images
            'group_image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:20480',     // Validate file input for images
            'privacy' => 'in:public,private'
        ]);

        // Initialize media paths as null
        $headerPicturePath = null;
        $groupImagePath = null;

        // Check if header_picture is provided and process the upload
        if ($request->hasFile('header_picture')) {
            $headerPicture = $request->file('header_picture');
            $uploadResponse = $this->mainController->uploadImageAndVideo($headerPicture, 'header_pictures');

            // Check if the upload was successful
            if ($uploadResponse->getData()->value) {
                $headerPicturePath = $uploadResponse->getData()->image;  // Store the uploaded image URL
            } else {
                // Handle upload error
                return response()->json([
                    'value' => false,
                    'message' => 'Header picture upload failed.'
                ], 500);
            }
        }

        // Check if group_image is provided and process the upload
        if ($request->hasFile('group_image')) {
            $groupImage = $request->file('group_image');
            $uploadResponse = $this->mainController->uploadImageAndVideo($groupImage, 'group_images');

            // Check if the upload was successful
            if ($uploadResponse->getData()->value) {
                $groupImagePath = $uploadResponse->getData()->image;  // Store the uploaded image URL
            } else {
                // Handle upload error
                return response()->json([
                    'value' => false,
                    'message' => 'Group image upload failed.'
                ], 500);
            }
        }

        // Create the group and assign the authenticated user as the owner
        $group = Group::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'header_picture' => $headerPicturePath,  // Save uploaded header picture path
            'group_image' => $groupImagePath,        // Save uploaded group image path
            'privacy' => $validated['privacy'],
            'owner_id' => Auth::id(),
        ]);

        // Log the creation of a new group
        Log::info('Group created', [
            'group_id' => $group->id,
            'owner_id' => Auth::id(),
            'name' => $group->name
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $group,
            'message' => 'Group created successfully'
        ], 201);
    }

    /**
     * Update an existing group.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Find the group or fail if not found
        $group = Group::findOrFail($id);

        // Check if the authenticated user is the group owner
        $this->authorizeOwner($group);


        // Validate the incoming request data
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'string',
            'header_picture' => 'file|mimes:jpeg,png,jpg,gif|max:20480',  // Validate file input for images
            'group_image' => 'file|mimes:jpeg,png,jpg,gif|max:20480',     // Validate file input for images
            'privacy' => 'in:public,private'
        ]);

        // Initialize media paths as null
        $headerPicturePath = $group->header_picture; // Keep the existing value if not updated
        $groupImagePath = $group->group_image;       // Keep the existing value if not updated

        // Check if a new header picture is provided and process the upload
        if ($request->hasFile('header_picture')) {
            $headerPicture = $request->file('header_picture');
            $uploadResponse = $this->mainController->uploadImageAndVideo($headerPicture, 'header_pictures');

            // Check if the upload was successful
            if ($uploadResponse->getData()->value) {
                $headerPicturePath = $uploadResponse->getData()->image;  // Update the uploaded image URL
            } else {
                // Handle upload error
                return response()->json([
                    'value' => false,
                    'message' => 'Header picture upload failed.'
                ], 500);
            }
        }

        // Check if a new group image is provided and process the upload
        if ($request->hasFile('group_image')) {
            $groupImage = $request->file('group_image');
            $uploadResponse = $this->mainController->uploadImageAndVideo($groupImage, 'group_images');

            // Check if the upload was successful
            if ($uploadResponse->getData()->value) {
                $groupImagePath = $uploadResponse->getData()->image;  // Update the uploaded image URL
            } else {
                // Handle upload error
                return response()->json([
                    'value' => false,
                    'message' => 'Group image upload failed.'
                ], 500);
            }
        }

        // Update the group details
        $group->update([
            'name' => $validated['name'] ?? $group->name,
            'description' => $validated['description'] ?? $group->description,
            'header_picture' => $headerPicturePath,  // Update header picture path if changed
            'group_image' => $groupImagePath,        // Update group image path if changed
            'privacy' => $validated['privacy'] ?? $group->privacy,
        ]);

        // Log the update action
        Log::info('Group updated', [
            'group_id' => $group->id,
            'updated_by' => Auth::id(),
            'changes' => $validated  // Log changes made
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $group,
            'message' => 'Group updated successfully'
        ], 200);
    }

    /**
     * Delete a group.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        // Find the group or fail if not found
        $group = Group::findOrFail($id);

        // Check if the authenticated user is the group owner
        $this->authorizeOwner($group);


        // Delete the group
        $group->delete();

        // Log the deletion
        Log::info('Group deleted', [
            'group_id' => $id,
            'deleted_by' => Auth::id()
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'message' => 'Group deleted successfully'
        ], 200);
    }

    /**
     * Invite a member to the group.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteMember(Request $request, $groupId)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id'
        ]);

        // Find the group or fail if not found
        $group = Group::findOrFail($groupId);

        // Check if the authenticated user is the group owner
        $this->authorizeOwner($group);


        // Invite the user to the group (attach the user to the group members with status "invited")
        $group->doctors()->attach($validated['doctor_id'], ['status' => 'invited']);

        // Log the invitation
        Log::info('User invited to group', [
            'group_id' => $groupId,
            'invited_doctor_id' => $validated['doctor_id'],
            'invited_by' => Auth::id()
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'message' => 'Invitation sent successfully'
        ], 200);
    }

    /**
     * Accept or decline a group invitation.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleInvitation(Request $request, $groupId)
    {
        // Validate the request to ensure status is either 'accepted' or 'declined'
        $validated = $request->validate([
            'status' => 'required|in:accepted,declined'
        ]);

        // Find the group or fail if not found
        $group = Group::findOrFail($groupId);
        $userId = Auth::id();

        // Update the invitation status for the authenticated user
        $group->doctors()->updateExistingPivot($userId, ['status' => $validated['status']]);

        // Log the invitation status change
        Log::info('Invitation status updated', [
            'group_id' => $groupId,
            'doctor_id' => $userId,
            'status' => $validated['status']
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'message' => 'Invitation status updated successfully'
        ], 200);
    }

    /**
     * Get group details, including members and privacy settings.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Retrieve the group with its members
        $group = Group::with('doctors')->findOrFail($id);

        // Log group retrieval
        Log::info('Group details retrieved', [
            'group_id' => $id,
            'retrieved_by' => Auth::id()
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $group,
            'message' => 'Group details retrieved successfully'
        ], 200);
    }

    /**
     * Remove a member from the group.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember(Request $request, $groupId)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id'
        ]);

        $doctorId = $validated['doctor_id'];

        // Find the group or fail if not found
        $group = Group::findOrFail($groupId);

        // Check if the authenticated user is the group owner
        $this->authorizeOwner($group);


        // Check if the member exists in the group
        if (!$group->doctors()->where('doctor_id', $doctorId)->exists()) {
            return response()->json([
                'value' => false,
                'message' => 'Member not found in the group'
            ], 404);
        }

        // Remove the member from the group
        $group->doctors()->detach($doctorId);

        // Log the removal
        Log::info('Member removed from group', [
            'group_id' => $groupId,
            'removed_doctor_id' => $doctorId,
            'removed_by' => Auth::id()
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'message' => 'Member removed successfully'
        ], 200);
    }

    /**
     * Search for members in a group.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchMembers(Request $request, $groupId)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'query' => 'required|string|max:255'
        ]);

        // Find the group or fail if not found
        $group = Group::findOrFail($groupId);

        // Search for members in the group based on the query
        $members = $group->doctors()
            ->where('name', 'like', '%' . $validated['query'] . '%')
            ->orWhere('email', 'like', '%' . $validated['query'] . '%')
            ->get();

        // Log the search action
        Log::info('Group members searched', [
            'group_id' => $groupId,
            'searched_by' => Auth::id(),
            'query' => $validated['query']
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $members,
            'message' => 'Members search results'
        ], 200);
    }

    /**
     * Fetch community members.
     * 
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchMembers($groupId)
    {
        // Find the group or fail if not found
        $group = Group::findOrFail($groupId);

        // Retrieve the members of the group
        $members = $group->doctors()->get();

        // Log the action
        Log::info('Community members fetched', [
            'group_id' => $groupId,
            'fetched_by' => Auth::id()
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $members,
            'message' => 'Community members fetched successfully'
        ], 200);
    }

    /**
     * Fetch group details along with posts.
     * 
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchGroupDetailsWithPosts($groupId)
    {
        // Find the group or fail if not found
        $group = Group::with('posts')->findOrFail($groupId);

        // Check if the group has posts
        if ($group->posts->isEmpty()) {
            // Log the action
            Log::info('Group details fetched but no posts found', [
                'group_id' => $groupId,
                'fetched_by' => Auth::id()
            ]);

            // Return response indicating no posts found
            return response()->json([
                'value' => true,
                'data' => $group,
                'message' => 'Group details fetched successfully, but no posts found'
            ], 200);
        }

        // Log the action
        Log::info('Group details with posts fetched', [
            'group_id' => $groupId,
            'fetched_by' => Auth::id()
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $group,
            'message' => 'Group details with posts fetched successfully'
        ], 200);
    }

    /**
     * Join a group.
     * 
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function joinGroup($groupId)
    {
        // Find the group or fail if not found
        $group = Group::findOrFail($groupId);
        $userId = Auth::id();

        // Check if the user is already a member of the group
        if ($group->doctors()->where('doctor_id', $userId)->exists()) {
            return response()->json([
                'value' => false,
                'message' => 'You are already a member of this group'
            ], 400);
        }

        // Add the user to the group members with status "joined"
        $group->doctors()->attach($userId, ['status' => 'joined']);

        // Log the join action
        Log::info('User joined group', [
            'group_id' => $groupId,
            'doctor_id' => $userId
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'message' => 'Joined group successfully'
        ], 200);
    }

    /**
     * Leave a group.
     * 
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaveGroup($groupId)
    {
        // Find the group or fail if not found
        $group = Group::findOrFail($groupId);
        $userId = Auth::id();

        // Check if the user is a member of the group
        if (!$group->doctors()->where('doctor_id', $userId)->exists()) {
            return response()->json([
                'value' => false,
                'message' => 'You are not a member of this group'
            ], 400);
        }

        // Remove the user from the group members
        $group->doctors()->detach($userId);

        // Log the leave action
        Log::info('User left group', [
            'group_id' => $groupId,
            'doctor_id' => $userId
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'message' => 'Left group successfully'
        ], 200);
    }

    /**
     * Fetch groups owned by the authenticated user.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchMyGroups()
    {
        $userId = Auth::id();

        // Retrieve groups owned by the authenticated user
        $groups = Group::where('owner_id', $userId)->get();

        // Log the action
        Log::info('User groups fetched', [
            'owner_id' => $userId
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $groups,
            'message' => 'User groups fetched successfully'
        ], 200);
    }
    
    /**
     * Fetch all groups.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchAllGroups()
    {
        // Retrieve all groups
        $groups = Group::all();

        // Log the action
        Log::info('All groups fetched', [
            'fetched_by' => Auth::id()
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $groups,
            'message' => 'All groups fetched successfully'
        ], 200);
    }
}
