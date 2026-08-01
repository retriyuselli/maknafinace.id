<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Documentation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'documentation_category_id',
        'title',
        'slug',
        'content',
        'is_published',
        'keywords',
        'related_resource',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'is_published', 'documentation_category_id'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('documentation');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentationCategory::class, 'documentation_category_id');
    }
}
