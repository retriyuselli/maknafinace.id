<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DocumentAttachment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'document_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['document_id', 'file_name', 'mime_type'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('document_attachment');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
