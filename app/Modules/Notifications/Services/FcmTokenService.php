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
    public function storeFcmToken(string $token): array
    {
        try {
            $doctorId = Auth::id();

            // Validate token format
            if (! $this->isValidTokenFormat($token)) {
                Log::warning('Invalid FCM token format', [
                    'doctor_id' => $doctorId,
                    'token' => substr($token, 0, 20).'...',
                ]);

                return [
                    'value' => false,
                    'message' => 'Invalid FCM token format.',
                ];
            }

            // Use updateOrCreate to handle existing tokens properly
            $fcmToken = FcmToken::updateOrCreate(
                ['token' => $token],
                ['doctor_id' => $doctorId, 'updated_at' => now()]
            );

            // Limit tokens per user (keep only latest 5 tokens per user)
            $this->limitUserTokens($doctorId, 5);

            Log::info('FCM token stored successfully', [
                'doctor_id' => $doctorId,
                'token' => substr($token, 0, 20).'...',
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
                'error' => $e->getMessage(),
            ]);

            return [
                'value' => false,
                'message' => 'Failed to store FCM token.',
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
                    'token' => substr($token, 0, 20).'...',
                ]);

                return [
                    'value' => true,
                    'message' => 'FCM token removed successfully',
                ];
            }

            return [
                'value' => false,
                'message' => 'FCM token not found',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to remove FCM token', [
                'doctor_id' => Auth::id(),
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
        // FCM tokens are typically 152+ characters, alphanumeric with colons, underscores, hyphens
        return is_string($token) &&
               strlen($token) >= 152 &&
               preg_match('/^[a-zA-Z0-9:_-]+$/', $token);
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
