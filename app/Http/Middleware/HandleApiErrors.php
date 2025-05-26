<?php

namespace App\Http\Middleware;

use App\Services\SecurityLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleApiErrors
{
    protected $securityLog;

    public function __construct(SecurityLogService $securityLog)
    {
        $this->securityLog = $securityLog;
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (Throwable $e) {
            $this->logError($request, $e);

            // Don't expose internal errors in production
            $message = config('app.debug') ? $e->getMessage() : 'An error occurred';

            return response()->json([
                'value' => false,
                'message' => $message,
            ], $this->getStatusCode($e));
        }
    }

    protected function logError(Request $request, Throwable $e)
    {
        $context = [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if ($request->user()) {
            $context['user_id'] = $request->user()->id;
        }

        $this->securityLog->logRateLimitEvent('API Error', $context);
        Log::error('API Error', $context);
    }

    protected function getStatusCode(Throwable $e)
    {
        // Map common exceptions to appropriate HTTP status codes
        $statusCode = 500;

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $statusCode = 422;
        } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
            $statusCode = 401;
        } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            $statusCode = 403;
        } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $statusCode = 404;
        } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $statusCode = $e->getStatusCode();
        }

        return $statusCode;
    }
}
