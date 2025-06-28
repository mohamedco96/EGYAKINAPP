<?php

namespace App\Modules\Notifications\Services;

use App\Models\FcmToken;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FcmTokenService
{
    /**
     * Store FCM token for authenticated user
     */
    public function storeFcmToken(string $token): array
    {
        try {
            $doctorId = Auth::id();

            // Attempt to create a new FCM token
            $fcmToken = FcmToken::create([
                'doctor_id' => $doctorId,
                'token' => $token,
            ]);

            Log::info('FCM token stored successfully', [
                'doctor_id' => $doctorId,
                'token' => substr($token, 0, 20) . '...'
            ]);

            return [
                'value' => true,
                'message' => 'FCM token stored successfully',
                'data' => $fcmToken
            ];
        } catch (QueryException $e) {
            // Handle duplicate token error
            if ($e->errorInfo[1] == 1062) {
                Log::warning('Duplicate FCM token attempted', [
                    'doctor_id' => Auth::id(),
                    'token' => substr($token, 0, 20) . '...'
                ]);

                return [
                    'value' => false,
                    'message' => 'The FCM token already exists.',
                ];
            }

            Log::error('Database error while storing FCM token', [
                'doctor_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            throw $e;
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
                'token_count' => count($tokens)
            ]);

            return $tokens;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve FCM tokens for doctors', [
                'doctor_ids' => $doctorIds,
                'error' => $e->getMessage()
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
                'token_count' => count($tokens)
            ]);

            return $tokens;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve all FCM tokens', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Remove FCM token for authenticated user
     */
    public function removeFcmToken(string $token): array
    {
        try {
            $doctorId = Auth::id();
            
            $deleted = FcmToken::where('doctor_id', $doctorId)
                ->where('token', $token)
                ->delete();

            if ($deleted) {
                Log::info('FCM token removed successfully', [
                    'doctor_id' => $doctorId,
                    'token' => substr($token, 0, 20) . '...'
                ]);

                return [
                    'value' => true,
                    'message' => 'FCM token removed successfully'
                ];
            }

            return [
                'value' => false,
                'message' => 'FCM token not found'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to remove FCM token', [
                'doctor_id' => Auth::id(),
                'error' => $e->getMessage()
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
            // This could be expanded to include logic for detecting invalid tokens
            // For now, we'll just remove very old tokens (older than 6 months)
            $deletedCount = FcmToken::where('created_at', '<', now()->subMonths(6))->delete();

            Log::info('Cleaned up old FCM tokens', [
                'deleted_count' => $deletedCount
            ]);

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup FCM tokens', [
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }
}
