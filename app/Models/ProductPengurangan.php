<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ProductPengurangan extends Model
{
    use LogsActivity;

    protected $fillable = [
        'product_id',
        'description',
        'amount',
        'notes', // Optional: if you added notes in the repeater
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2', // Cast amount to a decimal with 2 places
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'description', 'amount'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('product_pengurangan');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
