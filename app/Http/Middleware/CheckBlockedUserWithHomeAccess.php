<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedUserWithHomeAccess
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
                // Set the locale based on user preference before checking access
                if ($user->locale && in_array($user->locale, ['en', 'ar'])) {
                    \App::setLocale($user->locale);
                }

                $routeUri = $request->getPathInfo();
                $currentRoute = $request->route();
                $routeName = $currentRoute ? $currentRoute->getName() : null;

                // Check if current route is the homeNew endpoint
                $isHomeNewEndpoint = (
                    $routeName === 'homeNew' ||
                    str_ends_with($routeUri, '/homeNew') ||
                    str_contains($routeUri, '/api/v1/homeNew')
                );

                if (! $isHomeNewEndpoint) {
                    // Log the blocked user access attempt to non-home endpoint
                    \Log::warning('Blocked user attempted to access restricted endpoint', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'url' => $request->url(),
                        'route_name' => $routeName,
                        'route_uri' => $routeUri,
                        'ip' => $request->ip(),
                        'user_locale' => $user->locale,
                    ]);

                    return response()->json([
                        'value' => false,
                        'message' => __('api.account_blocked'),
                        'allowed_endpoint' => '/api/v1/homeNew',
                    ], 403);
                }

                // Log successful access to allowed endpoint for blocked user
                \Log::info('Blocked user accessed allowed home endpoint', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'url' => $request->url(),
                    'route_name' => $routeName,
                    'route_uri' => $routeUri,
                    'ip' => $request->ip(),
                    'user_locale' => $user->locale,
                ]);
            }
        }

        return $next($request);
    }
}
