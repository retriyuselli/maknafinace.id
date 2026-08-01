<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PengaturanAbsensi extends Model
{
    protected $fillable = [
        'nama',
        'jam_masuk',
        'jam_pulang',
        'toleransi_terlambat_menit',
        'toleransi_pulang_cepat_menit',
        'wajib_pulang',
        'wajib_lokasi',
        'wajib_foto',
        'tolak_jika_di_luar_radius',
        'akurasi_gps_maksimal_meter',
        'ukuran_foto_maks_kb',
        'zona_waktu',
        'libur_sabtu',
        'libur_minggu',
        'denda_terlambat_per_menit',
        'tarif_lembur_per_menit',
        'aktif',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'wajib_pulang' => 'boolean',
            'wajib_lokasi' => 'boolean',
            'wajib_foto' => 'boolean',
            'tolak_jika_di_luar_radius' => 'boolean',
            'libur_sabtu' => 'boolean',
            'libur_minggu' => 'boolean',
            'aktif' => 'boolean',
            'toleransi_terlambat_menit' => 'integer',
            'toleransi_pulang_cepat_menit' => 'integer',
            'akurasi_gps_maksimal_meter' => 'integer',
            'ukuran_foto_maks_kb' => 'integer',
            'denda_terlambat_per_menit' => 'integer',
            'tarif_lembur_per_menit' => 'integer',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public static function aktifSekarang(): ?self
    {
        return static::query()->aktif()->latest('id')->first();
    }
}
