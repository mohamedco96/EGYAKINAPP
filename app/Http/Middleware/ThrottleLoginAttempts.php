<?php

namespace App\Http\Middleware;

use App\Services\SecurityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThrottleLoginAttempts
{
    protected $securityLog;

    protected $maxAttempts = 5;

    protected $decayMinutes = 15;

    public function __construct(SecurityLogService $securityLog)
    {
        $this->securityLog = $securityLog;
    }

    public function handle(Request $request, Closure $next)
    {
        $key = 'login_attempts_'.$request->ip();
        $attempts = Cache::get($key, 0);

        if ($attempts >= $this->maxAttempts) {
            $this->securityLog->logRateLimitEvent('Login attempts exceeded', [
                'ip' => $request->ip(),
                'attempts' => $attempts,
                'email' => $request->input('email'),
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Too many login attempts. Please try again in '.$this->decayMinutes.' minutes.',
            ], 429);
        }

        Cache::put($key, $attempts + 1, now()->addMinutes($this->decayMinutes));

        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            Cache::forget($key);
            $this->securityLog->logAuthEvent('Login successful', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
            ]);
        } else {
            $this->securityLog->logAuthEvent('Login failed', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
                'attempts' => $attempts + 1,
            ]);
        }

        return $response;
    }
}
