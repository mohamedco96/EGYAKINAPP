<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialAuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            Log::error('Google OAuth redirect failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to redirect to Google authentication',
            ], 500);
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Check if there's an error from Google
            if ($request->has('error')) {
                Log::warning('Google OAuth error: '.$request->get('error'));

                return response()->json([
                    'success' => false,
                    'message' => 'Google authentication was cancelled or failed',
                ], 400);
            }

            $socialUser = Socialite::driver('google')->user();

            return $this->handleSocialCallback('google', $socialUser);
        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Google authentication failed',
            ], 400);
        }
    }

    /**
     * Redirect to Apple OAuth
     */
    public function redirectToApple()
    {
        try {
            return Socialite::driver('apple')->redirect();
        } catch (\Exception $e) {
            Log::error('Apple OAuth redirect failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to redirect to Apple authentication',
            ], 500);
        }
    }

    /**
     * Handle Apple OAuth callback
     */
    public function handleAppleCallback(Request $request)
    {
        try {
            // Check if there's an error from Apple
            if ($request->has('error')) {
                Log::warning('Apple OAuth error: '.$request->get('error'));

                return response()->json([
                    'success' => false,
                    'message' => 'Apple authentication was cancelled or failed',
                ], 400);
            }

            $socialUser = Socialite::driver('apple')->user();

            return $this->handleSocialCallback('apple', $socialUser);
        } catch (\Exception $e) {
            Log::error('Apple OAuth callback failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Apple authentication failed',
            ], 400);
        }
    }

    /**
     * Handle social authentication callback
     */
    private function handleSocialCallback(string $provider, SocialiteUser $socialUser, ?Request $request = null)
    {
        try {
            // Validate required social user data
            if (! $socialUser->getId()) {
                Log::error("Missing user ID from {$provider}");

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user data from social provider',
                ], 400);
            }

            // Check if user exists by social ID
            $user = User::findBySocialId($provider, $socialUser->getId());

            if (! $user) {
                // Check if user exists by email (if email is provided)
                if ($socialUser->getEmail()) {
                    $user = User::where('email', $socialUser->getEmail())->first();
                }

                if ($user) {
                    // Link social account to existing user
                    // Check if email is not a placeholder
                    $hasRealEmail = $socialUser->getEmail() &&
                                    ! str_contains($socialUser->getEmail(), '@apple-user.egyakin.com') &&
                                    ! str_contains($socialUser->getEmail(), '@google-user.egyakin.com');

                    $updateData = [
                        $provider.'_id' => $socialUser->getId(),
                        'avatar' => $socialUser->getAvatar(),
                        'social_verified_at' => now(),
                    ];

                    // Update email if social provider gives a real email
                    if ($hasRealEmail && $user->email !== $socialUser->getEmail()) {
                        $updateData['email'] = $socialUser->getEmail();
                        $updateData['profile_completed'] = true;
                    }

                    // Mark email as verified if real email provided and not already verified
                    if ($hasRealEmail && ! $user->email_verified_at) {
                        $updateData['email_verified_at'] = now();
                    }

                    // Set user_type to 'normal' if not already set
                    if (! $user->user_type) {
                        $updateData['user_type'] = 'normal';
                    }

                    $user->update($updateData);

                    // Assign 'user' role if no role assigned yet
                    if (! $user->roles()->exists()) {
                        $user->assignSingleRole('user');
                    }
                } else {
                    // Create new user
                    $user = User::createFromSocial($provider, $socialUser);
                }
            } else {
                // Update existing social user avatar if available
                if ($socialUser->getAvatar() && $user->avatar !== $socialUser->getAvatar()) {
                    $user->update(['avatar' => $socialUser->getAvatar()]);
                }
            }

            // Check if user is blocked
            if ($user->blocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is blocked',
                ], 403);
            }

            // Generate token for API authentication
            $token = $user->createToken('social-auth')->plainTextToken;

            // Store FCM token if provided
            if ($request && $request->filled('fcmToken')) {
                $this->authService->storeFcmToken(
                    $user->id,
                    $request->input('fcmToken'),
                    $request->input('deviceId'),
                    $request->input('deviceType'),
                    $request->input('appVersion')
                );
            }

            // Determine if profile is complete - should be false only if email is missing/placeholder
            $isPlaceholderEmail = str_contains($user->email, '@apple-user.egyakin.com') ||
                                  str_contains($user->email, '@google-user.egyakin.com');
            $profileCompleted = ! $isPlaceholderEmail;

            // Update profile_completed if it has changed
            if ($user->profile_completed !== $profileCompleted) {
                $user->update(['profile_completed' => $profileCompleted]);
            }

            // Get user's single role (enforcing one role per user)
            $role = $user->roles()->first();
            $roleName = $role ? $role->name : null;

            // Get permissions from role only (not direct permissions)
            $permissions = $role ? $role->permissions()->pluck('name')->values() : collect();

            // Convert user to array and add role to data
            $userData = $user->toArray();
            $userData['image'] = $user->image; // Force accessor call — on first login, image is absent from attributes
            $userData['role'] = $roleName;

            return response()->json([
                'success' => true,
                'message' => 'Authentication successful',
                'data' => [
                    'user' => $userData,
                    'token' => $token,
                    'provider' => $provider,
                    'permissions' => $permissions,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Social authentication callback failed for {$provider}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
            ], 500);
        }
    }

    /**
     * API endpoint for Google authentication
     */
    public function googleAuth(Request $request)
    {
        try {
            $request->validate([
                'access_token' => 'required|string',
                'fcmToken'     => 'nullable|string',
                'deviceId'     => 'nullable|string',
                'deviceType'   => 'nullable|string',
                'appVersion'   => 'nullable|string',
            ]);

            $googleUser = Socialite::driver('google')->userFromToken($request->input('access_token'));

            return $this->handleSocialCallback('google', $googleUser, $request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Google API authentication failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Google authentication failed',
            ], 400);
        }
    }

    /**
     * API endpoint for Apple authentication
     */
    public function appleAuth(Request $request)
    {
        try {
            $request->validate([
                'identity_token' => 'required|string',
                'fcmToken'       => 'nullable|string',
                'deviceId'       => 'nullable|string',
                'deviceType'     => 'nullable|string',
                'appVersion'     => 'nullable|string',
            ]);

            // For Apple, we need to handle the identity token
            $identityToken = $request->input('identity_token');

            // Decode and verify the Apple identity token
            $appleUser = $this->verifyAppleIdentityToken($identityToken, $request);

            if (! $appleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Apple identity token',
                ], 400);
            }

            return $this->handleSocialCallback('apple', $appleUser, $request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Apple API authentication failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Apple authentication failed',
            ], 400);
        }
    }

    /**
     * Verify Apple identity token
     */
    private function verifyAppleIdentityToken(string $identityToken, Request $request)
    {
        try {
            // Decode the JWT token
            $tokenParts = explode('.', $identityToken);
            if (count($tokenParts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1])), true);

            if (! $payload || ! isset($payload['sub'])) {
                return null;
            }

            // Get optional user info from request (Apple sends this on first sign-in only)
            $userInfo = $request->input('user');
            $name = null;

            if ($userInfo && is_array($userInfo)) {
                $firstName = $userInfo['name']['firstName'] ?? '';
                $lastName = $userInfo['name']['lastName'] ?? '';
                $name = trim($firstName.' '.$lastName);
            }

            // Create a SocialiteUser object for Apple with proper structure
            $appleUser = new SocialiteUser;
            $appleUser->map([
                'id' => $payload['sub'],
                'email' => $payload['email'] ?? null,
                'name' => $name,
                'avatar' => null, // Apple doesn't provide avatar
            ]);

            return $appleUser;
        } catch (\Exception $e) {
            Log::error('Apple token verification failed: '.$e->getMessage());

            return null;
        }
    }
}
