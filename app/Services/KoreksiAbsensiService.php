<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\KoreksiAbsensi;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KoreksiAbsensiService
{
    public function ajukan(
        User $user,
        Absensi $absensi,
        array $payload,
    ): KoreksiAbsensi {
        if ((int) $absensi->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'absensi' => ['Absensi tidak ditemukan untuk akun Anda.'],
            ]);
        }

        if (empty($payload['jam_masuk_diajukan']) && empty($payload['jam_pulang_diajukan'])) {
            throw ValidationException::withMessages([
                'jam' => ['Ajukan minimal jam masuk atau jam pulang.'],
            ]);
        }

        $pending = KoreksiAbsensi::query()
            ->where('absensi_id', $absensi->id)
            ->where('status', KoreksiAbsensi::STATUS_MENUNGGU)
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'absensi' => ['Masih ada pengajuan koreksi yang menunggu persetujuan untuk tanggal ini.'],
            ]);
        }

        return KoreksiAbsensi::query()->create([
            'absensi_id' => $absensi->id,
            'user_id' => $user->id,
            'jam_masuk_diajukan' => $payload['jam_masuk_diajukan'] ?? null,
            'jam_pulang_diajukan' => $payload['jam_pulang_diajukan'] ?? null,
            'alasan' => $payload['alasan'],
            'status' => KoreksiAbsensi::STATUS_MENUNGGU,
        ]);
    }

    public function setujui(KoreksiAbsensi $koreksi, User $peninjau, ?string $catatan = null): KoreksiAbsensi
    {
        if (! $koreksi->sedangMenunggu()) {
            throw ValidationException::withMessages([
                'status' => ['Pengajuan ini sudah ditinjau.'],
            ]);
        }

        return DB::transaction(function () use ($koreksi, $peninjau, $catatan) {
            /** @var Absensi $absensi */
            $absensi = Absensi::query()->lockForUpdate()->findOrFail($koreksi->absensi_id);
            $pengaturan = PengaturanAbsensi::aktifSekarang();
            $tz = $pengaturan?->zona_waktu ?? config('app.timezone', 'Asia/Jakarta');

            if ($koreksi->jam_masuk_diajukan) {
                $absensi->jam_masuk = $koreksi->jam_masuk_diajukan->timezone($tz);
            }

            if ($koreksi->jam_pulang_diajukan) {
                $absensi->jam_pulang = $koreksi->jam_pulang_diajukan->timezone($tz);
            }

            if ($absensi->jam_masuk && $pengaturan) {
                $absensi->menit_terlambat = app(AbsensiService::class)
                    ->hitungMenitTerlambatPublik($pengaturan, Carbon::parse($absensi->jam_masuk), $absensi->user_id, $absensi->tanggal->toDateString());
                $absensi->status = $absensi->menit_terlambat > 0
                    ? Absensi::STATUS_TERLAMBAT
                    : Absensi::STATUS_HADIR;
            }

            if ($absensi->jam_masuk && $absensi->jam_pulang) {
                $absensi->menit_kerja = max(0, $absensi->jam_masuk->diffInMinutes($absensi->jam_pulang));
                if ($pengaturan) {
                    $absensi->menit_pulang_cepat = app(AbsensiService::class)
                        ->hitungMenitPulangCepatPublik($pengaturan, Carbon::parse($absensi->jam_pulang), $absensi->user_id, $absensi->tanggal->toDateString());
                }
            }

            $absensi->disetujui_oleh = $peninjau->id;
            $absensi->sumber = $absensi->sumber ?: 'admin';
            $absensi->catatan = trim(($absensi->catatan ? $absensi->catatan."\n" : '').'Dikoreksi via pengajuan #'.$koreksi->id);
            $absensi->save();

            $koreksi->status = KoreksiAbsensi::STATUS_DISETUJUI;
            $koreksi->ditinjau_oleh = $peninjau->id;
            $koreksi->ditinjau_pada = now($tz);
            $koreksi->catatan_peninjau = $catatan;
            $koreksi->save();

            return $koreksi->fresh(['absensi', 'user', 'ditinjauOleh']);
        });
    }

    public function tolak(KoreksiAbsensi $koreksi, User $peninjau, ?string $catatan = null): KoreksiAbsensi
    {
        if (! $koreksi->sedangMenunggu()) {
            throw ValidationException::withMessages([
                'status' => ['Pengajuan ini sudah ditinjau.'],
            ]);
        }

        $koreksi->status = KoreksiAbsensi::STATUS_DITOLAK;
        $koreksi->ditinjau_oleh = $peninjau->id;
        $koreksi->ditinjau_pada = now();
        $koreksi->catatan_peninjau = $catatan;
        $koreksi->save();

        return $koreksi->fresh(['absensi', 'user', 'ditinjauOleh']);
    }
}
