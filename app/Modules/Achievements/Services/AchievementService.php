<?php

namespace App\Modules\Achievements\Services;

use App\Modules\Achievements\Models\Achievement;
use App\Models\AppNotification;
use App\Models\User;
use App\Models\FcmToken;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all achievements
     */
    public function getAllAchievements(): array
    {
        try {
            $achievements = Achievement::all();
            Log::info('Listing all achievements', ['achievement_count' => $achievements->count()]);
            
            return [
                'value' => true,
                'data' => $achievements,
                'message' => 'Achievements retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving achievements', ['error' => $e->getMessage()]);
            return [
                'value' => false,
                'message' => 'Failed to retrieve achievements'
            ];
        }
    }

    /**
     * Create a new achievement
     */
    public function createAchievement(array $data): array
    {
        try {
            Log::info('Creating a new achievement', ['request_data' => $data]);

            $achievement = new Achievement($data);

            // Handle image upload if present
            if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                $achievement->image = $data['image']->store('achievement_images', 'public');
                Log::info('Image uploaded for achievement', ['image_path' => $achievement->image]);
            }

            $achievement->save();
            Log::info('Achievement created successfully', ['achievement_id' => $achievement->id]);

            return [
                'value' => true,
                'data' => $achievement,
                'message' => 'Achievement created successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Error creating achievement', ['error' => $e->getMessage()]);
            return [
                'value' => false,
                'message' => 'Failed to create achievement'
            ];
        }
    }

    /**
     * Get a specific achievement by ID
     */
    public function getAchievementById(int $id): array
    {
        try {
            $achievement = Achievement::find($id);

            if (!$achievement) {
                Log::warning('Achievement not found', ['achievement_id' => $id]);
                return [
                    'value' => false,
                    'message' => 'Achievement not found'
                ];
            }

            Log::info('Achievement retrieved', ['achievement_id' => $id]);
            return [
                'value' => true,
                'data' => $achievement,
                'message' => 'Achievement retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving achievement', [
                'achievement_id' => $id,
                'error' => $e->getMessage()
            ]);
            return [
                'value' => false,
                'message' => 'Failed to retrieve achievement'
            ];
        }
    }

    /**
     * Update an achievement
     */
    public function updateAchievement(int $id, array $data): array
    {
        try {
            $achievement = Achievement::find($id);

            if (!$achievement) {
                Log::warning('Achievement not found for update', ['achievement_id' => $id]);
                return [
                    'value' => false,
                    'message' => 'Achievement not found'
                ];
            }

            // Handle image upload if present
            if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
                $data['image'] = $data['image']->store('achievement_images', 'public');
                Log::info('New image uploaded for achievement', ['image_path' => $data['image']]);
            }

            $achievement->update($data);
            Log::info('Achievement updated successfully', ['achievement_id' => $id]);

            return [
                'value' => true,
                'data' => $achievement->fresh(),
                'message' => 'Achievement updated successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Error updating achievement', [
                'achievement_id' => $id,
                'error' => $e->getMessage()
            ]);
            return [
                'value' => false,
                'message' => 'Failed to update achievement'
            ];
        }
    }

    /**
     * Delete an achievement
     */
    public function deleteAchievement(int $id): array
    {
        try {
            $achievement = Achievement::find($id);

            if (!$achievement) {
                Log::warning('Achievement not found for deletion', ['achievement_id' => $id]);
                return [
                    'value' => false,
                    'message' => 'Achievement not found'
                ];
            }

            $achievement->delete();
            Log::info('Achievement deleted successfully', ['achievement_id' => $id]);

            return [
                'value' => true,
                'message' => 'Achievement deleted successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Error deleting achievement', [
                'achievement_id' => $id,
                'error' => $e->getMessage()
            ]);
            return [
                'value' => false,
                'message' => 'Failed to delete achievement'
            ];
        }
    }

    /**
     * Check and assign achievements to all users
     */
    public function checkAndAssignAchievementsForAllUsers(): array
    {
        try {
            Log::info('Starting achievement assignment for all users.');

            $achievements = Achievement::all();
            Log::info('Loaded all achievements', ['achievement_count' => $achievements->count()]);

            // Process users in chunks to manage memory and performance
            User::with(['score', 'patients', 'achievements'])->chunk(100, function ($users) use ($achievements) {
                foreach ($users as $user) {
                    Log::info('Processing user achievements', ['user_id' => $user->id]);
                    $this->processUserAchievements($user, $achievements);
                }
            });

            Log::info('All users processed for achievements.');
            
            return [
                'value' => true,
                'message' => 'Achievements processed for all users successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Error processing achievements for all users', ['error' => $e->getMessage()]);
            return [
                'value' => false,
                'message' => 'Failed to process achievements for all users'
            ];
        }
    }

    /**
     * Get achievements for a specific user
     */
    public function getUserAchievements(User $user): array
    {
        try {
            Log::info('Fetching user achievements', ['user_id' => $user->id]);

            $achievedAchievements = $user->achievements()->wherePivot('achieved', 1)->get();

            $transformedAchievements = $achievedAchievements->map(fn($achievement) => [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'type' => $achievement->type,
                'score' => (string)$achievement->score,
                'image' => $achievement->image,
                'created_at' => $achievement->created_at,
                'updated_at' => $achievement->updated_at,
                'pivot' => [
                    'user_id' => (string)$achievement->pivot->user_id,
                    'achievement_id' => (string)$achievement->pivot->achievement_id,
                    'achieved' => (string)$achievement->pivot->achieved,
                    'created_at' => $achievement->pivot->created_at,
                    'updated_at' => $achievement->pivot->updated_at,
                ]
            ]);

            Log::info('User achievements fetched', [
                'user_id' => $user->id, 
                'achievement_count' => $transformedAchievements->count()
            ]);

            return [
                'value' => true,
                'data' => $transformedAchievements,
                'message' => 'User achievements retrieved successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving user achievements', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return [
                'value' => false,
                'message' => 'Failed to retrieve user achievements'
            ];
        }
    }

    /**
     * Process achievements for a single user
     */
    private function processUserAchievements(User $user, $achievements): void
    {
        $userScore = $user->score->score ?? 0;
        $userPatientCount = $user->patients->count() ?? 0;

        Log::info('User data for achievement check', [
            'user_id' => $user->id,
            'score' => $userScore,
            'patient_count' => $userPatientCount
        ]);

        $detachIds = [];
        $attachData = [];
        $achievementUpdates = [];

        foreach ($achievements as $achievement) {
            $qualifies = $this->qualifiesForAchievement($userScore, $userPatientCount, $achievement);
            $existingAchievement = $user->achievements->find($achievement->id);

            if ($existingAchievement && !$qualifies) {
                $detachIds[] = $achievement->id;
                $achievementUpdates[] = ['achievement_id' => $achievement->id, 'status' => 'removed'];
            } elseif ($qualifies && (!$existingAchievement || !$existingAchievement->pivot->achieved)) {
                $attachData[$achievement->id] = ['achieved' => true];
                $achievementUpdates[] = ['achievement_id' => $achievement->id, 'status' => 'achieved'];

                if (!$existingAchievement) {
                    Log::info('Notifying user about new achievement', [
                        'user_id' => $user->id,
                        'achievement_id' => $achievement->id
                    ]);
                    $this->notifyAchievement($user, $achievement);
                }
            }
        }

        $this->updateUserAchievements($user, $detachIds, $attachData);
        Log::info('Achievements updated for user', ['user_id' => $user->id, 'updates' => $achievementUpdates]);
    }

    /**
     * Update user achievements
     */
    private function updateUserAchievements(User $user, array $detachIds, array $attachData): void
    {
        if (!empty($detachIds)) {
            Log::info('Detaching achievements', ['user_id' => $user->id, 'detach_ids' => $detachIds]);
            $user->achievements()->detach($detachIds);
        }

        if (!empty($attachData)) {
            Log::info('Attaching achievements', ['user_id' => $user->id, 'attach_data' => $attachData]);
            $user->achievements()->syncWithoutDetaching($attachData);
        }
    }

    /**
     * Check if a user qualifies for a specific achievement
     */
    private function qualifiesForAchievement(int $userScore, int $userPatientCount, Achievement $achievement): bool
    {
        $result = match ($achievement->type) {
            'score' => $userScore >= $achievement->score,
            'patient' => $userPatientCount >= $achievement->score,
            default => false,
        };

        Log::info('Qualification check', [
            'achievement_id' => $achievement->id,
            'type' => $achievement->type,
            'qualifies' => $result
        ]);

        return $result;
    }

    /**
     * Notify user about an achievement
     */
    private function notifyAchievement(User $user, Achievement $achievement): void
    {
        $title = 'Achievement Unlocked! 🎉';
        $body = $this->generateNotificationBody($user, $achievement);

        Log::info('Preparing notification', [
            'user_id' => $user->id,
            'achievement_id' => $achievement->id
        ]);

        $doctorIds = User::role(['Admin', 'Tester'])->pluck('id');
        $tokens = $this->notificationService->getDoctorTokens($doctorIds->toArray());

        $this->notificationService->sendPushNotification($title, $body, $tokens);
        $this->createAchievementNotification($doctorIds->toArray(), $user->name, $achievement->id, $user->id);
    }

    /**
     * Generate the notification body
     */
    private function generateNotificationBody(User $user, Achievement $achievement): string
    {
        $body = 'Dr. ' . $user->name . ' achieved a new milestone: ' . $achievement->name;
        Log::info('Generated notification body', ['body' => $body]);
        return $body;
    }

    /**
     * Create a new achievement notification for all relevant doctors
     */
    private function createAchievementNotification(array $doctorIds, string $doctorName, int $achievementId, int $doctorID): void
    {
        $notifications = array_map(fn($doctorId) => [
            'doctor_id' => $doctorId,
            'type' => 'Achievement',
            'type_id' => $achievementId,
            'content' => 'Dr. ' . $doctorName . ' earned a new achievement.',
            'type_doctor_id' => $doctorID,
            'created_at' => now(),
            'updated_at' => now(),
        ], $doctorIds);

        Log::info('Inserting notifications', ['notifications' => $notifications]);
        AppNotification::insert($notifications);
    }
}
