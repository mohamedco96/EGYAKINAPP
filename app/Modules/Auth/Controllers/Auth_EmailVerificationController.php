<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\User;
use App\Services\BrevoApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * Send verification email
     */
    public function sendVerificationEmail()
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.user_not_authenticated'),
            ], 401);
        }

        $user = User::where('email', $user->email)->first();

        // Generate signed verification URL (valid for 60 minutes)
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id]
        );

        try {
            // Send via Brevo API
            $this->sendViaBrevoApi($user, $verificationUrl);

            return response()->json([
                'status' => 'success',
                'message' => __('api.verification_email_sent_successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.failed_to_send_verification_email'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify email
     */
    public function verifyEmail(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (! $request->hasValidSignature()) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.invalid_or_expired_verification_link'),
            ], 401);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json([
            'status' => 'success',
            'message' => __('api.email_verified_successfully'),
        ]);
    }

    /**
     * Send using Brevo API
     */
    protected function sendViaBrevoApi($user, $verificationUrl)
    {
        $brevoService = new BrevoApiService();

        // Create the VerifyEmail mailable to get content
        $verifyEmail = new VerifyEmail($verificationUrl);
        $envelope = $verifyEmail->envelope();
        $content = $verifyEmail->content();

        // Generate HTML content from the view
        $htmlContent = view($content->view, $content->with)->render();

        // Generate text content
        $textContent = "Please verify your email address by clicking the link below:\n\n{$verificationUrl}\n\nIf you did not create an account with EGYAKIN, please ignore this email.\n\nBest regards,\nEGYAKIN Development Team";

        $result = $brevoService->sendEmail(
            $user->email,
            $envelope->subject,
            $htmlContent,
            $textContent,
            [
                'name' => config('mail.from.name'),
                'email' => config('mail.from.address'),
            ]
        );

        if (! $result['success']) {
            throw new \Exception('Brevo API Error: '.($result['error'] ?? 'Unknown error'));
        }
    }
}
