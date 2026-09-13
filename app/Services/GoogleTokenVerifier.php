<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GoogleTokenVerifier
{
    /**
     * @return array<string, mixed>
     */
    public function verify(string $idToken): array
    {
        $response = Http::timeout(12)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $response->ok()) {
            throw ValidationException::withMessages([
                'id_token' => ['Token Google tidak valid.'],
            ]);
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json() ?? [];

        $audience = (string) ($payload['aud'] ?? '');
        $allowedAudiences = array_values(array_unique(array_filter([
            ...(array) config('services.google.ios_client_ids', []),
            (string) config('services.google.ios_client_id'),
            (string) config('services.google.client_id'),
        ])));

        if ($allowedAudiences === [] || ! in_array($audience, $allowedAudiences, true)) {
            throw ValidationException::withMessages([
                'id_token' => ['Token Google tidak valid untuk aplikasi ini.'],
            ]);
        }

        if (trim((string) ($payload['email'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'id_token' => ['Email Google tidak tersedia.'],
            ]);
        }

        return $payload;
    }
}
