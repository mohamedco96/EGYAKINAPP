<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\MainController;
use App\Models\FeedPost;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\NotificationController;
use App\Models\FcmToken;
use App\Models\AppNotification;


class GroupController extends Controller
{
    protected $mainController;
    protected $notificationController;

    public function __construct(MainController $mainController, NotificationController $notificationController)
    {
        $this->mainController = $mainController;
        $this->notificationController = $notificationController;
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
                'name' => $group->name
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'data' => $group,
                'message' => 'Group created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error
            Log::error('Error creating group', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while creating the group'
            ], 500);
        }
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
                'privacy' => 'in:public,private'
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
                        throw new \Exception('Header picture upload failed.');
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
                        throw new \Exception('Group image upload failed.');
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
                    'changes' => array_intersect_key($validated, array_flip(['name', 'privacy'])) // Only log non-sensitive fields
                ]);

                // Return success response
                return response()->json([
                    'value' => true,
                    'data' => $group,
                    'message' => 'Group updated successfully'
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $id,
                'updated_by' => Auth::id()
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors
            Log::warning('Group update validation failed', [
                'group_id' => $id,
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error updating group', [
                'group_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while updating the group'
            ], 500);
        }
    }

    /**
     * Delete a group.
     * 
     * @param int $id
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

            // Remove the associated AppNotification
            $deletedCount = AppNotification::where('type', 'group_invitation')
            ->orWhere('type', 'group_invitation_accepted')
            ->orWhere('type', 'group_join_request')
            ->where('type_id', $id)
            ->delete();
    
            Log::info("Deleted $deletedCount notifications for post ID $id.");

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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $id,
                'deleted_by' => Auth::id()
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        }
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
            'doctor_ids' => 'required|array', // Ensure doctor_ids is an array
            'doctor_ids.*' => 'exists:users,id' // Ensure each doctor_id exists in the users table
        ]);

        // Find the group or fail if not found
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        }

        // Check if the authenticated user is the group owner
        //$this->authorizeOwner($group);

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
                        'attempted_by' => Auth::id()
                    ]);
                } elseif ($existingStatus === 'invited' || $existingStatus === 'pending') {
                    // Log the attempt to invite an already invited member
                    Log::info('Doctor is already invited to the group', [
                        'group_id' => $groupId,
                        'doctor_id' => $doctorId,
                        'attempted_by' => Auth::id()
                    ]);
                } elseif ($existingStatus === 'declined') {
                    // Update the status to invited again
                    $group->doctors()->updateExistingPivot($doctorId, ['status' => 'invited']);
                     
                    

                    if ($doctorId !== Auth::id()) {
                        AppNotification::create([
                            'doctor_id' => $doctorId,
                            'type' => 'group_invitation',
                            'type_id' => $groupId,
                            'content' => sprintf('Dr. %s Invited you to his group', Auth::user()->name . ' ' . Auth::user()->lname),
                            'type_doctor_id' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        Log::info("Notification sent to group owner ID: " . $group->owner_id . " for group ID: " . $groupId);

                        // Get FCM tokens for push notification
                        $tokens = FcmToken::where('doctor_id', $doctorId)
                            ->pluck('token')
                            ->toArray();

                        if (!empty($tokens)) {
                            $this->notificationController->sendPushNotification(
                                'New Invitation was created 📣',
                                'Dr. ' . ucfirst(Auth::user()->name) . ' invited you to his group',
                                $tokens
                            );
                        }
                    }
                    
                    // Log the re-invitation
                    Log::info('Doctor re-invited to the group', [
                        'group_id' => $groupId,
                        'doctor_id' => $doctorId,
                        'invited_by' => Auth::id()
                    ]);
                } elseif ($existingStatus === 'accepted') {
                    // Log the attempt to invite an already invited member
                    Log::info('Doctor is already accepted the invitation', [
                        'group_id' => $groupId,
                        'doctor_id' => $doctorId,
                        'attempted_by' => Auth::id()
                    ]);
                } else {
                    // Invite the user to the group (attach the user to the group members with status "invited")
                    $group->doctors()->attach($doctorId, ['status' => 'invited']);

                    // Log the invitation
                    Log::info('User invited to group', [
                        'group_id' => $groupId,
                        'invited_doctor_id' => $doctorId,
                        'invited_by' => Auth::id()
                    ]);
            
                    if ($doctorId !== Auth::id()) {
                        AppNotification::create([
                            'doctor_id' => $doctorId,
                            'type' => 'group_invitation',
                            'type_id' => $groupId,
                            'content' => sprintf('Dr. %s Invited you to his group', Auth::user()->name . ' ' . Auth::user()->lname),
                            'type_doctor_id' => Auth::id(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        Log::info("Notification sent to group owner ID: " . $group->owner_id . " for group ID: " . $groupId);

                        // Get FCM tokens for push notification
                        $tokens = FcmToken::where('doctor_id', $doctorId)
                            ->pluck('token')
                            ->toArray();

                        if (!empty($tokens)) {
                            $this->notificationController->sendPushNotification(
                                'New Invitation was created 📣',
                                'Dr. ' . ucfirst(Auth::user()->name) . ' invited you to his group',
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
                'message' => 'Invitations processed'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error
            Log::error('Error processing group invitations', [
                'error' => $e->getMessage(),
                'group_id' => $groupId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while processing invitations'
            ], 500);
        }
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
        try {
            // Validate the request with stricter rules
            $validated = $request->validate([
                'status' => 'required|in:accepted,declined',
                'invitation_id' => 'required|exists:group_user,id'
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

            if (!$invitation) {
                Log::error('Invalid invitation', [
                    'group_id' => $groupId,
                    'doctor_id' => $userId,
                    'invitation_id' => $validated['invitation_id']
                ]);

                return response()->json([
                    'value' => false,
                    'message' => 'Invalid invitation'
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
                        'updated_at' => now()
                    ]);

                if ($validated['status'] === 'accepted') {
                    // Send notification to group owner
                    if ($group->owner_id !== $userId) {
                        $notification = AppNotification::create([
                            'doctor_id' => $group->owner_id,
                            'type' => 'group_invitation_accepted',
                            'type_id' => $groupId,
                            'content' => sprintf('Dr. %s accepted your group invitation', Auth::user()->name . ' ' . Auth::user()->lname),
                            'type_doctor_id' => $userId,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        Log::info("Notification sent to group owner", [
                            'owner_id' => $group->owner_id,
                            'group_id' => $groupId
                        ]);
                    }
                }

                DB::commit();

                // Log the invitation status change
                Log::info('Invitation status updated', [
                    'group_id' => $groupId,
                    'doctor_id' => $userId,
                    'invitation_id' => $validated['invitation_id'],
                    'status' => $newStatus
                ]);

                return response()->json([
                    'value' => true,
                    'message' => sprintf('Invitation %s successfully', $validated['status'])
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Group not found', [
                'group_id' => $groupId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Invitation handling validation failed', [
                'group_id' => $groupId,
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error handling invitation', [
                'group_id' => $groupId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while handling the invitation'
            ], 500);
        }
    }

    /**
     * Get group details, including members and privacy settings.
     * 
     * @param int $id
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

            // Count the number of members in the group
            $group->members_count = $group->doctors()->where('status', 'joined')->count();

            // Check if the authenticated user is a member of the group and get their status
            $userId = Auth::id();

            $userStatus = DB::table('group_user')
                ->where('group_id', $id)
                ->where('doctor_id', $userId)
                ->value('status');

            $group->user_status = $userStatus ?? null;

            // Log group retrieval
            Log::info('Group details retrieved', [
                'group_id' => $id,
                'retrieved_by' => $userId,
                'user_status' => $userStatus
            ]);

            // Return success response with members count and user status
            return response()->json([
                'value' => true,
                'data' => $group,
                'message' => 'Group details retrieved successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $id,
                'retrieved_by' => Auth::id()
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        }
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
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'doctor_id' => 'required|exists:users,id'
            ]);

            $doctorId = $validated['doctor_id'];

            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);

            // Check if the authenticated user is the group owner
            //$this->authorizeOwner($group);

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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
                'removed_by' => Auth::id()
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        }
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
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'query' => 'required|string|max:255'
            ]);

            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);

            // Search for members in the group based on the query with pagination
            $members = $group->doctors()
                ->where(function ($query) use ($validated) {
                    $query->where('users.name', 'like', '%' . $validated['query'] . '%')
                        ->orWhere('users.email', 'like', '%' . $validated['query'] . '%');
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
                'query' => $validated['query']
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'data' => $members,
                'message' => 'Members search results'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
                'searched_by' => Auth::id()
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        }
    }

    /**
     * Fetch community members with pagination.
     * 
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchMembers($groupId)
    {
        try {
            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);

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
                ->map(function($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'lname' => $doctor->lname,
                        'image' => $doctor->image,
                        'syndicate_card' => $doctor->syndicate_card,
                        'isSyndicateCardRequired' => $doctor->isSyndicateCardRequired,
                        'version' => $doctor->version,
                        'invitation_id' => $doctor->invitation_id,
                        'invited_at' => $doctor->invited_at
                    ];
                });

            // Log the action
            Log::info('Community members and pending invitations fetched', [
                'group_id' => $groupId,
                'members_count' => $members->count(),
                'pending_invitations_count' => $pendingInvitations->count()
            ]);

            // Return success response with both members and pending invitations
            return response()->json([
                'value' => true,
                'data' => [
                    'members' => $members,
                    'pending_invitations' => $pendingInvitations
                ],
                'message' => 'Community members and pending invitations fetched successfully'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
                'fetched_by' => Auth::id()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error fetching community members and invitations', [
                'group_id' => $groupId,
                'fetched_by' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while fetching members and invitations'
            ], 500);
        }
    }

    /**
     * Fetch group details along with paginated posts.
     * 
     * @param int $groupId
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
            $group->invitation_id = $userGroupData->invitation_id ?? null;

            // Check if group has pending invitations
            $hasPendingInvitations = $group->doctors()
                ->where('group_id', $groupId)
                ->where('status', 'pending')
                ->exists();

            $group->has_pending_invitations = $hasPendingInvitations;

            // Fetch member count for the group from the group_user table
            $memberCount = DB::table('group_user')
                ->where('group_id', $group->id)
                ->where('status', 'joined')
                ->count();

            $group->member_count = $memberCount; // Add member count to the group object

            // Fetch posts with necessary relationships and counts
            $feedPosts = $group->posts()->with([
                'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'poll.options' => function ($query) use ($doctorId) {
                    $query->withCount('votes') // Count votes per option
                        ->with(['votes' => function ($voteQuery) use ($doctorId) {
                            $voteQuery->where('doctor_id', $doctorId); // Check if user voted
                        }]);
                }
            ])
                ->withCount(['likes', 'comments'])  // Count likes and comments
                ->with([
                    'saves' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is saved by the doctor
                    },
                    'likes' => function ($query) use ($doctorId) {
                        $query->where('doctor_id', $doctorId); // Check if the post is liked by the doctor
                    }
                ])
                ->latest('created_at') // Sort by created_at in descending order
                ->paginate(10); // Paginate 10 posts per page

            // Add 'is_saved' and 'is_liked' fields to each post
            $feedPosts->getCollection()->transform(function ($post) use ($doctorId) {
                // Add 'is_saved' field (true if the doctor saved the post)
                $post->isSaved = $post->saves->isNotEmpty();

                // Add 'is_liked' field (true if the doctor liked the post)
                $post->isLiked = $post->likes->isNotEmpty();

                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) use ($doctorId) {
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
                'fetched_by' => Auth::id()
            ]);

            // Return success response with group details and paginated posts
            return response()->json([
                'value' => true,
                'data' => [
                    'group' => $group,
                    'posts' => $feedPosts
                ],
                'message' => 'Group details with paginated posts fetched successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
                'fetched_by' => Auth::id()
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        }
    }


    /**
     * Join a group.
     * 
     * @param int $groupId
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
                            'doctor_id' => $userId
                        ]);
                        return response()->json([
                            'value' => false,
                            'message' => 'You are already a member of this group'
                        ], 400);

                    case 'pending':
                    case 'invited':
                        Log::info('User already has a pending request or invitation', [
                            'group_id' => $groupId,
                            'doctor_id' => $userId,
                            'status' => $existingRecord->status
                        ]);
                        return response()->json([
                            'value' => false,
                            'message' => $existingRecord->status === 'pending' 
                                ? 'Your join request is still pending'
                                : 'You already have an invitation to this group'
                        ], 400);

                    case 'declined':
                        $newStatus = ($group->privacy === 'private') ? 'pending' : 'joined';
                        
                        // Update the existing record
                        DB::table('group_user')
                            ->where('id', $existingRecord->id)
                            ->update([
                                'status' => $newStatus,
                                'updated_at' => now()
                            ]);

                        // Send notifications if needed
                        if ($newStatus === 'pending') {
                            $this->sendJoinRequestNotification($group, $userId);
                        }

                        return response()->json([
                            'value' => true,
                            'message' => ($newStatus === 'joined')
                                ? 'Joined group successfully'
                                : 'Join request sent, waiting for approval'
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
                'updated_at' => now()
            ]);

            // Send notifications if needed
            if ($status === 'pending') {
                $this->sendJoinRequestNotification($group, $userId);
            }

            return response()->json([
                'value' => true,
                'message' => ($status === 'joined')
                    ? 'Joined group successfully'
                    : 'Join request sent, waiting for approval'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Group not found', [
                'group_id' => $groupId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        }
    }

    private function sendJoinRequestNotification($group, $userId)
    {
        if ($group->owner_id !== Auth::id()) {
            AppNotification::create([
                'doctor_id' => $group->owner_id,
                'type' => 'group_join_request',
                'type_id' => $group->id,
                'content' => sprintf('Dr. %s requested to join group', Auth::user()->name . ' ' . Auth::user()->lname),
                'type_doctor_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info("Notification sent to group owner ID: " . $group->owner_id . " for group ID: " . $groupId);

            // Get FCM tokens for push notification
            $tokens = FcmToken::where('doctor_id', $group->owner_id)
                ->pluck('token')
                ->toArray();

            if (!empty($tokens)) {
                $this->notificationController->sendPushNotification(
                    'New Join Request 📣',
                    'Dr. ' . ucfirst(Auth::user()->name) . ' requested to join group',
                    $tokens
                );
            }
        }
    }

    /**
     * Leave a group.
     * 
     * @param int $groupId
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaveGroup($groupId)
    {
        try {
            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);
            $userId = Auth::id();

            // Check if the user is a member of the group
            if (!$group->doctors()->where('doctor_id', $userId)->exists()) {
                // Log the attempt to leave a group the user is not a member of
                Log::info('User not a member of the group', [
                    'group_id' => $groupId,
                    'doctor_id' => $userId
                ]);

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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId,
                'user_id' => Auth::id()
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'Group not found'
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
        ->through(function ($group) use ($userId) {
            $group->user_status = $group->doctors->first()->status ?? null;
            $group->member_count = $group->doctors_count;
            unset($group->doctors);
            return $group;
        });

        // Log the action
        Log::info('User groups fetched with pagination', [
            'owner_id' => $userId
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $myGroups,
            'message' => 'User groups fetched successfully'
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
        ->through(function ($group) use ($userId) {
            $group->user_status = $group->doctors->first()->status ?? null;
            $group->member_count = $group->doctors_count;
            unset($group->doctors);
            return $group;
        });

        // Log the action
        Log::info('All groups fetched with pagination', [
            'fetched_by' => Auth::id()
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $groups,
            'message' => 'All groups fetched successfully'
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

                $group->member_count = $memberCount; // Add member count to the group object
            }


            // Fetch posts with necessary relationships and counts
            $randomPosts = FeedPost::with([
                'doctor:id,name,lname,image,email,syndicate_card,isSyndicateCardRequired',
                'poll.options' => function ($query) use ($userId) {
                    $query->withCount('votes') // Count votes per option
                        ->with(['votes' => function ($voteQuery) use ($userId) {
                            $voteQuery->where('doctor_id', $userId); // Check if user voted
                        }]);
                }
            ])
                ->withCount(['likes', 'comments'])  // Count likes and comments
                ->with([
                    'saves' => function ($query) use ($userId) {
                        $query->where('doctor_id', $userId); // Check if the post is saved by the doctor
                    },
                    'likes' => function ($query) use ($userId) {
                        $query->where('doctor_id', $userId); // Check if the post is liked by the doctor
                    }
                ])
                ->whereNotNull('group_id') // Ensure group_id is not null
                ->inRandomOrder() // Fetch posts randomly
                ->with(['group' => function ($query) {
                    $query->select('id', 'name'); // Include group name
                }])
                ->paginate(10); // Paginate 10 posts per page

            // Add 'is_saved' and 'is_liked' fields to each post
            $randomPosts->getCollection()->transform(function ($post) use ($userId) {
                // Add 'is_saved' field (true if the doctor saved the post)
                $post->isSaved = $post->saves->isNotEmpty();

                // Add 'is_liked' field (true if the doctor liked the post)
                $post->isLiked = $post->likes->isNotEmpty();

                // Sort poll options by vote count (highest first) and check if the user has voted
                if ($post->poll) {
                    $post->poll->options = $post->poll->options->map(function ($option) use ($userId) {
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
                'fetched_by' => Auth::id()
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'data' => [
                    'latest_groups' => $latestGroups,
                    'random_posts' => $randomPosts
                ],
                'message' => 'Latest groups and random posts fetched successfully'
            ], 200);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error fetching latest groups and random posts', [
                'error' => $e->getMessage(),
                'fetched_by' => Auth::id()
            ]);

            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'An error occurred while fetching data'
            ], 500);
        }
    }

    /**
     * Get group invitations for a specific doctor.
     * 
     * @param int $doctorId
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

                $group->member_count = $memberCount;
                $group->user_status = 'invited';
                // Add the invitation_id from the pivot table
                $group->invitation_id = $group->doctors->first()->invitation_id ?? null;
                // Remove the doctors relationship from the response
                unset($group->doctors);
            }

            // Log the action
            Log::info('Group invitations fetched', [
                'doctor_id' => $doctorId
            ]);

            return response()->json([
                'value' => true,
                'data' => $invitations,
                'message' => 'Group invitations fetched successfully'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Doctor not found', [
                'doctor_id' => $doctorId
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Doctor not found'
            ], 404);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error fetching group invitations', [
                'doctor_id' => $doctorId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while fetching group invitations'
            ], 500);
        }
    }

    /**
     * Get all invitations for a specific group.
     * 
     * @param int $groupId
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
                'group_id' => $groupId
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
                        'owner' => $group->owner()->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version')->first()
                    ],
                    'invitations' => $invitations
                ],
                'message' => 'Group invitations fetched successfully'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log the error
            Log::error('Group not found', [
                'group_id' => $groupId
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Group not found'
            ], 404);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error fetching group invitations', [
                'group_id' => $groupId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'An error occurred while fetching group invitations'
            ], 500);
        }
    }

}
