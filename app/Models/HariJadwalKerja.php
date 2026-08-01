<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HariJadwalKerja extends Model
{
    public const HARI_LABEL = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    protected $fillable = [
        'jadwal_kerja_id',
        'hari',
        'hari_kerja',
        'jam_masuk',
        'jam_pulang',
        'menit_istirahat',
    ];

    protected function casts(): array
    {
        return [
            'hari' => 'integer',
            'hari_kerja' => 'boolean',
            'menit_istirahat' => 'integer',
        ];
    }

    public function jadwalKerja(): BelongsTo
    {
        return $this->belongsTo(JadwalKerja::class);
    }

    public function labelHari(): string
    {
        return self::HARI_LABEL[$this->hari] ?? (string) $this->hari;
    }
}
