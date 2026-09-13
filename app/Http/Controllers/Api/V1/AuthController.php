<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\GoogleTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Issue a Sanctum personal access token for mobile / API clients.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak valid.'],
            ]);
        }

        $this->assertUserCanLogin($user);

        return $this->tokenResponse($user, $credentials['device_name'] ?? 'ios-app');
    }

    /**
     * Login dengan Google ID token. Tidak membuat akun baru.
     */
    public function google(Request $request, GoogleTokenVerifier $verifier): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $payload = $verifier->verify($data['id_token']);
        $googleId = (string) ($payload['sub'] ?? '');
        $email = trim((string) ($payload['email'] ?? ''));
        $emailVerified = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOL);

        if ($googleId === '' || $email === '') {
            throw ValidationException::withMessages([
                'id_token' => ['Akun Google tidak menyediakan email yang valid.'],
            ]);
        }

        /** @var User|null $user */
        $user = User::query()
            ->where(function ($query) use ($googleId, $email) {
                $query->where('google_id', $googleId)->orWhere('email', $email);
            })
            ->first();

        if ($user) {
            $this->assertUserCanLogin($user);

            $updates = [];
            if (! $user->google_id) {
                $updates['google_id'] = $googleId;
            }
            if ($emailVerified && ! $user->email_verified_at) {
                $updates['email_verified_at'] = now();
            }
            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }
        } else {
            throw ValidationException::withMessages([
                'id_token' => ['Akun Google belum terdaftar di Makna Finance. Hubungi administrator.'],
            ]);
        }

        return $this->tokenResponse($user, $data['device_name'] ?? 'ios-wofins-google');
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    private function assertUserCanLogin(User $user): void
    {
        if (in_array($user->status, ['terminated', 'inactive'], true)) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif. Hubungi administrator.'],
            ]);
        }

        if ($user->isExpired()) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda telah kedaluwarsa. Hubungi administrator.'],
            ]);
        }
    }

    private function tokenResponse(User $user, string $deviceName): JsonResponse
    {
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->loadMissing(['roles'])),
        ]);
    }
}
