<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Services\BrevoApiService;
use Otp;

class OtpService
{
    protected $brevoService;

    protected $from;

    protected $otp;

    public function __construct()
    {
        // Initialize Brevo API service
        $this->brevoService = new BrevoApiService();

        // Get from configuration
        $this->from = [
            'name' => config('mail.from.name', 'EGYAKIN'),
            'email' => config('mail.from.address', 'noreply@egyakin.com'),
        ];

        // Initialize OTP
        $this->otp = new Otp;
    }

    /**
     * Generate a new OTP code for a user
     */
    public function generateOtp(User $user): string
    {
        // Generate a 4-digit OTP that expires in 10 minutes
        $otpResult = $this->otp->generate($user->email, 'numeric', 4, 10);

        return $otpResult->token;
    }

    /**
     * Send OTP verification email
     */
    public function sendOtpEmail(User $user): bool
    {
        try {
            // Generate new OTP
            $otp = $this->generateOtp($user);

            // Prepare email content
            $subject = 'Your Verification Code';
            $text = "Hello {$user->name},\n\n"
                  ."Your OTP verification code is: {$otp}\n\n"
                  ."This code will expire in 10 minutes.\n\n"
                  ."If you didn't request this code, please ignore this email.";

            $html = '<html><body>'
                  .'<h2>Email Verification</h2>'
                  ."<p>Hello {$user->name},</p>"
                  ."<p>Your OTP verification code is: <strong>{$otp}</strong></p>"
                  .'<p>This code will expire in 10 minutes.</p>'
                  ."<p>If you didn't request this code, please ignore this email.</p>"
                  .'</body></html>';

            // Send email using Brevo API
            $result = $this->brevoService->sendEmail(
                $user->email,
                $subject,
                $html,
                $text,
                $this->from
            );

            if ($result['success']) {
                // Log the result
                logger()->info('OTP email sent via Brevo API', [
                    'user' => $user->id,
                    'message_id' => $result['message_id'],
                ]);

                return true;
            } else {
                logger()->error('Brevo API failed to send OTP email', [
                    'user' => $user->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                return false;
            }
        } catch (\Exception $e) {
            logger()->error('Failed to send OTP email', [
                'user' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify an OTP for a user
     */
    public function verifyOtp(User $user, string $otp): bool
    {
        $validation = $this->otp->validate($user->email, $otp);

        return $validation->status;
    }
}
