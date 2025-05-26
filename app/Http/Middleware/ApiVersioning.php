<?php

namespace App\Http\Middleware;

use App\Services\SecurityLogService;
use Closure;
use Illuminate\Http\Request;

class ApiVersioning
{
    protected $securityLog;

    protected $supportedVersions = ['v1', 'v2'];

    protected $deprecatedVersions = [];

    protected $sunsetVersions = [];

    public function __construct(SecurityLogService $securityLog)
    {
        $this->securityLog = $securityLog;
    }

    public function handle(Request $request, Closure $next)
    {
        $version = $this->getVersionFromRequest($request);

        if (! $version) {
            return response()->json([
                'value' => false,
                'message' => 'API version is required. Please specify a version (e.g., /api/v1/)',
                'supported_versions' => $this->supportedVersions,
            ], 400);
        }

        if (! in_array($version, $this->supportedVersions)) {
            return response()->json([
                'value' => false,
                'message' => 'Unsupported API version',
                'supported_versions' => $this->supportedVersions,
            ], 400);
        }

        if (in_array($version, $this->sunsetVersions)) {
            return response()->json([
                'value' => false,
                'message' => 'This API version has been sunset',
                'supported_versions' => $this->supportedVersions,
            ], 410);
        }

        $response = $next($request);

        if (in_array($version, $this->deprecatedVersions)) {
            $response->headers->set('Deprecation', 'true');
            $response->headers->set('Sunset', date('r', strtotime('+30 days')));
            $response->headers->set('Link', '</v2>; rel="successor-version"');
        }

        $this->logVersionUsage($request, $version);

        return $response;
    }

    protected function getVersionFromRequest(Request $request)
    {
        $path = $request->path();
        $segments = explode('/', $path);
        
        // Look for version in the format /api/v1/...
        if (count($segments) >= 2 && $segments[0] === 'api') {
            return $segments[1] ?? null;
        }
        
        return null;
    }

    protected function logVersionUsage(Request $request, string $version)
    {
        $this->securityLog->logRateLimitEvent('API Version Usage', [
            'version' => $version,
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
