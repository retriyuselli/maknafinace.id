<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KoreksiAbsensi extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    protected $fillable = [
        'absensi_id',
        'user_id',
        'jam_masuk_diajukan',
        'jam_pulang_diajukan',
        'alasan',
        'status',
        'ditinjau_oleh',
        'ditinjau_pada',
        'catatan_peninjau',
    ];

    protected function casts(): array
    {
        return [
            'jam_masuk_diajukan' => 'datetime',
            'jam_pulang_diajukan' => 'datetime',
            'ditinjau_pada' => 'datetime',
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

    public function ditinjauOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }

    public function sedangMenunggu(): bool
    {
        return $this->status === self::STATUS_MENUNGGU;
    }
}
