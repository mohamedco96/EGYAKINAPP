<?php

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Models\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SettingsService
{
    /**
     * Get the latest settings
     *
     * @return array
     */
    public function getLatestSettings(): array
    {
        try {
            $setting = Settings::latest()->first();

            if ($setting) {
                Log::info('Settings retrieved successfully.', [
                    'app_freeze' => $setting->app_freeze,
                    'force_update' => $setting->force_update,
                    'updated_at' => $setting->updated_at,
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'value' => true,
                        'app_freeze' => $setting->app_freeze,
                        'force_update' => $setting->force_update,
                        'updated_at' => $setting->updated_at,
                    ],
                    'status_code' => 200
                ];
            }

            Log::warning('No settings found.');
            
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'No settings found.'
                ],
                'status_code' => 404
            ];

        } catch (\Exception $e) {
            Log::error('Error retrieving settings: ' . $e->getMessage());
            
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'An error occurred while retrieving settings.'
                ],
                'status_code' => 500
            ];
        }
    }

    /**
     * Create a new setting
     *
     * @param array $data
     * @return array
     */
    public function createSetting(array $data): array
    {
        try {
            $setting = Settings::create($data);

            if ($setting) {
                Log::info('Setting created successfully.', [
                    'setting_id' => $setting->id,
                    'data' => $setting->toArray()
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'value' => true,
                        'data' => $setting,
                    ],
                    'status_code' => 201
                ];
            }

            Log::warning('Failed to create setting.');

            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Failed to create setting.',
                ],
                'status_code' => 400
            ];

        } catch (\Exception $e) {
            Log::error('Error creating setting: ' . $e->getMessage());

            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'An error occurred while creating the setting.'
                ],
                'status_code' => 500
            ];
        }
    }

    /**
     * Update an existing setting
     *
     * @param Settings $settings
     * @param array $data
     * @return array
     */
    public function updateSetting(Settings $settings, array $data): array
    {
        try {
            Log::info('Updating setting.', [
                'setting_id' => $settings->id,
                'incoming_data' => $data
            ]);

            // Manually update each field to avoid issues with mass assignment
            $settings->app_freeze = $data['app_freeze'] ?? $settings->app_freeze;
            $settings->force_update = $data['force_update'] ?? $settings->force_update;

            $settings->save();

            Log::info('Setting updated successfully.', [
                'setting_id' => $settings->id,
                'updated_data' => $settings->toArray()
            ]);

            return [
                'success' => true,
                'data' => [
                    'value' => true,
                    'data' => $settings,
                    'message' => 'Setting updated successfully.'
                ],
                'status_code' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Error updating setting: ' . $e->getMessage(), [
                'setting_id' => $settings->id,
                'exception' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'An error occurred while updating the setting.'
                ],
                'status_code' => 500
            ];
        }
    }
}
