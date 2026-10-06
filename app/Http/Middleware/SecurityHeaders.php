<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com",
            "img-src 'self' data: https://unpkg.com https://tile.openstreetmap.org",
            "font-src 'self' data:",
            "connect-src 'self'",
            'upgrade-insecure-requests',
        ]));
        // OSM tiles require a Referer. Only the map page sends our origin
        // across origins; account details and query parameters stay private.
        $response->headers->set('Referrer-Policy', $request->routeIs('admin.map')
            ? 'strict-origin-when-cross-origin'
            : 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), payment=(), usb=(), geolocation=(self)');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // HSTS must only be emitted over HTTPS. Once a browser receives it,
        // it will refuse clear-text HTTP for this host for the next year.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Authenticated responses can contain survey answers, contact data,
        // reward numbers, or precise locations. Do not let browsers or
        // intermediary caches retain copies of those pages or JSON payloads.
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }
}
