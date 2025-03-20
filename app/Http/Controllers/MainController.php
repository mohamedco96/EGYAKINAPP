<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MainController extends Controller
{

    public function uploadImageAndVideo($media, $path)
    {
        try {
            // Get the authenticated user's name
            $name = auth()->user()->name;
    
            // Generate a unique timestamp
            $timestamp = time();
    
            // Create a unique file name using the user's name and timestamp
            $fileName = "{$name}_media_{$timestamp}." . $media->getClientOriginalExtension();
    
            // Store the media in the specified path (image or video directory)
            $storedPath = $media->storeAs($path, $fileName, 'public');
    
            // Construct the full URL for the uploaded media
            $mediaUrl = config('app.url') . '/storage/' . $storedPath;
    
            // Return success response with media URL
            return response()->json([
                'value' => true,
                'message' => 'Media uploaded successfully.',
                'image' => $mediaUrl,  // This could be image or video URL
            ], 200);
        } catch (\Exception $e) {
            // Log any exception during the upload process
            Log::error("Error uploading media: " . $e->getMessage());
    
            // Return error response
            return response()->json([
                'value' => false,
                'message' => 'An error occurred during media upload.',
            ], 500);
        }
    }

    public function uploadVideo(Request $request)
    {
        // Validate the request to ensure a video file is provided
        $request->validate([
            'video' => 'required|mimes:mp4,mkv,avi,flv|max:20480', // max 20MB
        ]);

        // Check if a video file is present in the request
        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $name = auth()->user()->name; // Get the authenticated user's name
            $timestamp = time(); // Get the current timestamp

            // Create a unique file name using the username, 'video', and the timestamp
            $fileName = "{$name}_video_{$timestamp}." . $video->getClientOriginalExtension();

            // Store the video in the 'media_videos' directory in the 'public' disk
            $path = $video->storeAs('media_videos', $fileName, 'public');

            // Construct the full URL for the uploaded video
            $videoUrl = config('app.url') . '/storage/' . $path;

            // Return success response
            return response()->json([
                'value' => true,
                'message' => 'Video uploaded successfully.',
                'video' => $videoUrl,
            ], 200);
        }

        // If no file is present, return an error response
        return response()->json([
            'value' => false,
            'message' => 'Please choose a video file.',
        ], 400);
    }

}
