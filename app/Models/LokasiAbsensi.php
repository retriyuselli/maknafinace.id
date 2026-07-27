<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LokasiAbsensi extends Model
{
    protected $fillable = [
        'nama',
        'lintang',
        'bujur',
        'radius_meter',
        'aktif',
        'alamat',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'lintang' => 'float',
            'bujur' => 'float',
            'radius_meter' => 'integer',
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true)->orderBy('urutan')->orderBy('id');
    }

    public function logAbsensis(): HasMany
    {
        return $this->hasMany(LogAbsensi::class);
    }
}
