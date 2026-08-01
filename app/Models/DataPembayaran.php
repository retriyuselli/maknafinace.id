<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DataPembayaran extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'order_id',
        'nominal',
        'image',
        'payment_method_id',
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
        'kategori_transaksi' => 'string',
        'nominal' => 'integer',
    ];

    // public function nominal(): BelongsTo
    // {
    //     return $this->belongsTo(Order::class);
    // }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'nominal', 'tgl_bayar', 'payment_method_id', 'keterangan'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('pembayaran');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function journalBatches(): HasMany
    {
        return $this->hasMany(JournalBatch::class, 'reference_id')
            ->whereIn('reference_type', ['payment', 'payment_reversal']);
    }
}
