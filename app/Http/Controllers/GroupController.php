<?php

namespace App\Http\Controllers;

use App\Models\FeedPost;
use App\Models\Group;
use App\Models\User;
use App\Modules\Notifications\Models\AppNotification;
use App\Modules\Notifications\Models\FcmToken;
use App\Modules\Notifications\Services\NotificationService;
use App\Traits\FormatsUserName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroupController extends Controller
{
    use FormatsUserName;

    protected $mainController;

    protected $notificationService;

    public function __construct(MainController $mainController, NotificationService $notificationService)
    {
        $this->mainController = $mainController;
        $this->notificationService = $notificationService;
    }

    /**
     * Authorize the owner of the group.
     *
     * @throws \App\Exceptions\UnauthorizedException
     */
    protected function authorizeOwner(Group $group)
    {
        if (Auth::id() !== $group->owner_id) {
            Log::warning('Unauthorized action attempt', [
                'user_id' => Auth::id(),
                'group_id' => $group->id,
            ]);
            throw new UnauthorizedException('Unauthorized');
        }
    }

    /**
     * Check if user can access a private group (either member or owner).
     *
     * @param  int|null  $userId
     * @return bool
     */
    protected function canAccessPrivateGroup(Group $group, $userId = null)
    {
        $userId = $userId ?? Auth::id();

        // Public groups are accessible to everyone
        if ($group->privacy === 'public') {
            return true;
        }

        // Owner can always access their own group
        if ($group->owner_id === $userId) {
            return true;
        }

        // Check if user is a joined member of the private group
        $memberStatus = DB::table('group_user')
            ->where('group_id', $group->id)
            ->where('doctor_id', $userId)
            ->value('status');

        return $memberStatus === 'joined';
    }

    /**
     * Validate access to private group and return appropriate response if unauthorized.
     *
     * @param  string  $action
     * @param  int|null  $userId
     * @return \Illuminate\Http\JsonResponse|null
     */
    protected function validatePrivateGroupAccess(Group $group, $action = 'access', $userId = null)
    {
        $userId = $userId ?? Auth::id();

        if (! $this->canAccessPrivateGroup($group, $userId)) {
            Log::warning('Unauthorized private group access attempt', [
                'user_id' => $userId,
                'group_id' => $group->id,
                'action' => $action,
                'group_privacy' => $group->privacy,
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.private_group_access_denied'),
            ], 403);
        }

        return null; // Access granted
    }

    /**
     * Create a new group.
     *
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
            'privacy' => 'in:public,private',
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
                    'message' => __('api.header_picture_upload_failed'),
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
                    'message' => __('api.group_image_upload_failed'),
                ], 500);
            }
        }

        try {
            DB::beginTransaction();

            // Create the group and assign the authenticated user as the owner
            $group = Group::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'header_picture' => $headerPicturePath,  // Save uploaded header picture path
                'group_image' => $groupImagePath,        // Save uploaded group image path
                'privacy' => $validated['privacy'],
                'owner_id' => Auth::id(),
            ]);

            $group->doctors()->attach(Auth::id(), ['status' => 'joined']);

            DB::commit();

            // Log the creation of a new group
            Log::info('Group created', [
                'group_id' => $group->id,
                'owner_id' => Auth::id(),
                'name' => $group->name,
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'data' => $group,
                'message' => __('api.group_created_successfully'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log the error
            Log::error('Error creating group', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.error_creating_group'),
            ], 500);
        }
    }

    /**
     * Update an existing group.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Find the group or fail if not found
            $group = Group::findOrFail($id);

            // Check if the authenticated user is the group owner
            //$this->authorizeOwner($group);

            // Validate the incoming request data with stricter rules
            $validated = $request->validate([
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'header_picture' => 'nullable|file|mimes:jpeg,png,jpg,gif',
                'group_image' => 'nullable|file|mimes:jpeg,png,jpg,gif',
                'privacy' => 'in:public,private',
            ]);

            // Initialize media paths as null
            $headerPicturePath = $group->header_picture; // Keep the existing value if not updated
            $groupImagePath = $group->group_image;       // Keep the existing value if not updated

            try {
                DB::beginTransaction();

                // Check if a new header picture is provided and process the upload
                if ($request->hasFile('header_picture')) {
                    $headerPicture = $request->file('header_picture');
                    $uploadResponse = $this->mainController->uploadImageAndVideo($headerPicture, 'header_pictures');

                    // Check if the upload was successful
                    if ($uploadResponse->getData()->value) {
                        $headerPicturePath = $uploadResponse->getData()->image;  // Update the uploaded image URL
                    } else {
                        throw new \Exception(__('api.header_picture_upload_failed'));
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
                        throw new \Exception(__('api.group_image_upload_failed'));
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

                DB::commit();

                // Log the update action with sanitized data
                Log::info('Group updated', [
                    'group_id' => $group->id,
                    'updated_by' => Auth::id(),
                    'changes' => array_intersect_key($validated, array_flip(['name', 'privacy'])), // Only log non-sensitive fields
                ]);

                // Return success response
                return response()->json([
                    'value' => true,
                    'data' => $group,
                    'message' => __('api.group_updated_successfully'),
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $id,
                'updated_by' => Auth::id(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors
            Log::warning('Group update validation failed', [
                'group_id' => $id,
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error updating group', [
                'group_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.error_updating_group'),
            ], 500);
        }
    }

    /**
     * Delete a group.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        try {
            // Find the group or fail if not found
            $group = Group::findOrFail($id);

            // Check if the authenticated user is the group owner
            //$this->authorizeOwner($group);

            // Delete the group
            $group->delete();

            // Remove the associated notifications using trait method
            $this->cleanupGroupNotifications($id);

            // Log the deletion
            Log::info('Group deleted', [
                'group_id' => $id,
                'deleted_by' => Auth::id(),
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'message' => __('api.group_deleted_successfully'),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $id,
                'deleted_by' => Auth::id(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        }
    }

    /**
     * Invite a member to the group.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function inviteMember(Request $request, $groupId)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'doctor_ids' => 'required|array', // Ensure doctor_ids is an array
            'doctor_ids.*' => 'exists:users,id', // Ensure each doctor_id exists in the users table
        ]);

        // Find the group or fail if not found
        $group = Group::find($groupId);

        if (! $group) {
            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        }

        // Check if the authenticated user is the group owner
        //$this->authorizeOwner($group);

        // For private groups, only owners and existing members can invite others
        if ($group->privacy === 'private') {
            $accessValidation = $this->validatePrivateGroupAccess($group, 'invite_member');
            if ($accessValidation) {
                return $accessValidation;
            }
        }

        try {
            DB::beginTransaction();

            // Iterate over each doctor_id in the list
            foreach ($validated['doctor_ids'] as $doctorId) {
                // Check if the doctor is already invited or a member of the group
                $existingStatus = $group->doctors()->where('doctor_id', $doctorId)->value('status');

                if ($existingStatus === 'joined') {
                    // Log the attempt to invite an existing member
                    Log::info('Doctor is already a member of the group', [
                        'group_id' => $groupId,
                        'doctor_id' => $doctorId,
                        'attempted_by' => Auth::id(),
                    ]);
                } elseif ($existingStatus === 'invited' || $existingStatus === 'pending') {
                    // Log the attempt to invite an already invited member
                    Log::info('Doctor is already invited to the group', [
                        'group_id' => $groupId,
                        'doctor_id' => $doctorId,
                        'attempted_by' => Auth::id(),
                    ]);
                } elseif ($existingStatus === 'declined') {
                    // Update the status to invited again
                    $group->doctors()->updateExistingPivot($doctorId, ['status' => 'invited']);

                    if ($doctorId !== Auth::id()) {
                        AppNotification::createLocalized([
                            'doctor_id' => $doctorId,
                            'type' => 'group_invitation',
                            'type_id' => $groupId,
                            'localization_key' => 'api.clean_notification_group_invitation',
                            'localization_params' => ['name' => $this->formatUserName(Auth::user())],
                            'type_doctor_id' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        Log::info('Notification sent to group owner ID: '.$group->owner_id.' for group ID: '.$groupId);

                        // Get FCM tokens for push notification
                        $tokens = FcmToken::where('doctor_id', $doctorId)
                            ->pluck('token')
                            ->toArray();

                        if (! empty($tokens)) {
                            $this->notificationService->sendPushNotification(
                                __('api.new_invitation_created'),
                                __('api.clean_doctor_invited_to_group', ['name' => ucfirst($this->formatUserName(Auth::user()))]),
                                $tokens
                            );
                        }
                    }

                    // Log the re-invitation
                    Log::info('Doctor re-invited to the group', [
                        'group_id' => $groupId,
                        'doctor_id' => $doctorId,
                        'invited_by' => Auth::id(),
                    ]);
                } elseif ($existingStatus === 'accepted') {
                    // Log the attempt to invite an already invited member
                    Log::info('Doctor is already accepted the invitation', [
                        'group_id' => $groupId,
                        'doctor_id' => $doctorId,
                        'attempted_by' => Auth::id(),
                    ]);
                } else {
                    // Invite the user to the group (attach the user to the group members with status "invited")
                    $group->doctors()->attach($doctorId, ['status' => 'invited']);

                    // Log the invitation
                    Log::info('User invited to group', [
                        'group_id' => $groupId,
                        'invited_doctor_id' => $doctorId,
                        'invited_by' => Auth::id(),
                    ]);

                    if ($doctorId !== Auth::id()) {
                        AppNotification::createLocalized([
                            'doctor_id' => $doctorId,
                            'type' => 'group_invitation',
                            'type_id' => $groupId,
                            'localization_key' => 'api.clean_notification_group_invitation',
                            'localization_params' => ['name' => $this->formatUserName(Auth::user())],
                            'type_doctor_id' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        Log::info('Notification sent to group owner ID: '.$group->owner_id.' for group ID: '.$groupId);

                        // Get FCM tokens for push notification
                        $tokens = FcmToken::where('doctor_id', $doctorId)
                            ->pluck('token')
                            ->toArray();

                        if (! empty($tokens)) {
                            $this->notificationService->sendPushNotification(
                                __('api.new_invitation_created'),
                                __('api.clean_doctor_invited_to_group', ['name' => ucfirst($this->formatUserName(Auth::user()))]),
                                $tokens
                            );
                        }
                    }
                }
            }

            DB::commit();

            // Return success response with details of successful and failed invites
            return response()->json([
                'value' => true,
                'message' => __('api.invitations_processed'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log the error
            Log::error('Error processing group invitations', [
                'error' => $e->getMessage(),
                'group_id' => $groupId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.error_processing_invitations'),
            ], 500);
        }
    }

    /**
     * Accept or decline a group invitation.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleInvitation(Request $request, $groupId)
    {
        try {
            // Validate the request with stricter rules
            $validated = $request->validate([
                'status' => 'required|in:accepted,declined',
                'invitation_id' => 'required|exists:group_user,id',
            ]);

            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);
            $userId = Auth::id();

            // Find the invitation and check if it belongs to the authenticated user
            $invitation = DB::table('group_user')
                ->where('id', $validated['invitation_id'])
                ->where('group_id', $groupId)
                ->whereIn('status', ['invited', 'pending'])
                ->first();

            if (! $invitation) {
                Log::error('Invalid invitation', [
                    'group_id' => $groupId,
                    'doctor_id' => $userId,
                    'invitation_id' => $validated['invitation_id'],
                ]);

                return response()->json([
                    'value' => false,
                    'message' => __('api.invalid_invitation'),
                ], 400);
            }

            try {
                DB::beginTransaction();

                // Update the invitation status
                $newStatus = $validated['status'] === 'accepted' ? 'joined' : 'declined';
                DB::table('group_user')
                    ->where('id', $validated['invitation_id'])
                    ->update([
                        'status' => $newStatus,
                        'updated_at' => now(),
                    ]);

                if ($validated['status'] === 'accepted') {
                    // Send notification to group owner
                    if ($group->owner_id !== $userId) {
                        AppNotification::createLocalized([
                            'doctor_id' => $group->owner_id,
                            'type' => 'group_invitation_accepted',
                            'type_id' => $groupId,
                            'localization_key' => 'api.clean_notification_group_invitation_accepted',
                            'localization_params' => ['name' => $this->formatUserName(Auth::user())],
                            'type_doctor_id' => $userId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Send push notification
                        $tokens = FcmToken::where('doctor_id', $group->owner_id)
                            ->pluck('token')
                            ->toArray();

                        if (! empty($tokens)) {
                            $this->notificationService->sendPushNotification(
                                __('api.group_invitation_accepted'),
                                __('api.clean_doctor_accepted_invitation', ['name' => ucfirst($this->formatUserName(Auth::user()))]),
                                $tokens
                            );
                        }

                        Log::info('Notification sent to group owner', [
                            'owner_id' => $group->owner_id,
                            'group_id' => $groupId,
                        ]);
                    }
                }

                DB::commit();

                // Log the invitation status change
                Log::info('Invitation status updated', [
                    'group_id' => $groupId,
                    'doctor_id' => $userId,
                    'invitation_id' => $validated['invitation_id'],
                    'status' => $newStatus,
                ]);

                return response()->json([
                    'value' => true,
                    'message' => __('api.invitation_status_updated', ['status' => $validated['status']]),
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Group not found', [
                'group_id' => $groupId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Invitation handling validation failed', [
                'group_id' => $groupId,
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error handling invitation', [
                'group_id' => $groupId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.error_handling_invitation'),
            ], 500);
        }
    }

    /**
     * Get group details, including members and privacy settings.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Retrieve the group without loading the doctors relationship
            $group = Group::with(['owner' => function ($query) {
                $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
            }])
                ->findOrFail($id);

            // Check if the authenticated user is a member of the group and get their status
            $userId = Auth::id();

            // For private groups, validate access for detailed information
            if ($group->privacy === 'private' && ! $this->canAccessPrivateGroup($group, $userId)) {
                // Return limited group info for non-members of private groups
                return response()->json([
                    'value' => true,
                    'data' => [
                        'id' => $group->id,
                        'name' => $group->name,
                        'description' => $group->description,
                        'privacy' => $group->privacy,
                        'owner' => $group->owner,
                        'members_count' => null, // Hide member count for private groups
                        'user_status' => null,
                    ],
                    'message' => __('api.group_details_retrieved_successfully'),
                ], 200);
            }

            // Count the number of members in the group
            $group->members_count = (int) $group->doctors()->where('status', 'joined')->count();

            $userStatus = DB::table('group_user')
                ->where('group_id', $id)
                ->where('doctor_id', $userId)
                ->value('status');

            $group->user_status = $userStatus ?? null;

            // Log group retrieval
            Log::info('Group details retrieved', [
                'group_id' => $id,
                'retrieved_by' => $userId,
                'user_status' => $userStatus,
            ]);

            // Return success response with members count and user status
            return response()->json([
                'value' => true,
                'data' => $group,
                'message' => __('api.group_details_retrieved_successfully'),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $id,
                'retrieved_by' => Auth::id(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        }
    }

    /**
     * Remove a member from the group.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember(Request $request, $groupId)
    {
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'doctor_id' => 'required|exists:users,id',
            ]);

            $doctorId = $validated['doctor_id'];

            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);

            // Check if the authenticated user is the group owner
            //$this->authorizeOwner($group);

            // For private groups, only owners and existing members can remove members
            if ($group->privacy === 'private') {
                $accessValidation = $this->validatePrivateGroupAccess($group, 'remove_member');
                if ($accessValidation) {
                    return $accessValidation;
                }
            }

            // Check if the member exists in the group
            if (! $group->doctors()->where('doctor_id', $doctorId)->exists()) {
                return response()->json([
                    'value' => false,
                    'message' => __('api.member_not_found_in_group'),
                ], 404);
            }

            // Remove the member from the group
            $group->doctors()->detach($doctorId);

            // Send notification to removed member (if not removing themselves)
            if ($doctorId !== Auth::id()) {
                $this->sendMemberRemovedNotification($group, $doctorId);
            }

            // Clean up related notifications for the removed member
            $this->cleanupDoctorActionNotifications($doctorId, ['group_join_request', 'group_invitation'], $groupId);

            // Log the removal
            Log::info('Member removed from group', [
                'group_id' => $groupId,
                'removed_doctor_id' => $doctorId,
                'removed_by' => Auth::id(),
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'message' => __('api.member_removed_successfully'),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
                'removed_by' => Auth::id(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        }
    }

    /**
     * Search for members in a group.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchMembers(Request $request, $groupId)
    {
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'query' => 'required|string|max:255',
            ]);

            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);

            // Validate access for private groups
            $accessValidation = $this->validatePrivateGroupAccess($group, 'search_members');
            if ($accessValidation) {
                return $accessValidation;
            }

            // Search for members in the group based on the query with pagination
            $members = $group->doctors()
                ->where(function ($query) use ($validated) {
                    $query->where('users.name', 'like', '%'.$validated['query'].'%')
                        ->orWhere('users.email', 'like', '%'.$validated['query'].'%');
                })
                ->select('users.id', 'users.name', 'users.lname', 'users.image', 'users.syndicate_card', 'users.isSyndicateCardRequired', 'users.version')
                ->paginate(10)
                ->through(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'lname' => $doctor->lname,
                        'image' => $doctor->image,
                        'syndicate_card' => $doctor->syndicate_card,
                        'isSyndicateCardRequired' => $doctor->isSyndicateCardRequired,
                        'version' => $doctor->version,
                    ];
                });

            // Log the search action
            Log::info('Group members searched', [
                'group_id' => $groupId,
                'searched_by' => Auth::id(),
                'query' => $validated['query'],
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'data' => $members,
                'message' => __('api.members_search_results'),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
                'searched_by' => Auth::id(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        }
    }

    /**
     * Fetch community members with pagination.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchMembers($groupId)
    {
        try {
            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);

            // Validate access for private groups
            $accessValidation = $this->validatePrivateGroupAccess($group, 'fetch_members');
            if ($accessValidation) {
                return $accessValidation;
            }

            // Retrieve the joined members of the group with pagination
            $members = $group->doctors()
                ->where('status', 'joined')
                ->select('users.id', 'users.name', 'users.lname', 'users.image', 'users.syndicate_card', 'users.isSyndicateCardRequired', 'users.version')
                ->paginate(10)
                ->through(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'lname' => $doctor->lname,
                        'image' => $doctor->image,
                        'syndicate_card' => $doctor->syndicate_card,
                        'isSyndicateCardRequired' => $doctor->isSyndicateCardRequired,
                        'version' => $doctor->version,
                    ];
                });

            // Retrieve pending invitations using User model
            $pendingInvitations = $group->doctors()
                ->where('status', 'pending')
                ->select(
                    'users.id',
                    'users.name',
                    'users.lname',
                    'users.image',
                    'users.syndicate_card',
                    'users.isSyndicateCardRequired',
                    'users.version',
                    'group_user.id as invitation_id',
                    'group_user.created_at as invited_at'
                )
                ->get()
                ->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'lname' => $doctor->lname,
                        'image' => $doctor->image,
                        'syndicate_card' => $doctor->syndicate_card,
                        'isSyndicateCardRequired' => $doctor->isSyndicateCardRequired,
                        'version' => $doctor->version,
                        'invitation_id' => (int) $doctor->invitation_id,
                        'invited_at' => $doctor->invited_at,
                    ];
                });

            // Log the action
            Log::info('Community members and pending invitations fetched', [
                'group_id' => $groupId,
                'members_count' => $members->count(),
                'pending_invitations_count' => $pendingInvitations->count(),
            ]);

            // Return success response with both members and pending invitations
            return response()->json([
                'value' => true,
                'data' => [
                    'members' => $members,
                    'pending_invitations' => $pendingInvitations,
                ],
                'message' => __('api.community_members_fetched_successfully'),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
                'fetched_by' => Auth::id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error fetching community members and invitations', [
                'group_id' => $groupId,
                'fetched_by' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.error_fetching_members_invitations'),
            ], 500);
        }
    }

    /**
     * Fetch group details along with paginated posts.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchGroupDetailsWithPosts($groupId)
    {
        try {
            // Find the group or fail if not found, without loading posts initially
            $group = Group::with(['owner' => function ($query) {
                $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
            }])->findOrFail($groupId);

            // Check if the authenticated user is a member of the group and get their status
            $doctorId = Auth::id();

            // Get user status and invitation_id
            $userGroupData = DB::table('group_user')
                ->where('group_id', $groupId)
                ->where('doctor_id', $doctorId)
                ->select('status', 'id as invitation_id')
                ->first();

            $group->user_status = $userGroupData->status ?? null;
            $group->invitation_id = $userGroupData ? ($userGroupData->invitation_id ? (int) $userGroupData->invitation_id : null) : null;

            // Check if group has pending invitations
            $hasPendingInvitations = $group->doctors()
                ->where('group_id', $groupId)
                ->where('status', 'pending')
                ->exists();

            $group->has_pending_invitations = $hasPendingInvitations;

            // Fetch member count for the group from the group_user table
            $memberCount = (int) DB::table('group_user')
                ->where('group_id', $group->id)
                ->where('status', 'joined')
                ->count();

            $group->member_count = $memberCount; // Add member count to the group object

            // Check if user can access posts based on group privacy
            if ($group->privacy === 'private' && (! $userGroupData || $userGroupData->status !== 'joined')) {
                // For private groups, only return group info without posts if user is not a member
                return response()->json([
                    'value' => true,
                    'data' => [
                        'group' => $group,
                        'posts' => [
                            'data' => [],
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => 10,
                            'total' => 0,
                        ],
                    ],
                    'message' => __('api.group_details_retrieved_successfully'),
                ]);
            }

            // Fetch posts with necessary relationships and counts
            $feedPosts = $group->posts()->with([
                'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'poll.options' => function ($query) use ($doctorId) {
                    $query->withCount('votes') // Count votes per option
                        ->with(['votes' => function ($voteQuery) use ($doctorId) {
                            $voteQuery->where('doctor_id', $doctorId); // Check if user voted
                        }]);
                },
            ])
                ->withCount(['likes', 'comments'])  // Count likes and comments
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is saved by the doctor
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is liked by the doctor
                    },
                ])
                ->latest('created_at') // Sort by created_at in descending order
                ->paginate(10); // Paginate 10 posts per page

            // Add 'is_saved' and 'is_liked' fields to each post
            $feedPosts->getCollection()->transform(function ($post) {
                // Add 'is_saved' field (true if the doctor saved the post)
                $post->isSaved = $post->saves->isNotEmpty();

                // Add 'is_liked' field (true if the doctor liked the post)
                $post->isLiked = $post->likes->isNotEmpty();

                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) {
                        $option->is_voted = $option->votes->isNotEmpty(); // If user has voted for this option
                        unset($option->votes); // Remove unnecessary vote data

                        return $option;
                    })->sortByDesc('votes_count')->values();
                }

                // Remove unnecessary data to clean up the response
                unset($post->saves, $post->likes);

                return $post;
            });

            // Log the action
            Log::info('Group details with paginated posts fetched', [
                'group_id' => $groupId,
                'fetched_by' => Auth::id(),
            ]);

            // Return success response with group details and paginated posts
            return response()->json([
                'value' => true,
                'data' => [
                    'group' => $group,
                    'posts' => $feedPosts,
                ],
                'message' => __('api.group_details_posts_fetched_successfully'),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
                'fetched_by' => Auth::id(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        }
    }

    /**
     * Join a group.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function joinGroup($groupId)
    {
        try {
            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);
            $userId = Auth::id();

            // Check if the user exists in the group and get their status
            $existingRecord = DB::table('group_user')
                ->where('group_id', $groupId)
                ->where('doctor_id', $userId)
                ->first();

            if ($existingRecord) {
                switch ($existingRecord->status) {
                    case 'joined':
                        Log::info('User already a member of the group', [
                            'group_id' => $groupId,
                            'doctor_id' => $userId,
                        ]);

                        return response()->json([
                            'value' => false,
                            'message' => __('api.already_member_of_group'),
                        ], 400);

                    case 'pending':
                    case 'invited':
                        Log::info('User already has a pending request or invitation', [
                            'group_id' => $groupId,
                            'doctor_id' => $userId,
                            'status' => $existingRecord->status,
                        ]);

                        return response()->json([
                            'value' => false,
                            'message' => $existingRecord->status === 'pending'
                                ? 'Your join request is still pending'
                                : 'You already have an invitation to this group',
                        ], 400);

                    case 'declined':
                        $newStatus = ($group->privacy === 'private') ? 'pending' : 'joined';

                        // Update the existing record
                        DB::table('group_user')
                            ->where('id', $existingRecord->id)
                            ->update([
                                'status' => $newStatus,
                                'updated_at' => now(),
                            ]);

                        // Send notifications if needed
                        if ($newStatus === 'pending') {
                            $this->sendJoinRequestNotification($group, $userId);
                        }

                        return response()->json([
                            'value' => true,
                            'message' => ($newStatus === 'joined')
                                ? __('api.joined_group_successfully')
                                : __('api.join_request_sent'),
                        ], 200);
                }
            }

            // If user doesn't exist in the group, create new record
            $status = ($group->privacy === 'private') ? 'pending' : 'joined';

            DB::table('group_user')->insert([
                'group_id' => $groupId,
                'doctor_id' => $userId,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Send notifications if needed
            if ($status === 'pending') {
                $this->sendJoinRequestNotification($group, $userId);
            }

            return response()->json([
                'value' => true,
                'message' => ($status === 'joined')
                    ? __('api.joined_group_successfully')
                    : __('api.join_request_sent'),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Group not found', [
                'group_id' => $groupId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        }
    }

    private function sendJoinRequestNotification($group, $userId)
    {
        if ($group->owner_id !== Auth::id()) {
            AppNotification::createLocalized([
                'doctor_id' => $group->owner_id,
                'type' => 'group_join_request',
                'type_id' => $group->id,
                'localization_key' => 'api.clean_notification_group_join_request',
                'localization_params' => ['name' => $this->formatUserName(Auth::user())],
                'type_doctor_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Notification sent to group owner ID: '.$group->owner_id.' for group ID: '.$group->id);

            // Get FCM tokens for push notification
            $tokens = FcmToken::where('doctor_id', $group->owner_id)
                ->pluck('token')
                ->toArray();

            if (! empty($tokens)) {
                $this->notificationService->sendPushNotification(
                    __('api.new_join_request'),
                    __('api.clean_doctor_requested_to_join', ['name' => ucfirst($this->formatUserName(Auth::user()))]),
                    $tokens
                );
            }
        }
    }

    /**
     * Approve or decline join requests by group owner.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleJoinRequest(Request $request, $groupId)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'status' => 'required|in:approved,declined',
                'request_id' => 'required|exists:group_user,id',
            ]);

            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);
            $ownerId = Auth::id();

            // Check if the authenticated user is the group owner
            if ($group->owner_id !== $ownerId) {
                Log::error('Unauthorized join request handling attempt', [
                    'group_id' => $groupId,
                    'user_id' => $ownerId,
                    'actual_owner' => $group->owner_id,
                ]);

                return response()->json([
                    'value' => false,
                    'message' => __('api.unauthorized_group_action'),
                ], 403);
            }

            // Find the join request and check if it's pending
            $joinRequest = DB::table('group_user')
                ->where('id', $validated['request_id'])
                ->where('group_id', $groupId)
                ->where('status', 'pending')
                ->first();

            if (! $joinRequest) {
                Log::error('Invalid join request', [
                    'group_id' => $groupId,
                    'owner_id' => $ownerId,
                    'request_id' => $validated['request_id'],
                ]);

                return response()->json([
                    'value' => false,
                    'message' => __('api.invalid_join_request'),
                ], 400);
            }

            try {
                DB::beginTransaction();

                // Update the join request status
                $newStatus = $validated['status'] === 'approved' ? 'joined' : 'declined';
                DB::table('group_user')
                    ->where('id', $validated['request_id'])
                    ->update([
                        'status' => $newStatus,
                        'updated_at' => now(),
                    ]);

                // Send notification to the requesting user
                $this->sendJoinRequestResponseNotification($group, $joinRequest->doctor_id, $validated['status']);

                DB::commit();

                Log::info('Join request handled successfully', [
                    'group_id' => $groupId,
                    'request_id' => $validated['request_id'],
                    'status' => $validated['status'],
                    'owner_id' => $ownerId,
                ]);

                return response()->json([
                    'value' => true,
                    'message' => $validated['status'] === 'approved'
                        ? __('api.join_request_approved')
                        : __('api.join_request_declined'),
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Group not found for join request handling', [
                'group_id' => $groupId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error handling join request', [
                'error' => $e->getMessage(),
                'group_id' => $groupId,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.error_handling_join_request'),
            ], 500);
        }
    }

    /**
     * Send notification to user about join request response
     */
    private function sendJoinRequestResponseNotification($group, $requestingUserId, $status)
    {
        try {
            $owner = Auth::user();
            $notificationType = $status === 'approved' ? 'group_join_approved' : 'group_join_declined';
            $localizationKey = $status === 'approved'
                ? 'api.notification_group_join_approved'
                : 'api.notification_group_join_declined';

            AppNotification::createLocalized([
                'doctor_id' => $requestingUserId,
                'type' => $notificationType,
                'type_id' => $group->id,
                'localization_key' => $localizationKey,
                'localization_params' => [
                    'group_name' => $group->name,
                    'owner_name' => $this->formatUserName($owner),
                ],
                'type_doctor_id' => $owner->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Send push notification
            $tokens = FcmToken::where('doctor_id', $requestingUserId)
                ->pluck('token')
                ->toArray();

            if (! empty($tokens)) {
                $title = $status === 'approved'
                    ? __('api.join_request_approved_title')
                    : __('api.join_request_declined_title');
                $body = $status === 'approved'
                    ? __('api.join_request_approved_body', ['group' => $group->name])
                    : __('api.join_request_declined_body', ['group' => $group->name]);

                $this->notificationService->sendPushNotification($title, $body, $tokens);
            }

            Log::info('Join request response notification sent', [
                'group_id' => $group->id,
                'requesting_user_id' => $requestingUserId,
                'status' => $status,
                'owner_id' => $owner->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending join request response notification: '.$e->getMessage());
        }
    }

    /**
     * Send notification to user when they are removed from a group
     */
    private function sendMemberRemovedNotification($group, $removedUserId)
    {
        try {
            $remover = Auth::user();

            AppNotification::createLocalized([
                'doctor_id' => $removedUserId,
                'type' => 'group_member_removed',
                'type_id' => $group->id,
                'localization_key' => 'api.notification_group_member_removed',
                'localization_params' => [
                    'group_name' => $group->name,
                    'remover_name' => $this->formatUserName($remover),
                ],
                'type_doctor_id' => $remover->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Send push notification
            $tokens = FcmToken::where('doctor_id', $removedUserId)
                ->pluck('token')
                ->toArray();

            if (! empty($tokens)) {
                $this->notificationService->sendPushNotification(
                    __('api.member_removed_title'),
                    __('api.member_removed_body', ['group' => $group->name]),
                    $tokens
                );
            }

            Log::info('Member removed notification sent', [
                'group_id' => $group->id,
                'removed_user_id' => $removedUserId,
                'remover_id' => $remover->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending member removed notification: '.$e->getMessage());
        }
    }

    /**
     * Leave a group.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaveGroup($groupId)
    {
        try {
            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);
            $userId = Auth::id();

            // Check if the user is a member of the group
            if (! $group->doctors()->where('doctor_id', $userId)->exists()) {
                // Log the attempt to leave a group the user is not a member of
                Log::info('User not a member of the group', [
                    'group_id' => $groupId,
                    'doctor_id' => $userId,
                ]);

                return response()->json([
                    'value' => false,
                    'message' => __('api.not_member_of_group'),
                ], 400);
            }

            // Remove the user from the group members
            $group->doctors()->detach($userId);

            // Log the leave action
            Log::info('User left group', [
                'group_id' => $groupId,
                'doctor_id' => $userId,
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'message' => __('api.left_group_successfully'),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
                'user_id' => Auth::id(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        }
    }

    /**
     * Fetch groups owned by the authenticated user with pagination.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchMyGroups()
    {
        $userId = Auth::id();

        // Retrieve groups owned by the authenticated user with optimized queries
        $myGroups = Group::with(['owner' => function ($query) {
            $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
        }])
            ->withCount(['doctors' => function ($query) {
                $query->where('status', 'joined');
            }])
            ->with(['doctors' => function ($query) use ($userId) {
                $query->where('doctor_id', $userId)
                    ->select('group_user.status', 'group_user.group_id');
            }])
            ->where('owner_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function ($group) {
                $group->user_status = $group->doctors->first()->status ?? null;
                $group->member_count = (int) $group->doctors_count;
                unset($group->doctors);

                return $group;
            });

        // Log the action
        Log::info('User groups fetched with pagination', [
            'owner_id' => $userId,
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $myGroups,
            'message' => __('api.user_groups_fetched_successfully'),
        ], 200);
    }

    /**
     * Fetch all groups with pagination.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchAllGroups()
    {
        $userId = Auth::id();

        // Retrieve all groups with optimized queries
        $groups = Group::with(['owner' => function ($query) {
            $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
        }])
            ->withCount(['doctors' => function ($query) {
                $query->where('status', 'joined');
            }])
            ->with(['doctors' => function ($query) use ($userId) {
                $query->where('doctor_id', $userId)
                    ->select('group_user.status', 'group_user.group_id');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function ($group) {
                $group->user_status = $group->doctors->first()->status ?? null;
                $group->member_count = (int) $group->doctors_count;
                unset($group->doctors);

                return $group;
            });

        // Log the action
        Log::info('All groups fetched with pagination', [
            'fetched_by' => Auth::id(),
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $groups,
            'message' => __('api.all_groups_fetched_successfully'),
        ], 200);
    }

    /**
     * Fetch the latest three groups with user status and a paginated list of random posts from random groups.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchLatestGroupsWithRandomPosts()
    {
        try {
            // First try to fetch non-joined groups
            $latestGroups = Group::with(['owner' => function ($query) {
                $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
            }])
                ->whereDoesntHave('doctors', function ($query) {
                    $query->where('doctor_id', Auth::id())
                        ->where('status', 'joined'); // Exclude joined groups
                })
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get();

            // If no non-joined groups found, fetch joined groups
            if ($latestGroups->isEmpty()) {
                $latestGroups = Group::with(['owner' => function ($query) {
                    $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
                }])
                    ->whereHas('doctors', function ($query) {
                        $query->where('doctor_id', Auth::id())
                            ->where('status', 'joined'); // Include only joined groups
                    })
                    ->orderBy('created_at', 'desc')
                    ->take(3)
                    ->get();
            }

            // Add user status and member count to each group
            $userId = Auth::id();
            foreach ($latestGroups as $group) {
                // Fetch user status for the authenticated user
                $userStatus = DB::table('group_user')
                    ->where('group_id', $group->id)
                    ->where('doctor_id', $userId)
                    ->value('status');

                $group->user_status = $userStatus ?? null;

                // Fetch member count for the group from the group_user table
                $memberCount = DB::table('group_user')
                    ->where('group_id', $group->id)
                    ->where('status', 'joined')
                    ->count();

                $group->member_count = (int) $memberCount; // Add member count to the group object
            }

            // Get groups where the user is a joined member
            $userJoinedGroupIds = DB::table('group_user')
                ->where('doctor_id', $userId)
                ->where('status', 'joined')
                ->pluck('group_id')
                ->toArray();

            // Fetch posts with necessary relationships and counts
            // Include posts from public groups OR private groups where user is a member
            $randomPosts = FeedPost::with([
                'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'poll.options' => function ($query) use ($userId) {
                    $query->withCount('votes') // Count votes per option
                        ->with(['votes' => function ($voteQuery) use ($userId) {
                            $voteQuery->where('doctor_id', $userId); // Check if user voted
                        }]);
                },
            ])
                ->withCount(['likes', 'comments'])  // Count likes and comments
                ->with([
                    'saves' => function ($query) use ($userId) {
                        $query->where('doctor_id', $userId); // Check if the post is saved by the doctor
                    },
                    'likes' => function ($query) use ($userId) {
                        $query->where('doctor_id', $userId); // Check if the post is liked by the doctor
                    },
                ])
                ->whereNotNull('group_id') // Ensure group_id is not null
                ->where(function ($query) use ($userJoinedGroupIds) {
                    $query->whereHas('group', function ($groupQuery) {
                        $groupQuery->where('privacy', 'public'); // Include posts from public groups
                    })
                        ->orWhereIn('group_id', $userJoinedGroupIds); // OR include posts from private groups where user is a member
                })
                ->inRandomOrder() // Fetch posts randomly
                ->with(['group' => function ($query) {
                    $query->select('id', 'name', 'privacy'); // Include group name and privacy
                }])
                ->paginate(10); // Paginate 10 posts per page

            // Add 'is_saved' and 'is_liked' fields to each post
            $randomPosts->getCollection()->transform(function ($post) {
                // Add 'is_saved' field (true if the doctor saved the post)
                $post->isSaved = $post->saves->isNotEmpty();

                // Add 'is_liked' field (true if the doctor liked the post)
                $post->isLiked = $post->likes->isNotEmpty();

                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) {
                        $option->is_voted = $option->votes->isNotEmpty(); // If user has voted for this option
                        unset($option->votes); // Remove unnecessary vote data

                        return $option;
                    })->sortByDesc('votes_count')->values();
                }
                // Remove unnecessary data to clean up the response
                unset($post->saves, $post->likes);

                return $post;
            });

            // Log the action
            Log::info('Latest groups and random posts fetched', [
                'fetched_by' => Auth::id(),
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'data' => [
                    'latest_groups' => $latestGroups,
                    'random_posts' => $randomPosts,
                ],
                'message' => __('api.latest_groups_posts_fetched_successfully'),
            ], 200);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error fetching latest groups and random posts', [
                'error' => $e->getMessage(),
                'fetched_by' => Auth::id(),
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => __('api.error_fetching_data'),
            ], 500);
        }
    }

    /**
     * Get group invitations for a specific doctor.
     *
     * @param  int  $doctorId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDoctorInvitations($doctorId)
    {
        try {
            // Validate that the doctor exists
            $doctor = User::findOrFail($doctorId);

            // Get all groups where the doctor has been invited
            $invitations = Group::with(['owner' => function ($query) {
                $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
            }])
                ->whereHas('doctors', function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId)
                        ->where('status', 'invited');
                })
                ->with(['doctors' => function ($query) use ($doctorId) {
                    $query->where('doctor_id', $doctorId)
                        ->where('status', 'invited')
                        ->select('group_user.id as invitation_id');
                }])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Add member count and invitation_id to each group
            foreach ($invitations as $group) {
                // Fetch member count for the group
                $memberCount = DB::table('group_user')
                    ->where('group_id', $group->id)
                    ->where('status', 'joined')
                    ->count();

                $group->member_count = (int) $memberCount;
                $group->user_status = 'invited';
                // Add the invitation_id from the pivot table
                $group->invitation_id = $group->doctors->first() ? (int) $group->doctors->first()->invitation_id : null;
                // Remove the doctors relationship from the response
                unset($group->doctors);
            }

            // Log the action
            Log::info('Group invitations fetched', [
                'doctor_id' => $doctorId,
            ]);

            return response()->json([
                'value' => true,
                'data' => $invitations,
                'message' => __('api.group_invitations_fetched_successfully'),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Doctor not found', [
                'doctor_id' => $doctorId,
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.doctor_not_found'),
            ], 404);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error fetching group invitations', [
                'doctor_id' => $doctorId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.error_fetching_group_invitations'),
            ], 500);
        }
    }

    /**
     * Get all invitations for a specific group.
     *
     * @param  int  $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGroupInvitations($groupId)
    {
        try {
            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);

            // Get all invited doctors for the group with their invitation IDs
            $invitations = DB::table('users')
                ->join('group_user', 'users.id', '=', 'group_user.doctor_id')
                ->where('group_user.group_id', $groupId)
                ->where('group_user.status', 'invited')
                ->select(
                    'users.id',
                    'users.name',
                    'users.lname',
                    'users.image',
                    'users.syndicate_card',
                    'users.isSyndicateCardRequired',
                    'users.version',
                    'group_user.id as invitation_id'
                )
                ->orderBy('group_user.created_at', 'desc')
                ->paginate(20);

            // Log the action
            Log::info('Group invitations fetched by group ID', [
                'group_id' => $groupId,
            ]);

            return response()->json([
                'value' => true,
                'data' => [
                    'group' => [
                        'id' => $group->id,
                        'name' => $group->name,
                        'description' => $group->description,
                        'privacy' => $group->privacy,
                        'header_picture' => $group->header_picture,
                        'group_image' => $group->group_image,
                        'owner' => $group->owner()->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version')->first(),
                    ],
                    'invitations' => $invitations,
                ],
                'message' => __('api.group_invitations_fetched_successfully'),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.group_not_found'),
            ], 404);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error fetching group invitations', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'value' => false,
                'message' => __('api.error_fetching_group_invitations'),
            ], 500);
        }
    }
}
