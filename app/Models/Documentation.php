<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documentation extends Model
{
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentationCategory::class, 'documentation_category_id');
    }
}
