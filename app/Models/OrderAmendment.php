<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAmendment extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'reason',
        'before',
        'after',
        'diff',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'diff' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
