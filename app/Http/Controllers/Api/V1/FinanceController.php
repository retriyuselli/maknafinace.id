<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FinanceSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function __construct(
        private readonly FinanceSummaryService $finance,
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $period = $this->finance->resolvePeriod($data['from'] ?? null, $data['to'] ?? null);

        return response()->json([
            'data' => $this->finance->dashboard($period['from'], $period['to']),
        ]);
    }

    public function projects(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json(
            $this->finance->projects(
                $data['status'] ?? null,
                (int) ($data['per_page'] ?? 20),
            )
        );
    }

    public function prospects(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json(
            $this->finance->prospects(
                $data['status'] ?? null,
                (int) ($data['per_page'] ?? 20),
            )
        );
    }
}
