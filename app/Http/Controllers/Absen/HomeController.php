<?php

namespace App\Http\Controllers\Absen;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\PengaturanAbsensi;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function home(Request $request): View
    {
        $context = $this->absensiContext($request);

        $hour = Carbon::now($context['timezone'])->hour;
        $greeting = match (true) {
            $hour < 11 => 'Good Morning',
            $hour < 15 => 'Good Afternoon',
            $hour < 18 => 'Good Evening',
            default => 'Good Evening',
        };

        return view('absen.home', [
            'user' => $request->user(),
            'greeting' => $greeting,
            ...$context,
        ]);
    }

    public function more(Request $request): View
    {
        return view('absen.more', [
            'user' => $request->user(),
        ]);
    }

    public function clock(Request $request, string $aksi): View|RedirectResponse
    {
        abort_unless(in_array($aksi, ['masuk', 'pulang'], true), 404);

        $context = $this->absensiContext($request);

        if ($aksi === 'masuk' && ! $context['canMasuk']) {
            return redirect()
                ->route('absen.home')
                ->with('error', $context['canPulang']
                    ? 'Absen masuk hari ini sudah tercatat. Silakan Clock Out.'
                    : 'Clock In belum tersedia.');
        }

        if ($aksi === 'pulang' && ! $context['canPulang']) {
            return redirect()
                ->route('absen.home')
                ->with('error', $context['canMasuk']
                    ? 'Clock Out baru bisa dilakukan setelah Clock In.'
                    : 'Clock Out belum tersedia.');
        }

        return view('absen.clock', [
            'user' => $request->user(),
            'aksi' => $aksi,
            ...$context,
        ]);
    }

    /**
     * @return array{
     *     pengaturan: ?PengaturanAbsensi,
     *     absensiHariIni: ?Absensi,
     *     timezone: string,
     *     canMasuk: bool,
     *     canPulang: bool,
     *     wajibFoto: bool,
     *     wajibLokasi: bool,
     *     maxFotoKb: int,
     *     jamMasuk: string,
     *     jamPulang: string
     * }
     */
    private function absensiContext(Request $request): array
    {
        $pengaturan = PengaturanAbsensi::aktifSekarang();
        $timezone = $pengaturan?->zona_waktu ?? config('app.timezone', 'Asia/Jakarta');
        $today = Carbon::now($timezone)->toDateString();

        $absensiHariIni = Absensi::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('tanggal', $today)
            ->first();

        return [
            'pengaturan' => $pengaturan,
            'absensiHariIni' => $absensiHariIni,
            'timezone' => $timezone,
            'canMasuk' => (bool) $pengaturan && ! $absensiHariIni?->jam_masuk,
            'canPulang' => (bool) $pengaturan && (bool) $absensiHariIni?->jam_masuk && ! $absensiHariIni?->jam_pulang,
            'wajibFoto' => (bool) ($pengaturan?->wajib_foto ?? false),
            'wajibLokasi' => (bool) ($pengaturan?->wajib_lokasi ?? false),
            'maxFotoKb' => max(1, (int) ($pengaturan?->ukuran_foto_maks_kb ?: 5120)),
            'jamMasuk' => $absensiHariIni?->jam_masuk?->timezone($timezone)?->format('H:i') ?? '-',
            'jamPulang' => $absensiHariIni?->jam_pulang?->timezone($timezone)?->format('H:i') ?? '-',
        ];
    }
}
