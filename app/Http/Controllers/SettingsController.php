<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use App\Http\Requests\StoreSettingsRequest;
use App\Http\Requests\UpdateSettingsRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class SettingsController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Retrieve the latest settings
            $setting = Settings::latest()->first();

            // Check if any settings were found
            if ($setting) {
                // Prepare the response with the latest settings
                $response = [
                    'value' => true,
                    'app_freeze' => $setting->app_freeze,
                    'force_update' => $setting->force_update,
                    'updated_at' => $setting->updated_at,
                ];

                // Log the successful retrieval of settings
                Log::info('Settings retrieved successfully.', [
                    'app_freeze' => $setting->app_freeze,
                    'force_update' => $setting->force_update,
                    'updated_at' => $setting->updated_at,
                ]);

                // Return the response with a 200 OK status
                return response($response, 200);
            } else {
                // Log that no settings were found
                Log::warning('No settings found.');

                // Prepare the response indicating no settings were found
                $response = [
                    'value' => false,
                    'message' => 'No settings found.'
                ];

                // Return the response with a 404 Not Found status
                return response($response, 404);
            }
        } catch (\Exception $e) {
            // Log the exception with an error message
            Log::error('Error retrieving settings: ' . $e->getMessage());

            // Prepare the response indicating an error occurred
            $response = [
                'value' => false,
                'message' => 'An error occurred while retrieving settings.'
            ];

            // Return the response with a 500 Internal Server Error status
            return response($response, 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }



    /**
     * Store a newly created setting in storage.
     *
     * @param  \App\Http\Requests\StoreSettingsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSettingsRequest $request)
    {
        try {
            // Create a new setting using the validated request data
            $setting = Settings::create($request->all());

            // Check if the setting was successfully created
            if ($setting) {
                // Prepare the response with the created setting
                $response = [
                    'value' => true,
                    'data' => $setting,
                ];

                // Log the successful creation of the setting
                Log::info('Setting created successfully.');

                // Return the response with a 201 Created status
                return response($response, 201);
            } else {
                // Log that the setting creation failed
                Log::warning('Failed to create setting.');

                // Prepare the response indicating the creation failed
                $response = [
                    'value' => false,
                    'message' => 'Failed to create setting.',
                ];

                // Return the response with a 400 Bad Request status
                return response($response, 400);
            }
        } catch (\Exception $e) {
            // Log the exception with an error message
            Log::error('Error creating setting: ' . $e->getMessage());

            // Prepare the response indicating an error occurred
            $response = [
                'value' => false,
                'message' => 'An error occurred while creating the setting.'
            ];

            // Return the response with a 500 Internal Server Error status
            return response($response, 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Settings $settings)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Settings $settings)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSettingsRequest  $request
     * @param  \App\Models\Settings  $settings
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSettingsRequest $request, Settings $settings)
    {
        try {
            // Log the incoming update request
            Log::info('Updating setting.', [
                'setting_id' => $settings->id,
                'incoming_data' => $request->all()
            ]);

            // Manually update each field to avoid issues with mass assignment
            $settings->app_freeze = $request->input('app_freeze', $settings->app_freeze);
            $settings->force_update = $request->input('force_update', $settings->force_update);
            // Add more fields as necessary

            // Save the updated settings
            $settings->save();

            // Log the successful update of the setting
            Log::info('Setting updated successfully.', [
                'setting_id' => $settings->id,
                'updated_data' => $settings
            ]);

            // Prepare the response with the updated setting
            $response = [
                'value' => true,
                'data' => $settings,
                'message' => 'Setting updated successfully.'
            ];

            // Return the response with a 200 OK status
            return response($response, 200);
        } catch (\Exception $e) {
            // Log the exception with an error message
            Log::error('Error updating setting: ' . $e->getMessage());

            // Prepare the response indicating an error occurred
            $response = [
                'value' => false,
                'message' => 'An error occurred while updating the setting.'
            ];

            // Return the response with a 500 Internal Server Error status
            return response($response, 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Settings $settings)
    {
        //
    }
}
