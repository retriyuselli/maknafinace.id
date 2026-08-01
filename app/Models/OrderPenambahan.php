<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class OrderPenambahan extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'order_id',
        'vendor_id',
        'name',
        'description',
        'harga_publish',
        'harga_vendor',
    ];

    protected $casts = [
        'harga_publish' => 'decimal:2',
        'harga_vendor' => 'decimal:2',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'vendor_id', 'harga_publish', 'harga_vendor'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('order_penambahan');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
