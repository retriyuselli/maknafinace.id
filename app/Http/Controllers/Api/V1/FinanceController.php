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
}
