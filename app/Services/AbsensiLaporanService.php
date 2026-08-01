<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\PengajuanLembur;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AbsensiLaporanService
{
    /**
     * @param  array{user_id?: int|null, bulan?: int|null, tahun?: int|null, dari?: string|null, sampai?: string|null, status?: string|null}  $filters
     */
    public function queryDetail(array $filters = []): Builder
    {
        $query = Absensi::query()->with('user')->orderBy('tanggal')->orderBy('user_id');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['bulan']) && ! empty($filters['tahun'])) {
            $query->whereMonth('tanggal', (int) $filters['bulan'])
                ->whereYear('tanggal', (int) $filters['tahun']);
        } else {
            if (! empty($filters['dari'])) {
                $query->whereDate('tanggal', '>=', $filters['dari']);
            }
            if (! empty($filters['sampai'])) {
                $query->whereDate('tanggal', '<=', $filters['sampai']);
            }
        }

        return $query;
    }

    /**
     * @return array{
     *     periode: string,
     *     user_id: int|null,
     *     user_name: string|null,
     *     hadir: int,
     *     terlambat: int,
     *     alfa: int,
     *     cuti: int,
     *     libur: int,
     *     total_hari: int,
     *     total_menit_kerja: int,
     *     total_menit_terlambat: int,
     *     total_menit_pulang_cepat: int,
     *     total_menit_lembur: int,
     *     usulan_pengurangan: int,
     *     usulan_bonus: int,
     *     catatan: string
     * }
     */
    public function ringkasanBulanan(int $userId, int $bulan, int $tahun): array
    {
        $rows = Absensi::query()
            ->where('user_id', $userId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get();

        $lemburMenit = (int) PengajuanLembur::query()
            ->where('user_id', $userId)
            ->where('status', PengajuanLembur::STATUS_DISETUJUI)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->sum('menit');

        $pengaturan = PengaturanAbsensi::aktifSekarang();
        $dendaPerMenit = (int) ($pengaturan?->denda_terlambat_per_menit ?? 0);
        $tarifLembur = (int) ($pengaturan?->tarif_lembur_per_menit ?? 0);

        $totalTerlambat = (int) $rows->sum('menit_terlambat');
        $usulanPengurangan = $dendaPerMenit > 0 ? $totalTerlambat * $dendaPerMenit : 0;
        $usulanBonus = $tarifLembur > 0 ? $lemburMenit * $tarifLembur : 0;

        $user = User::query()->find($userId);
        $periode = Carbon::create($tahun, $bulan, 1)->locale('id')->translatedFormat('F Y');

        $hadir = $rows->where('status', Absensi::STATUS_HADIR)->count();
        $terlambat = $rows->where('status', Absensi::STATUS_TERLAMBAT)->count();
        $alfa = $rows->where('status', Absensi::STATUS_ALFA)->count();
        $cuti = $rows->where('status', Absensi::STATUS_CUTI)->count();
        $libur = $rows->whereIn('status', [Absensi::STATUS_LIBUR, Absensi::STATUS_LIBUR_MINGGUAN])->count();

        $catatan = "Absensi {$periode}: hadir {$hadir}, terlambat {$terlambat} ({$totalTerlambat} mnt), alfa {$alfa}, cuti {$cuti}, libur {$libur}, lembur {$lemburMenit} mnt.";

        return [
            'periode' => $periode,
            'user_id' => $userId,
            'user_name' => $user?->name,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'alfa' => $alfa,
            'cuti' => $cuti,
            'libur' => $libur,
            'total_hari' => $rows->count(),
            'total_menit_kerja' => (int) $rows->sum('menit_kerja'),
            'total_menit_terlambat' => $totalTerlambat,
            'total_menit_pulang_cepat' => (int) $rows->sum('menit_pulang_cepat'),
            'total_menit_lembur' => $lemburMenit,
            'usulan_pengurangan' => $usulanPengurangan,
            'usulan_bonus' => $usulanBonus,
            'catatan' => $catatan,
        ];
    }

    /**
     * @param  array{user_id?: int|null, bulan?: int|null, tahun?: int|null, dari?: string|null, sampai?: string|null, status?: string|null}  $filters
     * @return Collection<int, Absensi>
     */
    public function koleksiDetail(array $filters = []): Collection
    {
        return $this->queryDetail($filters)->get();
    }

    public function labelStatus(?string $status): string
    {
        return match ($status) {
            Absensi::STATUS_HADIR => 'Hadir',
            Absensi::STATUS_TERLAMBAT => 'Terlambat',
            Absensi::STATUS_ALFA => 'Alfa',
            Absensi::STATUS_CUTI => 'Cuti',
            Absensi::STATUS_LIBUR => 'Libur',
            Absensi::STATUS_LIBUR_MINGGUAN => 'Libur Mingguan',
            Absensi::STATUS_SETENGAH_HARI => 'Setengah Hari',
            Absensi::STATUS_REMOTE => 'Remote',
            default => $status ?? '-',
        };
    }
}
