<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocalizationTestController extends Controller
{
    /**
     * Test localization functionality
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function test(Request $request)
    {
        $currentLocale = App::getLocale();

        return response()->json([
            'success' => true,
            'current_locale' => $currentLocale,
            'accept_language_header' => $request->header('Accept-Language'),
            'messages' => [
                'login_success' => __('api.login_success'),
                'user_created' => __('api.user_created'),
                'patient_created' => __('api.patient_created'),
                'milestone_reached' => __('api.milestone_reached', ['points' => 50]),
                'validation_failed' => __('api.validation_failed'),
                'unauthorized' => __('api.unauthorized'),
            ],
            'validation_messages' => [
                'required' => __('validation.required', ['attribute' => __('validation.attributes.email')]),
                'email' => __('validation.email', ['attribute' => __('validation.attributes.email')]),
                'min' => __('validation.min.string', ['attribute' => __('validation.attributes.password'), 'min' => 8]),
            ],
            'debug_info' => [
                'supported_locales' => config('app.supported_locales'),
                'fallback_locale' => config('app.fallback_locale'),
                'request_headers' => [
                    'Accept-Language' => $request->header('Accept-Language'),
                    'Content-Type' => $request->header('Content-Type'),
                    'User-Agent' => $request->header('User-Agent'),
                ],
            ],
        ]);
    }

    /**
     * Test API response with localized messages
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Simulate login validation
        $email = $request->input('email');
        $password = $request->input('password');

        if (empty($email) || empty($password)) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_failed'),
                'errors' => [
                    'email' => empty($email) ? __('validation.required', ['attribute' => __('validation.attributes.email')]) : null,
                    'password' => empty($password) ? __('validation.required', ['attribute' => __('validation.attributes.password')]) : null,
                ],
            ], 422);
        }

        if ($email !== 'test@egyakin.com' || $password !== 'password') {
            return response()->json([
                'success' => false,
                'message' => __('api.login_failed'),
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => __('api.login_success'),
            'data' => [
                'user' => [
                    'id' => 1,
                    'name' => 'Dr. Test User',
                    'email' => $email,
                    'locale' => App::getLocale(),
                ],
                'token' => 'sample_token_123',
            ],
        ]);
    }

    /**
     * Test patient creation with localized response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPatient(Request $request)
    {
        // Simulate patient creation
        $patientName = $request->input('name');

        if (empty($patientName)) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_failed'),
                'errors' => [
                    'name' => __('validation.required', ['attribute' => __('validation.attributes.name')]),
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __('api.patient_created'),
            'data' => [
                'patient' => [
                    'id' => rand(1, 1000),
                    'name' => $patientName,
                    'created_at' => now()->toISOString(),
                ],
            ],
        ]);
    }

    /**
     * Test scoring system with localized achievement message
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function awardPoints(Request $request)
    {
        $points = $request->input('points', 10);
        $totalPoints = rand(40, 60); // Simulate current total

        $response = [
            'success' => true,
            'message' => __('api.points_awarded'),
            'data' => [
                'points_awarded' => $points,
                'total_points' => $totalPoints,
            ],
        ];

        // Check if milestone reached
        if ($totalPoints >= 50) {
            $response['achievement'] = [
                'milestone_reached' => true,
                'message' => __('api.milestone_reached', ['points' => $totalPoints]),
            ];
        }

        return response()->json($response);
    }
}
