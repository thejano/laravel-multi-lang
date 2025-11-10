<?php

namespace TheJano\MultiLang\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->getLocaleFromRequest($request);

        if ($locale) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Get locale from request.
     */
    protected function getLocaleFromRequest(Request $request): ?string
    {
        // Try to get locale from route parameter
        if ($request->route('locale')) {
            return $request->route('locale');
        }

        // Try to get locale from query parameter
        if ($request->has('locale')) {
            return $request->get('locale');
        }

        // Try to get locale from session
        if ($request->hasSession() && $request->session()->has('locale')) {
            return $request->session()->get('locale');
        }

        // Try to get locale from header
        if ($request->hasHeader('Accept-Language')) {
            $acceptLanguage = $request->header('Accept-Language');
            $locales = $this->parseAcceptLanguage($acceptLanguage);

            if (! empty($locales)) {
                return $locales[0];
            }
        }

        return null;
    }

    /**
     * Parse Accept-Language header.
     */
    protected function parseAcceptLanguage(string $acceptLanguage): array
    {
        $locales = [];
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);
            if (preg_match('/^([a-z]{2}(?:-[A-Z]{2})?)/i', $part, $matches)) {
                $locales[] = $matches[1];
            }
        }

        return $locales;
    }
}
