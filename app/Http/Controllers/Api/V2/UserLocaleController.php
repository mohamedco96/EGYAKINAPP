<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserLocaleController extends Controller
{
    /**
     * Update user's language preference
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLocale(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'locale' => 'required|string|in:en,ar',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            $newLocale = $request->input('locale');

            // Update user's locale preference
            $user->locale = $newLocale;
            $user->save();

            // Set the current request locale immediately
            App::setLocale($newLocale);

            // Log the locale change
            Log::info('User locale updated', [
                'user_id' => $user->id,
                'old_locale' => $user->getOriginal('locale'),
                'new_locale' => $newLocale,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            return response()->json([
                'success' => true,
                'message' => __('api.profile_updated'),
                'data' => [
                    'locale' => $newLocale,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'locale' => $user->locale,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update user locale', [
                'user_id' => Auth::id(),
                'requested_locale' => $request->input('locale'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('api.error'),
                'error' => 'Failed to update language preference',
            ], 500);
        }
    }

    /**
     * Get user's current language preference
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocale(Request $request)
    {
        try {
            $user = Auth::user();
            $currentLocale = App::getLocale();

            return response()->json([
                'success' => true,
                'data' => [
                    'current_locale' => $currentLocale,
                    'user_preferred_locale' => $user->locale ?? 'en',
                    'supported_locales' => config('app.supported_locales', ['en', 'ar']),
                    'locale_names' => [
                        'en' => 'English',
                        'ar' => 'العربية',
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user locale', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('api.error'),
            ], 500);
        }
    }

    /**
     * Test endpoint to demonstrate locale switching
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function testLocaleResponse(Request $request)
    {
        $user = Auth::user();
        $currentLocale = App::getLocale();

        return response()->json([
            'success' => true,
            'current_locale' => $currentLocale,
            'user_preferred_locale' => $user->locale ?? 'en',
            'localized_messages' => [
                'welcome' => __('api.login_success'),
                'user_created' => __('api.user_created'),
                'patient_created' => __('api.patient_created'),
                'points_awarded' => __('api.points_awarded'),
                'milestone_reached' => __('api.milestone_reached', ['points' => 100]),
                'validation_failed' => __('api.validation_failed'),
                'unauthorized' => __('api.unauthorized'),
            ],
            'validation_examples' => [
                'required_email' => __('validation.required', ['attribute' => __('validation.attributes.email')]),
                'required_password' => __('validation.required', ['attribute' => __('validation.attributes.password')]),
                'invalid_email' => __('validation.email', ['attribute' => __('validation.attributes.email')]),
            ],
            'debug_info' => [
                'accept_language_header' => $request->header('Accept-Language'),
                'url_lang_param' => $request->get('lang'),
                'user_id' => $user->id,
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }
}
