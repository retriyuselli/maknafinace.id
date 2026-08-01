<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Symfony\Component\HttpFoundation\Response;

/**
 * Extended CSRF middleware to handle Livewire Redirector compatibility.
 *
 * Laravel 13.x's PreventRequestForgery middleware calls $response->headers->setCookie()
 * but Livewire\Features\SupportRedirects\Redirector extends Illuminate\Routing\Redirector
 * (not a Symfony Response), so it doesn't have a $headers property.
 *
 * @see https://github.com/laravel/framework/issues/PreventRequestForgery
 */
class VerifyCsrfToken extends PreventRequestForgery
{
    /**
     * Add the CSRF token to the response cookies.
     *
     * Overrides parent to guard against non-Response objects (e.g. Livewire Redirector)
     * that do not have a $headers property.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $response
     * @return mixed
     */
    protected function addCookieToResponse($request, $response)
    {
        $config = config('session');

        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        // Guard: only set cookie on proper Symfony Response objects.
        // Livewire\Features\SupportRedirects\Redirector extends Illuminate\Routing\Redirector
        // which is NOT a Symfony Response and does not have a $headers property.
        if ($response instanceof Response) {
            $response->headers->setCookie($this->newCookie($request, $config));
        }

        return $response;
    }
}
