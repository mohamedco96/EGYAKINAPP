<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Http\Requests\StoreAchievementRequest;
use App\Http\Requests\UpdateAchievementRequest;

class AchievementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAchievementRequest $request)
    {

        // Handle file upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('images', 'public');
            $imagePath = Storage::disk('public')->url($imagePath);
            //$image_path = Storage::disk('public')->putFile('images/', $request->file('image'));

        }

        // Create the Achievement
        $achievement =Achievement::create(
            [
                'name' => $request->name,
                'image' => $imagePath,
                'description' => $request->description,
            ]
        );
        // Associate the post with the current user
        if ($achievement != null) {
            $response = [
                'value' => true,
                'data' => $achievement,
            ];

            return response($response, 200);
        } else {
            $response = [
                'value' => false,
                'message' => 'No Achievement was found',
            ];

            return response($response, 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Achievement $achievement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Achievement $achievement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAchievementRequest $request, Achievement $achievement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Achievement $achievement)
    {
        //
    }
}
