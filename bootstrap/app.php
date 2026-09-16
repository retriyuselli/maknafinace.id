<?php

use App\Http\Middleware\AbsensiPageSecurityHeaders;
use App\Http\Middleware\CheckProjectAccess;
use App\Http\Middleware\CheckUserExpiration;
use App\Http\Middleware\EnsureActiveApiUser;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\NoStoreResponse;
use App\Http\Middleware\VerifyCsrfToken;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Replace default CSRF middleware with patched version to fix Livewire Redirector compatibility.
        // @see app/Http/Middleware/VerifyCsrfToken.php
        $middleware->replace(
            PreventRequestForgery::class,
            VerifyCsrfToken::class,
        );

        $middleware->use([
            HandleCors::class,
        ]);
        // Add middleware aliases for better organization
        $middleware->alias([
            'filament.auth' => Authenticate::class,
            'check.expiration' => CheckUserExpiration::class,
            'project.access' => CheckProjectAccess::class,
            'no-store' => NoStoreResponse::class,
            'super-admin' => EnsureSuperAdmin::class,
            'absensi.headers' => AbsensiPageSecurityHeaders::class,
            'api.active' => EnsureActiveApiUser::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        // Ensure proper web middleware group for Niaga Hoster
        $middleware->web(append: [
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
        ]);

        // Apply CheckUserExpiration to web routes
        $middleware->web(CheckUserExpiration::class);

        // Handle method spoofing properly
        $middleware->web(prepend: [
            HandlePrecognitiveRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            return response()->redirectTo(config('app.url'));
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Method Not Allowed',
                    'message' => 'The requested method is not allowed for this route.',
                    'allowed_methods' => $e->getHeaders()['Allow'] ?? 'GET, POST',
                ], 405);
            }

            return response()->redirectToRoute('home')->with('error', 'Method tidak diizinkan untuk halaman ini.');
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Not Found',
                    'message' => 'The requested resource was not found.',
                ], 404);
            }

            return response()->redirectToRoute('home')->with('error', 'Halaman tidak ditemukan.');
        });
    })->create();
