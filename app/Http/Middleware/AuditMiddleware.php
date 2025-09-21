<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip auditing for certain routes/conditions
        if ($this->shouldSkipAudit($request)) {
            return $next($request);
        }

        // Store start time for performance tracking
        $startTime = microtime(true);

        // Process the request
        $response = $next($request);

        // Log the request after processing (in background to avoid performance impact)
        $this->logRequestAsync($request, $response, $startTime);

        return $response;
    }

    /**
     * Determine if the request should be skipped from auditing.
     */
    protected function shouldSkipAudit(Request $request): bool
    {
        // Skip health checks and monitoring endpoints
        $skipRoutes = [
            'telescope*',
            'horizon*',
            '_debugbar*',
            'livewire*',
            'health*',
            'ping*',
            'status*',
        ];

        foreach ($skipRoutes as $pattern) {
            if (Str::is($pattern, $request->path())) {
                return true;
            }
        }

        // Skip static assets
        if (Str::contains($request->path(), ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico', '.woff', '.woff2'])) {
            return true;
        }

        // Skip GET requests to certain paths (to reduce noise)
        if ($request->isMethod('GET') && $this->isReadOnlyRoute($request)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the route is read-only and should be skipped for GET requests.
     */
    protected function isReadOnlyRoute(Request $request): bool
    {
        $readOnlyPatterns = [
            'api/*/index',
            'api/*/show',
            'filament/assets/*',
        ];

        foreach ($readOnlyPatterns as $pattern) {
            if (Str::is($pattern, $request->path())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log the request asynchronously to avoid performance impact.
     */
    protected function logRequestAsync(Request $request, Response $response, float $startTime): void
    {
        // Use dispatch to queue the audit logging
        dispatch(function () use ($request, $response, $startTime) {
            try {
                $executionTime = microtime(true) - $startTime;

                // Determine event type based on request
                $eventType = $this->determineEventType($request, $response);

                // Create metadata
                $metadata = [
                    'response_status' => $response->getStatusCode(),
                    'execution_time' => $executionTime,
                    'response_size' => strlen($response->getContent()),
                ];

                // Add route information if available
                if ($request->route()) {
                    $metadata['route_name'] = $request->route()->getName();
                    $metadata['route_action'] = $request->route()->getActionName();
                }

                // Log based on request type
                if (Str::startsWith($request->path(), 'api/')) {
                    $this->auditService->logApiRequest($request, $response);
                } else {
                    $this->auditService->logCustomEvent(
                        $eventType,
                        $this->generateDescription($request, $response),
                        $metadata
                    );
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
                \Log::error('Audit middleware failed to log request', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'error' => $e->getMessage(),
                ]);
            }
        })->onQueue('audit');
    }

    /**
     * Determine the event type based on the request.
     */
    protected function determineEventType(Request $request, Response $response): string
    {
        $method = $request->method();
        $statusCode = $response->getStatusCode();

        // Handle authentication routes
        if (Str::contains($request->path(), ['login', 'auth'])) {
            return $statusCode >= 200 && $statusCode < 300 ? 'login' : 'failed_login';
        }

        if (Str::contains($request->path(), 'logout')) {
            return 'logout';
        }

        // Handle CRUD operations
        return match ($method) {
            'POST' => 'http_create',
            'PUT', 'PATCH' => 'http_update',
            'DELETE' => 'http_delete',
            'GET' => 'http_read',
            default => 'http_request'
        };
    }

    /**
     * Generate a human-readable description for the request.
     */
    protected function generateDescription(Request $request, Response $response): string
    {
        $user = Auth::user();
        $userName = $user?->name ?? 'Guest';
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();

        $action = match ($method) {
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            'GET' => 'accessed',
            default => 'requested'
        };

        return "{$userName} {$action} {$path} (Status: {$statusCode})";
    }
}
