<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\LeaveRequest;
use App\Models\PengaturanAbsensi;
use App\Models\PenugasanJadwal;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AbsensiRekapService
{
    /**
     * Status yang sudah final dari clock-in dan tidak boleh ditimpa rekap.
     *
     * @var list<string>
     */
    protected array $statusFinalKehadiran = [
        Absensi::STATUS_HADIR,
        Absensi::STATUS_TERLAMBAT,
        Absensi::STATUS_SETENGAH_HARI,
        Absensi::STATUS_REMOTE,
    ];

    public function pengaturanAktif(): ?PengaturanAbsensi
    {
        return PengaturanAbsensi::aktifSekarang();
    }

    public function timezone(?PengaturanAbsensi $pengaturan = null): string
    {
        return ($pengaturan ?? $this->pengaturanAktif())?->zona_waktu
            ?? config('app.timezone', 'Asia/Jakarta');
    }

    /**
     * Sinkronkan status cuti untuk rentang LeaveRequest yang disetujui.
     */
    public function sinkronkanCuti(LeaveRequest $leaveRequest): int
    {
        if ($leaveRequest->status !== 'approved') {
            return 0;
        }

        if (! $leaveRequest->start_date || ! $leaveRequest->end_date) {
            return 0;
        }

        $updated = 0;
        $cursor = $leaveRequest->start_date->copy()->startOfDay();
        $end = $leaveRequest->end_date->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $absensi = $this->tandaiStatusUntukUser(
                (int) $leaveRequest->user_id,
                $cursor->toDateString(),
                Absensi::STATUS_CUTI,
                [
                    'leave_request_id' => $leaveRequest->id,
                    'catatan' => 'Otomatis dari cuti disetujui #'.$leaveRequest->id,
                    'sumber' => 'sistem',
                ],
                overwriteKehadiran: false,
            );

            if ($absensi) {
                $updated++;
            }

            $cursor->addDay();
        }

        return $updated;
    }

    /**
     * Rekap satu tanggal untuk semua user aktif.
     *
     * @return array{tanggal: string, diproses: int, dibuat: int, diubah: int, dilewati: int}
     */
    public function rekapTanggal(CarbonInterface|string $tanggal): array
    {
        $pengaturan = $this->pengaturanAktif();
        $tz = $this->timezone($pengaturan);
        $date = Carbon::parse($tanggal, $tz)->toDateString();

        $dibuat = 0;
        $diubah = 0;
        $dilewati = 0;

        $users = $this->userAktif();

        foreach ($users as $user) {
            $hasil = $this->rekapUserTanggal($user, $date, $pengaturan);

            match ($hasil) {
                'created' => $dibuat++,
                'updated' => $diubah++,
                default => $dilewati++,
            };
        }

        return [
            'tanggal' => $date,
            'diproses' => $users->count(),
            'dibuat' => $dibuat,
            'diubah' => $diubah,
            'dilewati' => $dilewati,
        ];
    }

    /**
     * @return 'created'|'updated'|'skipped'
     */
    public function rekapUserTanggal(User $user, string $tanggal, ?PengaturanAbsensi $pengaturan = null): string
    {
        $pengaturan ??= $this->pengaturanAktif();
        $existing = Absensi::query()
            ->where('user_id', $user->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if ($existing && ($existing->jam_masuk || in_array($existing->status, $this->statusFinalKehadiran, true))) {
            return 'skipped';
        }

        $target = $this->tentukanStatusTanpaKehadiran($user, $tanggal, $pengaturan);
        $payload = [
            'sumber' => 'sistem',
            'catatan' => $target['catatan'],
            'leave_request_id' => $target['leave_request_id'],
        ];

        if (! $existing) {
            Absensi::query()->create([
                'user_id' => $user->id,
                'tanggal' => $tanggal,
                'status' => $target['status'],
                ...$payload,
            ]);

            return 'created';
        }

        if ($existing->status === $target['status']
            && (int) ($existing->leave_request_id ?? 0) === (int) ($target['leave_request_id'] ?? 0)
        ) {
            return 'skipped';
        }

        $existing->fill([
            'status' => $target['status'],
            ...$payload,
        ])->save();

        return 'updated';
    }

    /**
     * @return array{status: string, catatan: string|null, leave_request_id: int|null}
     */
    public function tentukanStatusTanpaKehadiran(
        User $user,
        string $tanggal,
        ?PengaturanAbsensi $pengaturan = null
    ): array {
        $pengaturan ??= $this->pengaturanAktif();

        $cuti = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $tanggal)
            ->whereDate('end_date', '>=', $tanggal)
            ->latest('id')
            ->first();

        if ($cuti) {
            return [
                'status' => Absensi::STATUS_CUTI,
                'catatan' => 'Otomatis dari cuti disetujui #'.$cuti->id,
                'leave_request_id' => $cuti->id,
            ];
        }

        $libur = HariLibur::untukTanggal($tanggal);
        if ($libur && $libur->adalahLiburEfektif()) {
            return [
                'status' => Absensi::STATUS_LIBUR,
                'catatan' => 'Libur: '.$libur->nama,
                'leave_request_id' => null,
            ];
        }

        if ($this->adalahLiburMingguan($tanggal, $pengaturan, $user)) {
            $label = Carbon::parse($tanggal)->locale('id')->translatedFormat('l');

            return [
                'status' => Absensi::STATUS_LIBUR_MINGGUAN,
                'catatan' => 'Libur menurut jadwal kerja ('.$label.')',
                'leave_request_id' => null,
            ];
        }

        return [
            'status' => Absensi::STATUS_ALFA,
            'catatan' => 'Tidak ada absen masuk (rekap otomatis)',
            'leave_request_id' => null,
        ];
    }

    public function adalahLiburMingguan(
        string $tanggal,
        ?PengaturanAbsensi $pengaturan = null,
        ?User $user = null,
    ): bool {
        $pengaturan ??= $this->pengaturanAktif();
        $dayOfWeek = Carbon::parse($tanggal)->dayOfWeek; // 0=Minggu, 6=Sabtu

        if ($user) {
            $penugasan = PenugasanJadwal::untukUserPada($user->id, $tanggal);
            $jadwal = $penugasan?->jadwalKerja ?? \App\Models\JadwalKerja::defaultAktif();
            $hari = $jadwal?->hariUntuk($dayOfWeek);

            if ($hari) {
                return ! $hari->hari_kerja;
            }
        }

        if ($dayOfWeek === Carbon::SATURDAY) {
            return (bool) ($pengaturan?->libur_sabtu ?? true);
        }

        if ($dayOfWeek === Carbon::SUNDAY) {
            return (bool) ($pengaturan?->libur_minggu ?? true);
        }

        return false;
    }

    public function adalahHariLiburEfektif(string $tanggal): bool
    {
        $libur = HariLibur::untukTanggal($tanggal);

        return $libur?->adalahLiburEfektif() ?? false;
    }

    /**
     * @param  array{leave_request_id?: int|null, catatan?: string|null, sumber?: string|null}  $extra
     */
    public function tandaiStatusUntukUser(
        int $userId,
        string $tanggal,
        string $status,
        array $extra = [],
        bool $overwriteKehadiran = false,
    ): ?Absensi {
        $absensi = Absensi::query()->firstOrNew([
            'user_id' => $userId,
            'tanggal' => $tanggal,
        ]);

        if (
            ! $overwriteKehadiran
            && $absensi->exists
            && ($absensi->jam_masuk || in_array($absensi->status, $this->statusFinalKehadiran, true))
        ) {
            return null;
        }

        $absensi->status = $status;
        $absensi->sumber = $extra['sumber'] ?? $absensi->sumber ?? 'sistem';
        $absensi->catatan = $extra['catatan'] ?? $absensi->catatan;
        $absensi->leave_request_id = $extra['leave_request_id'] ?? $absensi->leave_request_id;
        $absensi->save();

        return $absensi;
    }

    /**
     * @return Collection<int, User>
     */
    protected function userAktif(): Collection
    {
        return User::query()
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn('status', ['terminated', 'inactive']);
            })
            ->orderBy('id')
            ->get();
    }
}
