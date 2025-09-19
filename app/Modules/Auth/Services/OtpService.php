<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Notifications\EmailVerificationNotification;
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
     * Send OTP verification email using enhanced notification
     */
    public function sendOtpEmail(User $user): bool
    {
        try {
            // Generate new OTP (this will be used by the notification)
            $otp = $this->generateOtp($user);

            // Send enhanced verification notification
            $user->notify(new EmailVerificationNotification());

            // Log the result
            logger()->info('Enhanced OTP email sent successfully', [
                'user' => $user->id,
                'email' => $user->email,
                'otp_generated' => $otp,
            ]);

            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to send enhanced OTP email', [
                'user' => $user->id,
                'email' => $user->email,
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
