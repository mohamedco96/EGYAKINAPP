<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Basic request validation
        if ($request->isMethod('POST') || $request->isMethod('PUT')) {
            // Validate Content-Type for POST/PUT requests
            if (!$request->isJson() && !$request->isMethod('GET')) {
                return response()->json([
                    'message' => 'Content-Type must be application/json for POST/PUT requests'
                ], 415);
            }
        }

        return $next($request);
    }
} 