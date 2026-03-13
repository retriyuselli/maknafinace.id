<?php

namespace App\Http\Controllers;

use App\Models\JournalBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JournalPdfController extends Controller
{
    public function preview(Request $request)
    {
        Gate::authorize('viewAny', JournalBatch::class);

        $data = $this->validatedFilters($request);
        $batches = $this->queryBatches($data)->get();

        $pdf = Pdf::loadView('reports.journal_batches_pdf', [
            'batches' => $batches,
            'filters' => $data,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('jurnal_umum_'.now()->format('Y-m-d_H-i-s').'.pdf');
    }

    public function download(Request $request)
    {
        Gate::authorize('viewAny', JournalBatch::class);

        $data = $this->validatedFilters($request);
        $batches = $this->queryBatches($data)->get();

        $pdf = Pdf::loadView('reports.journal_batches_pdf', [
            'batches' => $batches,
            'filters' => $data,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('jurnal_umum_'.now()->format('Y-m-d_H-i-s').'.pdf');
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,posted,reversed'],
            'reference_type' => ['nullable', 'string', 'max:255'],
        ]);

        $start = $validated['start_date'] ?? null;
        $end = $validated['end_date'] ?? null;

        if ($start) {
            $validated['start_date'] = Carbon::parse($start)->toDateString();
        }

        if ($end) {
            $validated['end_date'] = Carbon::parse($end)->toDateString();
        }

        return $validated;
    }

    private function queryBatches(array $filters)
    {
        return JournalBatch::query()
            ->with(['journalEntries.account'])
            ->when(
                $filters['start_date'] ?? null,
                fn ($q, $date) => $q->whereDate('transaction_date', '>=', $date),
            )
            ->when(
                $filters['end_date'] ?? null,
                fn ($q, $date) => $q->whereDate('transaction_date', '<=', $date),
            )
            ->when(
                $filters['status'] ?? null,
                fn ($q, $status) => $q->where('status', $status),
            )
            ->when(
                $filters['reference_type'] ?? null,
                fn ($q, $type) => $q->where('reference_type', $type),
            )
            ->orderBy('transaction_date')
            ->orderBy('id');
    }
}

