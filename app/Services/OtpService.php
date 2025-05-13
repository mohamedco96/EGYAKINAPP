<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Mailgun\Mailgun;
use Otp;

class OtpService
{
    protected $mailgun;
    protected $domain;
    protected $from;
    protected $otp;
    
    public function __construct()
    {
        // Get configuration from .env
        $apiKey = config('services.mailgun.secret');
        $endpoint = config('services.mailgun.endpoint', 'https://api.eu.mailgun.net');
        $this->domain = config('services.mailgun.domain', 'egyakin.com');
        $this->from = config('services.mailgun.from', 'OTP Verification <verification@egyakin.com>');
        
        // Initialize Mailgun client
        $this->mailgun = Mailgun::create($apiKey, $endpoint);
        
        // Initialize OTP
        $this->otp = new Otp;
    }
    
    /**
     * Generate a new OTP code for a user
     * 
     * @param User $user
     * @return string
     */
    public function generateOtp(User $user): string
    {
        // Generate a 4-digit OTP that expires in 10 minutes
        $otpResult = $this->otp->generate($user->email, 'numeric', 4, 10);
        return $otpResult->token;
    }
    
    /**
     * Send OTP verification email
     * 
     * @param User $user
     * @return bool
     */
    public function sendOtpEmail(User $user): bool
    {
        try {
            // Generate new OTP
            $otp = $this->generateOtp($user);
            
            // Prepare email content
            $subject = 'Your Verification Code';
            $text = "Hello {$user->name},\n\n"
                  . "Your OTP verification code is: {$otp}\n\n"
                  . "This code will expire in 10 minutes.\n\n"
                  . "If you didn't request this code, please ignore this email.";
            
            $html = "<html><body>"
                  . "<h2>Email Verification</h2>"
                  . "<p>Hello {$user->name},</p>"
                  . "<p>Your OTP verification code is: <strong>{$otp}</strong></p>"
                  . "<p>This code will expire in 10 minutes.</p>"
                  . "<p>If you didn't request this code, please ignore this email.</p>"
                  . "</body></html>";
            
            // Send email using Mailgun
            $result = $this->mailgun->messages()->send(
                $this->domain,
                [
                    'from'    => $this->from,
                    'to'      => "{$user->name} <{$user->email}>",
                    'subject' => $subject,
                    'text'    => $text,
                    'html'    => $html
                ]
            );
            
            // Log the result
            logger()->info('OTP email sent', ['user' => $user->id, 'result' => $result->getMessage()]);
            
            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to send OTP email', [
                'user' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Verify an OTP for a user
     * 
     * @param User $user
     * @param string $otp
     * @return bool
     */
    public function verifyOtp(User $user, string $otp): bool
    {
        $validation = $this->otp->validate($user->email, $otp);
        return $validation->status;
    }
}