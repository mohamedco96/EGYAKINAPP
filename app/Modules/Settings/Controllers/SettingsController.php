<?php

namespace App\Modules\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\Settings;
use App\Modules\Settings\Requests\StoreSettingsRequest;
use App\Modules\Settings\Requests\UpdateSettingsRequest;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $result = $this->settingsService->getLatestSettings();
        
        return response()->json($result['data'], $result['status_code']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not implemented
    }

    /**
     * Store a newly created setting in storage.
     *
     * @param  \App\Modules\Settings\Requests\StoreSettingsRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreSettingsRequest $request): JsonResponse
    {
        $result = $this->settingsService->createSetting($request->validated());
        
        return response()->json($result['data'], $result['status_code']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\Settings\Models\Settings  $settings
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Settings $settings): JsonResponse
    {
        try {
            Log::info('Displaying specific setting.', ['setting_id' => $settings->id]);

            $response = [
                'value' => true,
                'data' => $settings,
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error displaying setting: ' . $e->getMessage(), [
                'setting_id' => $settings->id ?? 'unknown'
            ]);

            $response = [
                'value' => false,
                'message' => 'An error occurred while displaying the setting.'
            ];

            return response()->json($response, 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Modules\Settings\Models\Settings  $settings
     */
    public function edit(Settings $settings)
    {
        // Not implemented
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\Settings\Requests\UpdateSettingsRequest  $request
     * @param  \App\Modules\Settings\Models\Settings  $settings
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSettingsRequest $request, Settings $settings): JsonResponse
    {
        $result = $this->settingsService->updateSetting($settings, $request->validated());
        
        return response()->json($result['data'], $result['status_code']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\Settings\Models\Settings  $settings
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Settings $settings): JsonResponse
    {
        try {
            Log::info('Deleting setting.', ['setting_id' => $settings->id]);

            $settings->delete();

            Log::info('Setting deleted successfully.', ['setting_id' => $settings->id]);

            $response = [
                'value' => true,
                'message' => 'Setting deleted successfully.'
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('Error deleting setting: ' . $e->getMessage(), [
                'setting_id' => $settings->id ?? 'unknown'
            ]);

            $response = [
                'value' => false,
                'message' => 'An error occurred while deleting the setting.'
            ];

            return response()->json($response, 500);
        }
    }
}
