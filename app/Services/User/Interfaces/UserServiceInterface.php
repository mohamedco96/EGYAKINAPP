<?php

namespace App\Services\User\Interfaces;

interface UserServiceInterface
{
    public function updateUser($user, array $data): array;
    public function updateUserById($id, array $data): array;
    public function uploadProfileImage($user, $image): array;
    public function uploadSyndicateCard($user, $image): array;
} 