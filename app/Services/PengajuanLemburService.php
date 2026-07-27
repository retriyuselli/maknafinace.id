<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\PengajuanLembur;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PengajuanLemburService
{
    public function ajukan(User $user, array $payload): PengajuanLembur
    {
        $tz = PengaturanAbsensi::aktifSekarang()?->zona_waktu
            ?? config('app.timezone', 'Asia/Jakarta');

        $mulai = Carbon::parse($payload['mulai_pada'], $tz);
        $selesai = Carbon::parse($payload['selesai_pada'], $tz);

        if ($selesai->lte($mulai)) {
            throw ValidationException::withMessages([
                'selesai_pada' => ['Waktu selesai harus setelah waktu mulai.'],
            ]);
        }

        $menit = max(0, $mulai->diffInMinutes($selesai));

        if ($menit < 30) {
            throw ValidationException::withMessages([
                'selesai_pada' => ['Durasi lembur minimal 30 menit.'],
            ]);
        }

        $tanggal = isset($payload['tanggal'])
            ? Carbon::parse($payload['tanggal'], $tz)->toDateString()
            : $mulai->toDateString();

        $absensiId = $payload['absensi_id'] ?? null;
        if ($absensiId) {
            $absensi = Absensi::query()
                ->whereKey($absensiId)
                ->where('user_id', $user->id)
                ->first();

            if (! $absensi) {
                throw ValidationException::withMessages([
                    'absensi_id' => ['Absensi tidak valid untuk akun Anda.'],
                ]);
            }

            $tanggal = $absensi->tanggal->toDateString();
        } else {
            $absensi = Absensi::query()
                ->where('user_id', $user->id)
                ->whereDate('tanggal', $tanggal)
                ->first();
            $absensiId = $absensi?->id;
        }

        $overlap = PengajuanLembur::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                PengajuanLembur::STATUS_MENUNGGU,
                PengajuanLembur::STATUS_DISETUJUI,
            ])
            ->where(function ($query) use ($mulai, $selesai) {
                $query->where('mulai_pada', '<', $selesai)
                    ->where('selesai_pada', '>', $mulai);
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'mulai_pada' => ['Rentang waktu bentrok dengan pengajuan lembur lain.'],
            ]);
        }

        return PengajuanLembur::query()->create([
            'user_id' => $user->id,
            'absensi_id' => $absensiId,
            'tanggal' => $tanggal,
            'mulai_pada' => $mulai,
            'selesai_pada' => $selesai,
            'menit' => $menit,
            'alasan' => $payload['alasan'],
            'status' => PengajuanLembur::STATUS_MENUNGGU,
        ]);
    }

    public function setujui(PengajuanLembur $pengajuan, User $peninjau, ?string $catatan = null): PengajuanLembur
    {
        if (! $pengajuan->sedangMenunggu()) {
            throw ValidationException::withMessages([
                'status' => ['Pengajuan ini sudah ditinjau.'],
            ]);
        }

        $pengajuan->status = PengajuanLembur::STATUS_DISETUJUI;
        $pengajuan->disetujui_oleh = $peninjau->id;
        $pengajuan->disetujui_pada = now();
        $pengajuan->catatan = $catatan;
        $pengajuan->save();

        return $pengajuan->fresh(['user', 'absensi', 'disetujuiOleh']);
    }

    public function tolak(PengajuanLembur $pengajuan, User $peninjau, ?string $catatan = null): PengajuanLembur
    {
        if (! $pengajuan->sedangMenunggu()) {
            throw ValidationException::withMessages([
                'status' => ['Pengajuan ini sudah ditinjau.'],
            ]);
        }

        $pengajuan->status = PengajuanLembur::STATUS_DITOLAK;
        $pengajuan->disetujui_oleh = $peninjau->id;
        $pengajuan->disetujui_pada = now();
        $pengajuan->catatan = $catatan;
        $pengajuan->save();

        return $pengajuan->fresh(['user', 'absensi', 'disetujuiOleh']);
    }
}
