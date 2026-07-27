<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Absensi extends Model
{
    public const STATUS_HADIR = 'hadir';

    public const STATUS_TERLAMBAT = 'terlambat';

    public const STATUS_ALFA = 'alfa';

    public const STATUS_CUTI = 'cuti';

    public const STATUS_LIBUR = 'libur';

    public const STATUS_LIBUR_MINGGUAN = 'libur_mingguan';

    public const STATUS_SETENGAH_HARI = 'setengah_hari';

    public const STATUS_REMOTE = 'remote';

    protected $fillable = [
        'user_id',
        'tanggal',
        'status',
        'jam_masuk',
        'jam_pulang',
        'menit_kerja',
        'menit_terlambat',
        'menit_pulang_cepat',
        'sumber',
        'catatan',
        'leave_request_id',
        'disetujui_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_masuk' => 'datetime',
            'jam_pulang' => 'datetime',
            'menit_kerja' => 'integer',
            'menit_terlambat' => 'integer',
            'menit_pulang_cepat' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function logAbsensis(): HasMany
    {
        return $this->hasMany(LogAbsensi::class);
    }

    public function koreksiAbsensis(): HasMany
    {
        return $this->hasMany(KoreksiAbsensi::class);
    }

    public function pengajuanLemburs(): HasMany
    {
        return $this->hasMany(PengajuanLembur::class);
    }
}
