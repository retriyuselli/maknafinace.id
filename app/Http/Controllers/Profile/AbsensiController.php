<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Exports\AbsensiExport;
use App\Models\Absensi;
use App\Models\KoreksiAbsensi;
use App\Models\LokasiAbsensi;
use App\Models\PengajuanLembur;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use App\Services\AbsensiLaporanService;
use App\Services\AbsensiService;
use App\Services\KoreksiAbsensiService;
use App\Services\PengajuanLemburService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AbsensiController extends Controller
{
    public function __construct(
        private AbsensiService $absensiService,
        private KoreksiAbsensiService $koreksiAbsensiService,
        private PengajuanLemburService $pengajuanLemburService,
        private AbsensiLaporanService $laporanService,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $pengaturan = PengaturanAbsensi::aktifSekarang();
        $timezone = $pengaturan?->zona_waktu ?? config('app.timezone', 'Asia/Jakarta');
        $today = Carbon::now($timezone)->toDateString();

        $absensiHariIni = Absensi::query()
            ->with(['logAbsensis' => fn ($query) => $query->with('lokasiAbsensi')->orderByDesc('waktu')])
            ->where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        $lokasiAktif = LokasiAbsensi::query()->aktif()->get();

        $riwayatAbsensi = Absensi::query()
            ->with(['logAbsensis' => fn ($query) => $query->with('lokasiAbsensi')->orderByDesc('waktu')])
            ->where('user_id', $user->id)
            ->orderByDesc('tanggal')
            ->limit(10)
            ->get();

        $riwayatKoreksi = KoreksiAbsensi::query()
            ->with(['absensi', 'ditinjauOleh'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $riwayatLembur = PengajuanLembur::query()
            ->with(['absensi', 'disetujuiOleh'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $bulanIni = Carbon::now($timezone)->startOfMonth();
        $ringkasan = $this->laporanService->ringkasanBulanan($user->id, $bulanIni->month, $bulanIni->year);

        return view('profile.absensi', [
            'user' => $user,
            'pengaturan' => $pengaturan,
            'absensiHariIni' => $absensiHariIni,
            'lokasiAktif' => $lokasiAktif,
            'riwayatAbsensi' => $riwayatAbsensi,
            'riwayatKoreksi' => $riwayatKoreksi,
            'riwayatLembur' => $riwayatLembur,
            'timezone' => $timezone,
            'today' => $today,
            'ringkasan' => $ringkasan,
            'laporanBulan' => $bulanIni->month,
            'laporanTahun' => $bulanIni->year,
        ]);
    }

    public function laporanExcel(Request $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = $request->user();
        $filters = $this->ownFilters($request, $user);
        $records = $this->laporanService->koleksiDetail($filters);

        return Excel::download(
            new AbsensiExport($records),
            'absensi-saya-'.now()->format('YmdHis').'.xlsx'
        );
    }

    public function laporanPdf(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $filters = $this->ownFilters($request, $user);
        $records = $this->laporanService->koleksiDetail($filters);

        $ringkasan = $this->laporanService->ringkasanBulanan(
            $user->id,
            (int) $filters['bulan'],
            (int) $filters['tahun'],
        );

        $pdf = Pdf::loadView('pdf.absensi_report', [
            'records' => $records,
            'filters' => $filters,
            'ringkasan' => $ringkasan,
            'labelStatus' => fn (?string $status) => $this->laporanService->labelStatus($status),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('absensi-saya-'.now()->format('YmdHis').'.pdf');
    }

    /**
     * @return array{user_id: int, bulan: int, tahun: int, status?: string|null}
     */
    private function ownFilters(Request $request, User $user): array
    {
        $data = $request->validate([
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $pengaturan = PengaturanAbsensi::aktifSekarang();
        $timezone = $pengaturan?->zona_waktu ?? config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($timezone);

        return [
            'user_id' => $user->id,
            'bulan' => (int) ($data['bulan'] ?? $now->month),
            'tahun' => (int) ($data['tahun'] ?? $now->year),
            'status' => $data['status'] ?? null,
        ];
    }

    public function koreksi(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'absensi_id' => ['required', 'integer', 'exists:absensis,id'],
            'jam_masuk_diajukan' => ['nullable', 'date'],
            'jam_pulang_diajukan' => ['nullable', 'date'],
            'alasan' => ['required', 'string', 'max:2000'],
        ]);

        $absensi = Absensi::query()
            ->whereKey($data['absensi_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->koreksiAbsensiService->ajukan($user, $absensi, $data);

        return back()->with('success', 'Pengajuan koreksi absensi berhasil dikirim.');
    }

    public function lembur(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'absensi_id' => ['nullable', 'integer', 'exists:absensis,id'],
            'tanggal' => ['nullable', 'date'],
            'mulai_pada' => ['required', 'date'],
            'selesai_pada' => ['required', 'date', 'after:mulai_pada'],
            'alasan' => ['required', 'string', 'max:2000'],
        ]);

        $this->pengajuanLemburService->ajukan($user, $data);

        return back()->with('success', 'Pengajuan lembur berhasil dikirim.');
    }

    public function masuk(Request $request): RedirectResponse
    {
        return $this->simpanAbsensi($request, 'masuk');
    }

    public function pulang(Request $request): RedirectResponse
    {
        return $this->simpanAbsensi($request, 'pulang');
    }

    private function simpanAbsensi(Request $request, string $jenis): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $pengaturan = PengaturanAbsensi::aktifSekarang();
        $maxFotoKb = max(1, (int) ($pengaturan?->ukuran_foto_maks_kb ?: 5120));

        $data = $request->validate([
            'lintang' => ['nullable', 'numeric', 'between:-90,90'],
            'bujur' => ['nullable', 'numeric', 'between:-180,180'],
            'akurasi_meter' => ['nullable', 'numeric', 'min:0'],
            'nama_perangkat' => ['nullable', 'string', 'max:100'],
            'foto' => ['nullable', File::image()->types(['jpeg', 'jpg', 'png', 'webp'])->max($maxFotoKb)],
        ]);

        $payload = [
            'lintang' => isset($data['lintang']) ? (float) $data['lintang'] : null,
            'bujur' => isset($data['bujur']) ? (float) $data['bujur'] : null,
            'akurasi_meter' => isset($data['akurasi_meter']) ? (float) $data['akurasi_meter'] : null,
            'nama_perangkat' => $data['nama_perangkat'] ?? 'browser-web',
            'alamat_ip' => $request->ip(),
            'sumber' => 'web',
            'meta' => [
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ],
        ];

        $foto = $request->file('foto');

        if ($jenis === 'masuk') {
            $this->absensiService->absenMasuk($user, $payload, $foto);
        } else {
            $this->absensiService->absenPulang($user, $payload, $foto);
        }

        return redirect()
            ->route('profile.absensi')
            ->with('success', $jenis === 'masuk' ? 'Absen masuk berhasil dicatat.' : 'Absen pulang berhasil dicatat.');
    }
}
