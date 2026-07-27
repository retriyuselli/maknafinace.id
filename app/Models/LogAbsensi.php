<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class LogAbsensi extends Model
{
    public const JENIS_MASUK = 'masuk';

    public const JENIS_PULANG = 'pulang';

    protected $fillable = [
        'absensi_id',
        'user_id',
        'jenis',
        'waktu',
        'lokasi_absensi_id',
        'lintang',
        'bujur',
        'akurasi_meter',
        'jarak_ke_kantor_meter',
        'dalam_radius',
        'path_foto',
        'nama_perangkat',
        'alamat_ip',
        'meta',
        'valid',
        'alasan_tolak',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
            'lintang' => 'float',
            'bujur' => 'float',
            'akurasi_meter' => 'float',
            'jarak_ke_kantor_meter' => 'integer',
            'dalam_radius' => 'boolean',
            'meta' => 'array',
            'valid' => 'boolean',
        ];
    }

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lokasiAbsensi(): BelongsTo
    {
        return $this->belongsTo(LokasiAbsensi::class);
    }

    public function urlFoto(): ?string
    {
        if (! $this->path_foto || ! $this->fotoDisk()) {
            return null;
        }

        return URL::temporarySignedRoute(
            'absensi.logs.foto',
            now()->addMinutes(60),
            ['logAbsensi' => $this->getKey()]
        );
    }

    public function temporaryFotoUrl(?CarbonInterface $expiresAt = null): ?string
    {
        if (! $this->path_foto || ! $this->fotoDisk()) {
            return null;
        }

        return URL::temporarySignedRoute(
            'absensi.logs.foto',
            $expiresAt ?? now()->addMinutes(60),
            ['logAbsensi' => $this->getKey()]
        );
    }

    public function fotoDisk(): ?string
    {
        if (! $this->path_foto) {
            return null;
        }

        foreach (['private', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($this->path_foto)) {
                return $disk;
            }
        }

        return null;
    }
}
