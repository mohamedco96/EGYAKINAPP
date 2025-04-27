<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Mailgun\Mailgun;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    /**
     * Send verification email
     */
    public function sendVerificationEmail()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated'
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
            // Option 1: Using Laravel Mail (recommended)
            $this->sendViaLaravelMail($user, $verificationUrl);
            
            // OR Option 2: Using Mailgun API directly
            // $this->sendViaMailgunApi($user, $verificationUrl);

            return response()->json([
                'status' => 'success',
                'message' => 'Verification email sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send verification email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify email
     */
    public function verifyEmail(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!$request->hasValidSignature()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired verification link'
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully'
        ]);
    }

    /**
     * Method 1: Send using Laravel's Mail facade (recommended)
     */
    protected function sendViaLaravelMail($user, $verificationUrl)
    {
        Mail::to($user->email)->send(new VerifyEmail($verificationUrl));
        
        // For better performance, queue the email:
        // Mail::to($user->email)->queue(new VerifyEmail($verificationUrl));
    }

    /**
     * Method 2: Send using Mailgun API directly
     */
    protected function sendViaMailgunApi($user, $verificationUrl)
    {
        $mg = Mailgun::create(config('services.mailgun.secret'));
        
        $domain = config('services.mailgun.domain');
        $fromEmail = config('mail.from.address');
        $fromName = config('mail.from.name');

        $mg->messages()->send($domain, [
            'from'    => "{$fromName} <{$fromEmail}>",
            'to'      => "{$user->name} <{$user->email}>",
            'subject' => 'Verify Your Email Address',
            'html'    => view('emails.verify', [
                'user' => $user,
                'url' => $verificationUrl
            ])->render(),
            'text'    => "Please verify your email by visiting: {$verificationUrl}"
        ]);
    }
}