<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->determineLocale($request);

        // Set the application locale
        App::setLocale($locale);

        // Log the locale detection for debugging
        Log::info('Locale set for request', [
            'detected_locale' => $locale,
            'accept_language' => $request->header('Accept-Language'),
            'url_lang' => $request->get('lang'),
            'user_id' => Auth::id(),
            'endpoint' => $request->getPathInfo(),
        ]);

        return $next($request);
    }

    /**
     * Determine the locale based on multiple sources with priority
     */
    private function determineLocale(Request $request): string
    {
        $supportedLocales = ['en', 'ar'];
        $defaultLocale = 'en';

        // Priority 1: URL parameter (?lang=ar)
        $urlLang = $request->get('lang');
        if ($urlLang && in_array($urlLang, $supportedLocales)) {
            return $urlLang;
        }

        // Priority 2: User's saved preference (if authenticated)
        $user = null;

        // Try web guard first
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
        }
        // Try sanctum guard if web guard fails
        elseif (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
        }

        if ($user && $user->locale) {
            $userLocale = $user->locale;
            if (in_array($userLocale, $supportedLocales)) {
                return $userLocale;
            }
        }

        // Priority 3: Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguageHeader($acceptLanguage, $supportedLocales);
            if ($locale) {
                return $locale;
            }
        }

        // Priority 4: Default locale
        return $defaultLocale;
    }

    /**
     * Parse Accept-Language header and find the best match
     */
    private function parseAcceptLanguageHeader(string $acceptLanguage, array $supportedLocales): ?string
    {
        // Parse "en-US,en;q=0.9,ar;q=0.8" format
        $languages = [];

        // Split by comma and parse each language with its quality value
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';q=', trim($lang));
            $language = trim($parts[0]);
            $quality = isset($parts[1]) ? (float) $parts[1] : 1.0;

            // Extract main language code (en-US -> en)
            $mainLang = strtolower(substr($language, 0, 2));

            $languages[] = [
                'lang' => $mainLang,
                'quality' => $quality,
            ];
        }

        // Sort by quality (highest first)
        usort($languages, function ($a, $b) {
            return $b['quality'] <=> $a['quality'];
        });

        // Find the first supported language
        foreach ($languages as $lang) {
            if (in_array($lang['lang'], $supportedLocales)) {
                return $lang['lang'];
            }
        }

        return null;
    }
}
