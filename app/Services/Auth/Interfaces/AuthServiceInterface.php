<?php

namespace App\Services\Auth\Interfaces;

interface AuthServiceInterface
{
    public function register(array $data): array;
    public function login(array $data): array;
    public function logout($user): array;
} 