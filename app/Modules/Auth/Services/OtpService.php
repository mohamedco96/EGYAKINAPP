<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use App\Services\BrevoApiService;
use Carbon\Carbon;
use Ichtrojan\Otp\Models\Otp as OtpModel;
use Otp;

class OtpService
{
    protected $brevoService;

    protected $from;

    protected $otp;

    public function __construct()
    {
        // Initialize Brevo API service
        $this->brevoService = new BrevoApiService;

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
            $user->notify(new EmailVerificationNotification);

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
        $result = $this->validateByIdentifier($user->email, $otp);

        return $result->status;
    }

    /**
     * Validate an OTP by identifier (email) and token.
     *
     * Returns an object with `status` (bool) and `message` (string), compatible
     * with the ichtrojan/laravel-otp package's validate() return shape.
     * Uses an explicit (int) cast on validity to avoid a Carbon TypeError when
     * the package's Otp model returns the value as a string from the database.
     */
    public function validateByIdentifier(string $identifier, string $token): object
    {
        $record = OtpModel::where('identifier', $identifier)
            ->where('token', $token)
            ->first();

        if (! $record) {
            return (object) ['status' => false, 'message' => 'OTP does not exist'];
        }

        if (! $record->valid) {
            $record->update(['valid' => false]);

            return (object) ['status' => false, 'message' => 'OTP is not valid'];
        }

        $record->update(['valid' => false]);

        $expiresAt = $record->created_at->addMinutes((int) $record->validity);

        if (Carbon::now()->greaterThan($expiresAt)) {
            return (object) ['status' => false, 'message' => 'OTP Expired'];
        }

        return (object) ['status' => true, 'message' => 'OTP is valid'];
    }
}
