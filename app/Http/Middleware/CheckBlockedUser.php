<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user is blocked
            if ($user->blocked) {
                // Revoke all tokens for this user
                $user->tokens()->delete();

                // Log the blocked user access attempt
                \Log::warning('Blocked user attempted to access system', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'url' => $request->url(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'value' => false,
                    'message' => __('api.account_blocked'),
                ], 403);
            }
        }

        return $next($request);
    }
}
