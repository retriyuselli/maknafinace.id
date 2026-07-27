<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JadwalKerja extends Model
{
    protected $fillable = [
        'nama',
        'kode',
        'default',
        'aktif',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'default' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function hariJadwalKerjas(): HasMany
    {
        return $this->hasMany(HariJadwalKerja::class)->orderBy('hari');
    }

    public function penugasanJadwals(): HasMany
    {
        return $this->hasMany(PenugasanJadwal::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    public static function defaultAktif(): ?self
    {
        return static::query()
            ->aktif()
            ->where('default', true)
            ->latest('id')
            ->first()
            ?? static::query()->aktif()->latest('id')->first();
    }

    public function hariUntuk(int $dayOfWeek): ?HariJadwalKerja
    {
        return $this->hariJadwalKerjas->firstWhere('hari', $dayOfWeek);
    }
}
