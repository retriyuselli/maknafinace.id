<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanLembur extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    protected $fillable = [
        'user_id',
        'absensi_id',
        'tanggal',
        'mulai_pada',
        'selesai_pada',
        'menit',
        'alasan',
        'status',
        'disetujui_oleh',
        'disetujui_pada',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'disetujui_pada' => 'datetime',
            'menit' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class);
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function sedangMenunggu(): bool
    {
        return $this->status === self::STATUS_MENUNGGU;
    }

    public function labelDurasi(): string
    {
        $jam = intdiv(max(0, $this->menit), 60);
        $sisa = max(0, $this->menit) % 60;

        return "{$jam}j {$sisa}m";
    }
}
