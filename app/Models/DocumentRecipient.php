<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DocumentRecipient extends Model
{
    use LogsActivity;

    protected $fillable = [
        'document_id',
        'user_id',
        // 'department_id',
        'read_at',
        'is_cc',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_cc' => 'boolean',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['document_id', 'user_id', 'is_cc', 'read_at'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('document_recipient');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
