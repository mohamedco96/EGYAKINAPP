<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class SocialAuthController extends Controller
{
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
    private function handleSocialCallback(string $provider, SocialiteUser $socialUser)
    {
        try {
            // Check if user exists by social ID
            $user = User::findBySocialId($provider, $socialUser->getId());

            if (! $user) {
                // Check if user exists by email
                $user = User::where('email', $socialUser->getEmail())->first();

                if ($user) {
                    // Link social account to existing user
                    $user->update([
                        $provider.'_id' => $socialUser->getId(),
                        'avatar' => $socialUser->getAvatar(),
                        'social_verified_at' => now(),
                    ]);
                } else {
                    // Create new user
                    $user = User::createFromSocial($provider, $socialUser);
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

            return response()->json([
                'success' => true,
                'message' => 'Authentication successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'profile_completed' => $user->profile_completed,
                        'avatar' => $user->avatar,
                        'locale' => $user->locale,
                    ],
                    'token' => $token,
                    'provider' => $provider,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("Social authentication callback failed for {$provider}: ".$e->getMessage());

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
            $googleUser = Socialite::driver('google')->userFromToken($request->input('access_token'));

            return $this->handleSocialCallback('google', $googleUser);
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
            // For Apple, we need to handle the identity token
            $identityToken = $request->input('identity_token');

            // Decode and verify the Apple identity token
            $appleUser = $this->verifyAppleIdentityToken($identityToken);

            if (! $appleUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Apple identity token',
                ], 400);
            }

            return $this->handleSocialCallback('apple', $appleUser);
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
    private function verifyAppleIdentityToken(string $identityToken)
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

            // Create a mock SocialiteUser object for Apple
            $appleUser = new SocialiteUser;
            $appleUser->id = $payload['sub'];
            $appleUser->email = $payload['email'] ?? null;
            $appleUser->name = $payload['name'] ?? null;
            $appleUser->avatar = null; // Apple doesn't provide avatar

            return $appleUser;
        } catch (\Exception $e) {
            Log::error('Apple token verification failed: '.$e->getMessage());

            return null;
        }
    }
}
