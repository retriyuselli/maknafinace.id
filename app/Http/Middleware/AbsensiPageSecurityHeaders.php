<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AbsensiPageSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Jangan set Content-Security-Policy di sini:
        // halaman absensi memakai layout profile (Vite/Tailwind, Google Fonts,
        // Font Awesome). CSP ketat membuat CSS/JS tidak ter-load.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(self), geolocation=(self), microphone=()'
        );
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        return $response;
    }
}
