<?php

namespace App\Http\Controllers;

use App\Exports\AbsensiExport;
use App\Models\Absensi;
use App\Services\AbsensiLaporanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AbsensiLaporanController extends Controller
{
    public function __construct(private AbsensiLaporanService $laporanService) {}

    public function excel(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Absensi::class);

        $filters = $this->filters($request);
        $records = $this->laporanService->koleksiDetail($filters);

        $filename = 'laporan-absensi-'.now()->format('YmdHis').'.xlsx';

        return Excel::download(new AbsensiExport($records), $filename);
    }

    public function pdf(Request $request): Response
    {
        Gate::authorize('viewAny', Absensi::class);

        $filters = $this->filters($request);
        $records = $this->laporanService->koleksiDetail($filters);

        $ringkasan = null;
        if (! empty($filters['user_id']) && ! empty($filters['bulan']) && ! empty($filters['tahun'])) {
            $ringkasan = $this->laporanService->ringkasanBulanan(
                (int) $filters['user_id'],
                (int) $filters['bulan'],
                (int) $filters['tahun'],
            );
        }

        $pdf = Pdf::loadView('pdf.absensi_report', [
            'records' => $records,
            'filters' => $filters,
            'ringkasan' => $ringkasan,
            'labelStatus' => fn (?string $status) => $this->laporanService->labelStatus($status),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-absensi-'.now()->format('YmdHis').'.pdf');
    }

    /**
     * @return array{user_id?: int|null, bulan?: int|null, tahun?: int|null, dari?: string|null, sampai?: string|null, status?: string|null}
     */
    protected function filters(Request $request): array
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        return $data;
    }
}
