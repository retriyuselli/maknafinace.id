<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveApiUser
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return new JsonResponse(['message' => 'Unauthenticated'], 401);
        }

        if (in_array($user->status, ['terminated', 'inactive'], true) || $user->isExpired()) {
            $user->tokens()->delete();

            return new JsonResponse([
                'message' => 'Akun tidak aktif atau telah kedaluwarsa.',
            ], 403);
        }

        return $next($request);
    }
}
