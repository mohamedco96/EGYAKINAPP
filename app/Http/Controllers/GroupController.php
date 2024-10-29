<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\MainController;

class GroupController extends Controller
{
    protected $mainController;

    public function __construct(MainController $mainController)
    {
        $this->mainController = $mainController;
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
    if (Auth::id() !== $group->owner_id) {
        Log::warning('Unauthorized group update attempt', [
            'doctor_id' => Auth::id(),
            'group_id' => $id
        ]);
        return response()->json([
            'value' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

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
        try {
            // Find the group or fail if not found
            $group = Group::findOrFail($id);

            // Check if the authenticated user is the group owner
            if (Auth::id() !== $group->owner_id) {
                Log::warning('Unauthorized group deletion attempt', [
                    'doctor_id' => Auth::id(),
                    'group_id' => $id
                ]);
                return response()->json([
                    'value' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

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
        } catch (\Exception $e) {
            Log::error('Group deletion failed', [
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'value' => false,
                'message' => 'Group deletion failed.'
            ], 500);
        }
    }

    // ... other methods ...
}
