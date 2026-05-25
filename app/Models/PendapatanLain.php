<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PendapatanLain extends Model
{
    use HasFactory,
        SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'vendor_id',
        'payment_method_id',
        'nominal',
        'image',
        'tgl_bayar',
        'keterangan',
        'kategori_transaksi',
        'reconciliation_status',
        'matched_bank_item_id',
        'match_confidence',
        'reconciliation_notes',
    ];

    protected $casts = [
        'tgl_bayar' => 'date',
        'nominal' => 'integer',
        'kategori_transaksi' => 'string',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nominal', 'tanggal', 'keterangan', 'category_id'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('pendapatan_lain');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
