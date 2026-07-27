<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\HariJadwalKerja;
use App\Models\HariLibur;
use App\Models\LeaveRequest;
use App\Models\LogAbsensi;
use App\Models\LokasiAbsensi;
use App\Models\PengaturanAbsensi;
use App\Models\PenugasanJadwal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbsensiService
{
    public function __construct(private AbsensiFotoCompressor $fotoCompressor) {}

    public function pengaturanAktif(): PengaturanAbsensi
    {
        $pengaturan = PengaturanAbsensi::aktifSekarang();

        if (! $pengaturan) {
            throw ValidationException::withMessages([
                'pengaturan' => ['Pengaturan absensi belum dikonfigurasi.'],
            ]);
        }

        return $pengaturan;
    }

    /**
     * @return array{dalam_radius: bool, jarak_meter: int|null, lokasi: LokasiAbsensi|null}
     */
    public function cekLokasi(float $lintang, float $bujur): array
    {
        $lokasis = LokasiAbsensi::query()->aktif()->get();

        if ($lokasis->isEmpty()) {
            return [
                'dalam_radius' => false,
                'jarak_meter' => null,
                'lokasi' => null,
            ];
        }

        $terdekat = null;
        $jarakTerdekat = null;

        foreach ($lokasis as $lokasi) {
            $jarak = $this->jarakMeter($lintang, $bujur, (float) $lokasi->lintang, (float) $lokasi->bujur);

            if ($jarakTerdekat === null || $jarak < $jarakTerdekat) {
                $jarakTerdekat = $jarak;
                $terdekat = $lokasi;
            }

            if ($jarak <= $lokasi->radius_meter) {
                return [
                    'dalam_radius' => true,
                    'jarak_meter' => (int) round($jarak),
                    'lokasi' => $lokasi,
                ];
            }
        }

        return [
            'dalam_radius' => false,
            'jarak_meter' => $jarakTerdekat !== null ? (int) round($jarakTerdekat) : null,
            'lokasi' => $terdekat,
        ];
    }

    public function jarakMeter(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    /**
     * @param  array{
     *     lintang?: float|null,
     *     bujur?: float|null,
     *     akurasi_meter?: float|null,
     *     nama_perangkat?: string|null,
     *     alamat_ip?: string|null,
     *     meta?: array|null,
     *     sumber?: string|null,
     * }  $payload
     */
    public function absenMasuk(User $user, array $payload, ?UploadedFile $foto = null): Absensi
    {
        return $this->catat($user, LogAbsensi::JENIS_MASUK, $payload, $foto);
    }

    /**
     * @param  array{
     *     lintang?: float|null,
     *     bujur?: float|null,
     *     akurasi_meter?: float|null,
     *     nama_perangkat?: string|null,
     *     alamat_ip?: string|null,
     *     meta?: array|null,
     *     sumber?: string|null,
     * }  $payload
     */
    public function absenPulang(User $user, array $payload, ?UploadedFile $foto = null): Absensi
    {
        return $this->catat($user, LogAbsensi::JENIS_PULANG, $payload, $foto);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function catat(User $user, string $jenis, array $payload, ?UploadedFile $foto = null): Absensi
    {
        $pengaturan = $this->pengaturanAktif();
        $now = Carbon::now($pengaturan->zona_waktu);
        $tanggal = $now->toDateString();

        $this->assertBukanCuti($user, $tanggal);
        $this->assertBukanLibur($tanggal);
        $this->assertFoto($pengaturan, $foto);
        $lokasiInfo = $this->assertLokasi($pengaturan, $payload);

        return DB::transaction(function () use ($user, $jenis, $payload, $foto, $pengaturan, $now, $tanggal, $lokasiInfo) {
            $absensi = Absensi::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'tanggal' => $tanggal,
                ],
                [
                    'status' => Absensi::STATUS_ALFA,
                    'sumber' => $payload['sumber'] ?? 'mobile',
                ]
            );

            if ($jenis === LogAbsensi::JENIS_MASUK && $absensi->jam_masuk) {
                throw ValidationException::withMessages([
                    'absensi' => ['Anda sudah absen masuk hari ini.'],
                ]);
            }

            if ($jenis === LogAbsensi::JENIS_PULANG) {
                if (! $absensi->jam_masuk) {
                    throw ValidationException::withMessages([
                        'absensi' => ['Anda belum absen masuk hari ini.'],
                    ]);
                }
                if ($absensi->jam_pulang) {
                    throw ValidationException::withMessages([
                        'absensi' => ['Anda sudah absen pulang hari ini.'],
                    ]);
                }
            }

            $pathFoto = null;
            if ($foto) {
                $pathFoto = $this->fotoCompressor->store(
                    $foto,
                    "absensi/{$user->id}/{$tanggal}",
                    'private'
                );
            }

            /** @var LokasiAbsensi|null $lokasi */
            $lokasi = $lokasiInfo['lokasi'];

            LogAbsensi::query()->create([
                'absensi_id' => $absensi->id,
                'user_id' => $user->id,
                'jenis' => $jenis,
                'waktu' => $now,
                'lokasi_absensi_id' => $lokasi?->id,
                'lintang' => $payload['lintang'] ?? null,
                'bujur' => $payload['bujur'] ?? null,
                'akurasi_meter' => $payload['akurasi_meter'] ?? null,
                'jarak_ke_kantor_meter' => $lokasiInfo['jarak_meter'],
                'dalam_radius' => $lokasiInfo['dalam_radius'],
                'path_foto' => $pathFoto,
                'nama_perangkat' => $payload['nama_perangkat'] ?? null,
                'alamat_ip' => $payload['alamat_ip'] ?? null,
                'meta' => $payload['meta'] ?? null,
                'valid' => true,
                'alasan_tolak' => null,
            ]);

            if ($jenis === LogAbsensi::JENIS_MASUK) {
                $absensi->jam_masuk = $now;
                $absensi->menit_terlambat = $this->hitungMenitTerlambat($pengaturan, $now, $user->id, $tanggal);
                $absensi->status = $absensi->menit_terlambat > 0
                    ? Absensi::STATUS_TERLAMBAT
                    : Absensi::STATUS_HADIR;
            } else {
                $absensi->jam_pulang = $now;
                $absensi->menit_pulang_cepat = $this->hitungMenitPulangCepat($pengaturan, $now, $user->id, $tanggal);
                if ($absensi->jam_masuk) {
                    $absensi->menit_kerja = max(0, $absensi->jam_masuk->diffInMinutes($now));
                }
            }

            $absensi->sumber = $payload['sumber'] ?? $absensi->sumber ?? 'mobile';
            $absensi->save();

            return $absensi->fresh(['logAbsensis', 'user']);
        });
    }

    protected function assertBukanCuti(User $user, string $tanggal): void
    {
        $cuti = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $tanggal)
            ->whereDate('end_date', '>=', $tanggal)
            ->exists();

        if ($cuti) {
            throw ValidationException::withMessages([
                'absensi' => ['Anda sedang cuti pada tanggal ini. Absensi tidak diizinkan.'],
            ]);
        }
    }

    protected function assertBukanLibur(string $tanggal): void
    {
        $libur = HariLibur::untukTanggal($tanggal);

        if ($libur && $libur->adalahLiburEfektif()) {
            throw ValidationException::withMessages([
                'absensi' => ["Tanggal ini libur ({$libur->nama}). Absensi tidak diizinkan."],
            ]);
        }
    }

    protected function assertFoto(PengaturanAbsensi $pengaturan, ?UploadedFile $foto): void
    {
        if (! $pengaturan->wajib_foto) {
            return;
        }

        if (! $foto) {
            throw ValidationException::withMessages([
                'foto' => ['Foto kamera wajib untuk absensi.'],
            ]);
        }

        $maxKb = (int) $pengaturan->ukuran_foto_maks_kb;
        if ($foto->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                'foto' => ["Ukuran foto maksimal {$maxKb} KB."],
            ]);
        }

        if (! in_array($foto->getMimeType(), ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages([
                'foto' => ['Format foto harus JPEG, PNG, atau WebP.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{dalam_radius: bool, jarak_meter: int|null, lokasi: LokasiAbsensi|null}
     */
    protected function assertLokasi(PengaturanAbsensi $pengaturan, array $payload): array
    {
        if (! $pengaturan->wajib_lokasi) {
            return [
                'dalam_radius' => true,
                'jarak_meter' => null,
                'lokasi' => null,
            ];
        }

        $lintang = $payload['lintang'] ?? null;
        $bujur = $payload['bujur'] ?? null;

        if ($lintang === null || $bujur === null) {
            throw ValidationException::withMessages([
                'lokasi' => ['Koordinat GPS wajib untuk absensi.'],
            ]);
        }

        $akurasi = $payload['akurasi_meter'] ?? null;
        if (
            $pengaturan->akurasi_gps_maksimal_meter
            && $akurasi !== null
            && (float) $akurasi > $pengaturan->akurasi_gps_maksimal_meter
        ) {
            throw ValidationException::withMessages([
                'lokasi' => ['Akurasi GPS terlalu rendah. Pindah ke area terbuka dan coba lagi.'],
            ]);
        }

        $hasil = $this->cekLokasi((float) $lintang, (float) $bujur);

        if ($pengaturan->tolak_jika_di_luar_radius && ! $hasil['dalam_radius']) {
            $jarak = $hasil['jarak_meter'] ?? 0;
            $radius = $hasil['lokasi']?->radius_meter ?? 0;
            $nama = $hasil['lokasi']?->nama ?? 'kantor';

            throw ValidationException::withMessages([
                'lokasi' => [
                    "Anda berada ±{$jarak} m dari {$nama} (batas {$radius} m). Absensi hanya diizinkan di area kantor.",
                ],
            ]);
        }

        return $hasil;
    }

    public function hitungMenitTerlambatPublik(
        PengaturanAbsensi $pengaturan,
        Carbon $now,
        ?int $userId = null,
        ?string $tanggal = null,
    ): int {
        return $this->hitungMenitTerlambat($pengaturan, $now, $userId, $tanggal);
    }

    public function hitungMenitPulangCepatPublik(
        PengaturanAbsensi $pengaturan,
        Carbon $now,
        ?int $userId = null,
        ?string $tanggal = null,
    ): int {
        return $this->hitungMenitPulangCepat($pengaturan, $now, $userId, $tanggal);
    }

    protected function hitungMenitTerlambat(
        PengaturanAbsensi $pengaturan,
        Carbon $now,
        ?int $userId = null,
        ?string $tanggal = null,
    ): int {
        $jamMasuk = $this->jamMasukEfektif($pengaturan, $now, $userId, $tanggal);

        $batas = $jamMasuk->copy()->addMinutes((int) $pengaturan->toleransi_terlambat_menit);

        if ($now->lte($batas)) {
            return 0;
        }

        return max(0, $jamMasuk->diffInMinutes($now));
    }

    protected function hitungMenitPulangCepat(
        PengaturanAbsensi $pengaturan,
        Carbon $now,
        ?int $userId = null,
        ?string $tanggal = null,
    ): int {
        $jamPulang = $this->jamPulangEfektif($pengaturan, $now, $userId, $tanggal);

        $batas = $jamPulang->copy()->subMinutes((int) $pengaturan->toleransi_pulang_cepat_menit);

        if ($now->gte($batas)) {
            return 0;
        }

        return max(0, $now->diffInMinutes($jamPulang));
    }

    protected function jamMasukEfektif(
        PengaturanAbsensi $pengaturan,
        Carbon $now,
        ?int $userId = null,
        ?string $tanggal = null,
    ): Carbon {
        $hari = $this->hariJadwalUntuk($userId, $tanggal ?? $now->toDateString());

        $time = ($hari?->hari_kerja && $hari->jam_masuk)
            ? $hari->jam_masuk
            : $pengaturan->jam_masuk;

        return $now->copy()->setTimeFromTimeString($time);
    }

    protected function jamPulangEfektif(
        PengaturanAbsensi $pengaturan,
        Carbon $now,
        ?int $userId = null,
        ?string $tanggal = null,
    ): Carbon {
        $hari = $this->hariJadwalUntuk($userId, $tanggal ?? $now->toDateString());

        $time = ($hari?->hari_kerja && $hari->jam_pulang)
            ? $hari->jam_pulang
            : $pengaturan->jam_pulang;

        return $now->copy()->setTimeFromTimeString($time);
    }

    protected function hariJadwalUntuk(?int $userId, string $tanggal): ?HariJadwalKerja
    {
        if (! $userId) {
            return null;
        }

        $penugasan = PenugasanJadwal::untukUserPada($userId, $tanggal);
        $jadwal = $penugasan?->jadwalKerja ?? \App\Models\JadwalKerja::defaultAktif();

        if (! $jadwal) {
            return null;
        }

        return $jadwal->hariUntuk(Carbon::parse($tanggal)->dayOfWeek);
    }
}
