<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserService
{
    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }

    public function updateUser($user, array $data)
    {
        DB::beginTransaction();
        try {
            if (isset($data['email']) && $data['email'] !== $user->email) {
                $data['email'] = strtolower($data['email']);
                $data['email_verified_at'] = null;
            }

            $sanitized = array_map('trim', $data);
            $user->fill($sanitized);
            $user->save();

            DB::commit();

            Log::info('User updated', [
                'user_id' => $user->id,
                'fields' => array_keys($data),
            ]);

            return [
                'value' => true,
                'message' => 'User Updated Successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateUserById($id, array $data)
    {
        $user = User::find($id);

        if (! $user) {
            Log::warning("No user found with ID {$id}");

            return [
                'value' => false,
                'message' => 'No User was found',
            ];
        }

        if (isset($data['isSyndicateCardRequired'])) {
            $this->handleSyndicateCardUpdate($user, $data['isSyndicateCardRequired']);
        }

        $user->fill($data);
        $user->save();

        Log::info("User {$user->id} updated successfully", $user->toArray());

        return [
            'value' => true,
            'message' => 'User Updated Successfully',
            'data' => $user,
        ];
    }

    public function uploadProfileImage($user, $image)
    {
        $fileName = sprintf('%s_profileImage_%s.%s',
            $user->name,
            time(),
            $image->getClientOriginalExtension()
        );

        DB::beginTransaction();
        try {
            $path = $image->storeAs('profile_images', $fileName, 'public');

            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }

            $user->update(['image' => $path]);

            DB::commit();

            $imageUrl = config('app.url').'/storage/'.$path;

            Log::info('Profile image updated', [
                'user_id' => $user->id,
                'path' => $path,
            ]);

            return [
                'value' => true,
                'message' => 'Profile image uploaded successfully.',
                'image' => $imageUrl,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function uploadSyndicateCard($user, $image)
    {
        $fileName = time().'_'.$image->getClientOriginalName();
        $path = $image->storeAs('syndicate_card', $fileName, 'public');
        $relativePath = 'storage/'.$path;
        $imageUrl = config('app.url').'/'.'storage/'.$path;

        $user->update([
            'syndicate_card' => $path,
            'isSyndicateCardRequired' => 'Pending',
        ]);

        $this->notifyAdminsAboutSyndicateCard($user);

        return [
            'value' => true,
            'message' => 'User syndicate card uploaded successfully.',
            'image' => $imageUrl,
        ];
    }

    protected function handleSyndicateCardUpdate($user, $status)
    {
        if ($user->isSyndicateCardRequired === 'Pending') {
            $messages = $this->getSyndicateCardMessages($status);

            AppNotification::create([
                'doctor_id' => $user->id,
                'type' => 'Other',
                'content' => $messages['body'],
                'type_doctor_id' => $user->id,
            ]);

            $tokens = FcmToken::where('doctor_id', $user->id)
                ->pluck('token')
                ->toArray();

            $this->notificationController->sendPushNotification(
                $messages['title'],
                $messages['body'],
                $tokens
            );
        }
    }

    protected function getSyndicateCardMessages($status)
    {
        switch ($status) {
            case 'Required':
                return [
                    'title' => 'Syndicate Card Rejected ❌',
                    'body' => 'Your Syndicate Card was rejected. Please upload the correct one.',
                ];
            case 'Verified':
                return [
                    'title' => 'Syndicate Card Approved ✅',
                    'body' => 'Congratulations! 🎉 Your Syndicate Card has been approved.',
                ];
            default:
                throw new \Exception('Invalid value for isSyndicateCardRequired.');
        }
    }

    protected function notifyAdminsAboutSyndicateCard($user)
    {
        $doctors = User::role(['Admin', 'Tester'])
            ->where('id', '!=', $user->id)
            ->with('fcmTokens:id,doctor_id,token')
            ->get();

        $notifications = $doctors->map(function ($doctor) use ($user) {
            return [
                'doctor_id' => $doctor->id,
                'type' => 'Syndicate Card',
                'content' => 'Dr. '.$user->name.' has uploaded a new Syndicate Card for approval.',
                'type_doctor_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        AppNotification::insert($notifications);

        $tokens = $doctors->pluck('fcmTokens.*.token')
            ->flatten()
            ->filter()
            ->toArray();

        $this->notificationController->sendPushNotification(
            'New Syndicate Card Pending Approval 📋',
            'Dr. '.$user->name.' has uploaded a new Syndicate Card for approval.',
            $tokens
        );
    }
}
