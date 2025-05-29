<?php

namespace App\Repositories\User;

use App\Models\User;
use App\Models\FcmToken;
use App\Models\AppNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function create(array $data): User
    {
        $sanitized = array_map('trim', $data);
        
        return User::create([
            'name' => $sanitized['name'],
            'lname' => $sanitized['lname'],
            'email' => strtolower($sanitized['email']),
            'password' => Hash::make($sanitized['password']),
            'passwordValue' => encrypt($sanitized['password']),
            'age' => $sanitized['age'] ?? null,
            'specialty' => $sanitized['specialty'] ?? null,
            'workingplace' => $sanitized['workingplace'] ?? null,
            'phone' => $sanitized['phone'] ?? null,
            'job' => $sanitized['job'] ?? null,
            'highestdegree' => $sanitized['highestdegree'] ?? null,
            'registration_number' => $sanitized['registration_number'],
        ]);
    }

    public function findById($id): ?User
    {
        return User::find($id);
    }

    public function findByIdWithRelations($id): ?User
    {
        return User::with([
            'patients' => function($q) {
                $q->select('id', 'doctor_id');
            },
            'score:id,doctor_id,score',
            'posts:id,doctor_id',
            'saves:id,doctor_id'
        ])
        ->select('id', 'name', 'lname', 'image', 'email', 'specialty', 'workingplace')
        ->find($id);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function storeFcmToken($userId, $token): void
    {
        FcmToken::updateOrCreate(
            ['token' => $token],
            ['doctor_id' => $userId]
        );
    }

    public function getAdminAndTesterUsers($excludeUserId = null)
    {
        $query = User::role(['Admin', 'Tester']);
        
        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->with('fcmTokens:id,doctor_id,token')->get();
    }

    public function createNotification(array $data): AppNotification
    {
        return AppNotification::create($data);
    }

    public function createNotifications(array $notifications): void
    {
        AppNotification::insert($notifications);
    }
} 