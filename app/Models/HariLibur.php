<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    protected $fillable = [
        'tanggal',
        'nama',
        'nasional',
        'tetap_masuk',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nasional' => 'boolean',
            'tetap_masuk' => 'boolean',
        ];
    }

    public function scopePadaTanggal(Builder $query, string $tanggal): Builder
    {
        return $query->whereDate('tanggal', $tanggal);
    }

    public static function untukTanggal(string $tanggal): ?self
    {
        return static::query()->padaTanggal($tanggal)->first();
    }

    public function adalahLiburEfektif(): bool
    {
        return ! $this->tetap_masuk;
    }
}
