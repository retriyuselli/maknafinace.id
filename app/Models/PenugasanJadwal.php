<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenugasanJadwal extends Model
{
    protected $fillable = [
        'user_id',
        'jadwal_kerja_id',
        'berlaku_dari',
        'berlaku_sampai',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'berlaku_dari' => 'date',
            'berlaku_sampai' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jadwalKerja(): BelongsTo
    {
        return $this->belongsTo(JadwalKerja::class);
    }

    public function scopeBerlakuPada(Builder $query, string $tanggal): Builder
    {
        return $query
            ->whereDate('berlaku_dari', '<=', $tanggal)
            ->where(function (Builder $q) use ($tanggal) {
                $q->whereNull('berlaku_sampai')
                    ->orWhereDate('berlaku_sampai', '>=', $tanggal);
            });
    }

    public static function untukUserPada(int $userId, string $tanggal): ?self
    {
        return static::query()
            ->with(['jadwalKerja.hariJadwalKerjas'])
            ->where('user_id', $userId)
            ->berlakuPada($tanggal)
            ->latest('berlaku_dari')
            ->latest('id')
            ->first();
    }
}
