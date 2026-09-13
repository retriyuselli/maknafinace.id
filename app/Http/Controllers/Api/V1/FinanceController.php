<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
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

    public function projectShow(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $detail = $this->finance->projectDetail($user, $id);

        if (! $detail) {
            return response()->json(['message' => 'Proyek tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $detail,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'type' => ['nullable', 'string', 'in:wedding_payment,other_income,wedding_expense,operational_expense,other_expense'],
            'direction' => ['nullable', 'string', 'in:in,out'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $period = $this->finance->resolvePeriod($data['from'] ?? null, $data['to'] ?? null);

        return response()->json(
            $this->finance->transactions(
                $period['from'],
                $period['to'],
                $data['type'] ?? null,
                (int) ($data['limit'] ?? 100),
                $data['direction'] ?? null,
            )
        );
    }

    public function reportSummary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'mode' => ['nullable', 'string', 'in:cash,profit_loss'],
        ]);

        $period = $this->finance->resolvePeriod($data['from'] ?? null, $data['to'] ?? null);

        return response()->json([
            'data' => $this->finance->reportSummary(
                $period['from'],
                $period['to'],
                $data['mode'] ?? 'cash',
            ),
        ]);
    }

    public function paymentProof(int $id): \Symfony\Component\HttpFoundation\Response
    {
        $file = $this->finance->paymentProofFile($id);

        if (! $file) {
            return response()->json(['message' => 'Payment proof tidak ditemukan.'], 404);
        }

        return response()->file($file['absolute'], [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'inline; filename="'.$file['name'].'"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $file['mtime'] ?? time()).' GMT',
        ]);
    }
}
