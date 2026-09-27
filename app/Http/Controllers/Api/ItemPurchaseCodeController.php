<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ItemPurchaseCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemPurchaseCodeController extends Controller
{
    public function verify(Request $request, ItemPurchaseCodeService $service): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'uuid'],
            'domain' => ['nullable', 'string', 'max:255'],
            'bind' => ['sometimes', 'boolean'],
        ]);

        $bind = (bool) ($data['bind'] ?? true);
        $domain = array_key_exists('domain', $data) ? $data['domain'] : null;
        // Saat bind=false (mis. app iOS membuka host), jangan fallback ke Origin/getHost().
        if ($bind && ($domain === null || $domain === '')) {
            $domain = $request->headers->get('Origin') ?? $request->getHost();
        }

        $payload = $service->verify(
            $data['code'],
            $domain,
            $bind,
        );

        return response()->json($payload, $payload['valid'] ? 200 : 422);
    }
}
