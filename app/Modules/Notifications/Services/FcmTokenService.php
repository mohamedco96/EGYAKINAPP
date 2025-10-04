<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Models\FcmToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FcmTokenService
{
    /**
     * Store FCM token for authenticated user with proper validation and cleanup
     */
    public function storeFcmToken(string $token, ?string $deviceId = null, ?string $deviceType = null, ?string $appVersion = null): array
    {
        try {
            $doctorId = Auth::id();

            // Validate token format
            if (! $this->isValidTokenFormat($token)) {
                Log::warning('Invalid FCM token format', [
                    'doctor_id' => $doctorId,
                    'token' => substr($token, 0, 20).'...',
                    'device_id' => $deviceId,
                ]);

                return [
                    'value' => false,
                    'message' => __('api.invalid_fcm_token_format'),
                ];
            }

            // Prepare data for storage
            $tokenData = [
                'doctor_id' => $doctorId,
                'token' => $token,
                'updated_at' => now(),
            ];

            if ($deviceId) {
                $tokenData['device_id'] = $deviceId;
            }

            if ($deviceType) {
                $tokenData['device_type'] = strtolower($deviceType);
            }

            if ($appVersion) {
                $tokenData['app_version'] = $appVersion;
            }

            // Use unique constraint on doctor_id + device_id if device_id is provided
            $uniqueFields = $deviceId
                ? ['doctor_id' => $doctorId, 'device_id' => $deviceId]
                : ['token' => $token];

            $fcmToken = FcmToken::updateOrCreate($uniqueFields, $tokenData);

            // Limit tokens per user (keep only latest 10 tokens per user to accommodate multiple devices)
            $this->limitUserTokens($doctorId, 10);

            Log::info('FCM token stored successfully', [
                'doctor_id' => $doctorId,
                'token' => substr($token, 0, 20).'...',
                'device_id' => $deviceId,
                'device_type' => $deviceType,
                'was_updated' => ! $fcmToken->wasRecentlyCreated,
            ]);

            return [
                'value' => true,
                'message' => 'FCM token stored successfully',
                'data' => $fcmToken,
            ];
        } catch (\Exception $e) {
            Log::error('Error storing FCM token', [
                'doctor_id' => Auth::id(),
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'value' => false,
                'message' => __('api.failed_to_store_fcm_token'),
            ];
        }
    }

    /**
     * Get FCM tokens for specific doctors
     */
    public function getTokensForDoctors(array $doctorIds): array
    {
        try {
            $tokens = FcmToken::whereIn('doctor_id', $doctorIds)
                ->pluck('token')
                ->toArray();

            Log::info('Retrieved FCM tokens for doctors', [
                'doctor_count' => count($doctorIds),
                'token_count' => count($tokens),
            ]);

            return $tokens;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve FCM tokens for doctors', [
                'doctor_ids' => $doctorIds,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get all FCM tokens
     */
    public function getAllTokens(): array
    {
        try {
            $tokens = FcmToken::pluck('token')->toArray();

            Log::info('Retrieved all FCM tokens', [
                'token_count' => count($tokens),
            ]);

            return $tokens;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve all FCM tokens', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Remove FCM token for authenticated user
     */
    public function removeFcmToken(?string $token = null, ?string $deviceId = null): array
    {
        try {
            $doctorId = Auth::id();

            $query = FcmToken::where('doctor_id', $doctorId);

            if ($token) {
                $query->where('token', $token);
            }

            if ($deviceId) {
                $query->where('device_id', $deviceId);
            }

            if (! $token && ! $deviceId) {
                return [
                    'value' => false,
                    'message' => __('api.token_or_device_id_required'),
                ];
            }

            $deleted = $query->delete();

            if ($deleted) {
                Log::info('FCM token removed successfully', [
                    'doctor_id' => $doctorId,
                    'token' => $token ? substr($token, 0, 20).'...' : null,
                    'device_id' => $deviceId,
                    'deleted_count' => $deleted,
                ]);

                return [
                    'value' => true,
                    'message' => 'FCM token removed successfully',
                    'deleted_count' => $deleted,
                ];
            }

            return [
                'value' => false,
                'message' => 'FCM token not found',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to remove FCM token', [
                'doctor_id' => Auth::id(),
                'token' => $token,
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Clean up expired or invalid tokens
     */
    public function cleanupInvalidTokens(): int
    {
        try {
            // Remove very old tokens (older than 6 months)
            $deletedCount = FcmToken::where('created_at', '<', now()->subMonths(6))->delete();

            Log::info('Cleaned up old FCM tokens', [
                'deleted_count' => $deletedCount,
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup FCM tokens', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Validate FCM token format
     */
    private function isValidTokenFormat(string $token): bool
    {
        // FCM tokens are alphanumeric with colons, underscores, hyphens (removed min length requirement)
        return is_string($token) &&
               strlen($token) > 0 &&
               preg_match('/^[a-zA-Z0-9:_-]+$/', $token);
    }

    /**
     * Validate device ID format
     */
    private function isValidDeviceId(string $deviceId): bool
    {
        // Device IDs can be UUIDs, iOS identifiers, Android IDs, etc.
        // Allow alphanumeric, hyphens, and underscores, length 10-50 characters
        return is_string($deviceId) &&
               strlen($deviceId) >= 10 &&
               strlen($deviceId) <= 50 &&
               preg_match('/^[a-zA-Z0-9_-]+$/', $deviceId);
    }

    /**
     * Get tokens for a specific device
     */
    public function getTokensForDevice(string $deviceId): array
    {
        try {
            $tokens = FcmToken::forDevice($deviceId)->pluck('token')->toArray();

            Log::info('Retrieved FCM tokens for device', [
                'device_id' => $deviceId,
                'token_count' => count($tokens),
            ]);

            return $tokens;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve FCM tokens for device', [
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get user's devices information
     */
    public function getUserDevices(?int $userId = null): array
    {
        try {
            $userId = $userId ?? Auth::id();

            $devices = FcmToken::forUser($userId)
                ->select('device_id', 'device_type', 'app_version', 'updated_at')
                ->whereNotNull('device_id')
                ->orderBy('updated_at', 'desc')
                ->get()
                ->toArray();

            Log::info('Retrieved user devices', [
                'user_id' => $userId,
                'device_count' => count($devices),
            ]);

            return $devices;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user devices', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Limit number of tokens per user (keep only the most recent ones)
     */
    private function limitUserTokens(int $doctorId, int $maxTokens = 5): void
    {
        try {
            $tokenCount = FcmToken::where('doctor_id', $doctorId)->count();

            if ($tokenCount > $maxTokens) {
                // Get tokens to delete (oldest ones)
                $tokensToDelete = FcmToken::where('doctor_id', $doctorId)
                    ->orderBy('updated_at', 'asc')
                    ->take($tokenCount - $maxTokens)
                    ->pluck('id');

                $deletedCount = FcmToken::whereIn('id', $tokensToDelete)->delete();

                Log::info('Limited user FCM tokens', [
                    'doctor_id' => $doctorId,
                    'deleted_count' => $deletedCount,
                    'remaining_count' => $maxTokens,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to limit user FCM tokens', [
                'doctor_id' => $doctorId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clean up specific invalid tokens
     */
    public function removeInvalidTokens(array $invalidTokens): int
    {
        try {
            if (empty($invalidTokens)) {
                return 0;
            }

            $deletedCount = FcmToken::whereIn('token', $invalidTokens)->delete();

            Log::info('Removed invalid FCM tokens', [
                'deleted_count' => $deletedCount,
                'tokens_count' => count($invalidTokens),
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Failed to remove invalid FCM tokens', [
                'error' => $e->getMessage(),
                'tokens_count' => count($invalidTokens),
            ]);

            return 0;
        }
    }
}
