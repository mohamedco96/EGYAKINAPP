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
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\NotificationController;
use App\Models\FcmToken;


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
        try {
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
            } elseif ($existingStatus === 'invited') {
                // Log the attempt to invite an already invited member
                Log::info('Doctor is already invited to the group', [
                    'group_id' => $groupId,
                    'doctor_id' => $doctorId,
                    'attempted_by' => Auth::id()
                ]);
            } elseif ($existingStatus === 'declined') {
                // Update the status to invited again
                $group->doctors()->updateExistingPivot($doctorId, ['status' => 'invited']);
                    
                // Check if the post owner is not the one liking the post
                if ($doctorId !== Auth::id()) {
                    $notification = AppNotification::create([
                        'doctor_id' => $doctorId,
                        'type' => 'Other',
                        'type_id' => $groupId,
                        'content' => sprintf('Dr. %s  Invited you to his group', Auth::user()->name . ' ' . Auth::user()->lname),
                        'type_doctor_id' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
    
                    Log::info("Notification sent to group owner ID: " . $group->owner_id . " for group ID: " . $groupId);
                }
    
                // Notifying other doctors
                $doctors = User::role(['Admin', 'Tester'])
                ->where('id', '!=', Auth::id())
                ->pluck('id'); // Get only the IDs of the users

                $title = 'New Invitation was created 📣';
                $body = 'Dr. ' . ucfirst(Auth::user()->name) . ' Invited you to his group';
                $tokens = FcmToken::whereIn('doctor_id', $doctors)
                    ->pluck('token')
                    ->toArray();
            
                $this->notificationController->sendPushNotification($title, $body, $tokens);

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
    
                // Check if the post owner is not the one liking the post
                if ($doctorId !== Auth::id()) {
                    $notification = AppNotification::create([
                        'doctor_id' => $doctorId,
                        'type' => 'Other',
                        'type_id' => $groupId,
                        'content' => sprintf('Dr. %s  Invited you to his group', Auth::user()->name . ' ' . Auth::user()->lname),
                        'type_doctor_id' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
    
                    Log::info("Notification sent to group owner ID: " . $group->owner_id . " for group ID: " . $groupId);
                }
    
                // Notifying other doctors
                $doctors = User::role(['Admin', 'Tester'])
                ->where('id', '!=', Auth::id())
                ->pluck('id'); // Get only the IDs of the users

                $title = 'New Invitation was created 📣';
                $body = 'Dr. ' . ucfirst(Auth::user()->name) . ' Invited you to his group';
                $tokens = FcmToken::whereIn('doctor_id', $doctors)
                    ->pluck('token')
                    ->toArray();
            
                $this->notificationController->sendPushNotification($title, $body, $tokens);
            }
        }

        // Return success response with details of successful and failed invites
        return response()->json([
            'value' => true,
            'message' => 'Invitations processed'
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
        try {
            // Validate the request to ensure status is either 'accepted' or 'declined'
            $validated = $request->validate([
                'status' => 'required|in:accepted,declined'
            ]);

            // Find the group or fail if not found
            $group = Group::findOrFail($groupId);
            $userId = Auth::id();

            // Check the current status of the invitation
            $currentStatus = $group->doctors()->where('doctor_id', $userId)->value('status');

            // If the user does not have an invitation or the status is already 'accepted' or 'declined', return an error
            if ($currentStatus !== 'invited') {
                // Log the error
                Log::error('No invitation found or invitation status has already been changed', [
                    'group_id' => $groupId,
                    'doctor_id' => $userId,
                    'current_status' => $currentStatus
                ]);

                return response()->json([
                    'value' => false,
                    'message' => 'No invitation found or invitation status has already been changed'
                ], 400);
            }

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

            // Retrieve the members of the group with pagination
            $members = $group->doctors()
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

            // Log the action
            Log::info('Community members fetched with pagination', [
                'group_id' => $groupId,
                'fetched_by' => Auth::id()
            ]);

            // Return success response
            return response()->json([
                'value' => true,
                'data' => $members,
                'message' => 'Community members fetched successfully'
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

            $userStatus = DB::table('group_user')
                ->where('group_id', $groupId)
                ->where('doctor_id', $doctorId)
                ->value('status');

            $group->user_status = $userStatus ?? null;


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

            // Check if the user is already a member of the group
            if ($group->doctors()->where('doctor_id', $userId)->exists()) {
                // Log the attempt to join an already joined group
                Log::info('User already a member of the group', [
                    'group_id' => $groupId,
                    'doctor_id' => $userId
                ]);

                return response()->json([
                    'value' => false,
                    'message' => 'You are already a member of this group'
                ], 400);
            }

            // Determine user status based on group privacy
            $status = ($group->privacy === 'private') ? 'pending' : 'joined';

            // Add the user to the group with the determined status
            $group->doctors()->attach($userId, ['status' => $status]);

            // Log the join action
            Log::info('User requested to join group', [
                'group_id' => $groupId,
                'doctor_id' => $userId,
                'status' => $status
            ]);


            // Check if the post owner is not the one liking the post
            if ($group->owner_id !== Auth::id()) {
                $notification = AppNotification::create([
                    'doctor_id' => $group->owner_id,
                    'type' => 'Other',
                    'type_id' => $groupId,
                    'content' => sprintf('Dr. %s  requested to join group', Auth::user()->name . ' ' . Auth::user()->lname),
                    'type_doctor_id' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info("Notification sent to group owner ID: " . $group->owner_id . " for group ID: " . $groupId);
            }

            // Notifying other doctors
            $doctors = User::role(['Admin', 'Tester'])
            ->where('id', '!=', Auth::id())
            ->pluck('id'); // Get only the IDs of the users

            $title = 'New Join Request 📣';
            $body = 'Dr. ' . ucfirst(Auth::user()->name) . ' requested to join group';
            $tokens = FcmToken::whereIn('doctor_id', $doctors)
                ->pluck('token')
                ->toArray();
        
            $this->notificationController->sendPushNotification($title, $body, $tokens);            
            // Return appropriate response
            return response()->json([
                'value' => true,
                'message' => ($status === 'joined')
                    ? 'Joined group successfully'
                    : 'Join request sent, waiting for approval'
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

        // Retrieve groups owned by the authenticated user with pagination
        //$groups = Group::where('owner_id', $userId)->paginate(10);

        $MyGroups = Group::with(['owner' => function ($query) {
            $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
        }])
            ->where('owner_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);


        foreach ($MyGroups as $group) {
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

        // Log the action
        Log::info('User groups fetched with pagination', [
            'owner_id' => $userId
        ]);

        // Return success response
        return response()->json([
            'value' => true,
            'data' => $MyGroups,
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
        // Retrieve all groups with pagination
        $groups = Group::with(['owner' => function ($query) {
            $query->select('id', 'name', 'lname', 'image', 'syndicate_card', 'isSyndicateCardRequired', 'version');
        }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Check if the authenticated user is a member of each group and get their status
        $userId = Auth::id();

        foreach ($groups as $group) {
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
            // Fetch the latest three groups
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
}
