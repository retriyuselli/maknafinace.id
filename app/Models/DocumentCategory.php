<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'format_number',
        'parent_id',
        'is_approval_required',
    ];

    protected $casts = [
        'is_approval_required' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(DocumentCategory::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }
}
