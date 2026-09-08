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

        $payload = $service->verify(
            $data['code'],
            $data['domain'] ?? $request->headers->get('Origin') ?? $request->getHost(),
            (bool) ($data['bind'] ?? true),
        );

        return response()->json($payload, $payload['valid'] ? 200 : 422);
    }
}
