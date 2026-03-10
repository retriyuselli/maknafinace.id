<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentationCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function documentations(): HasMany
    {
        return $this->hasMany(Documentation::class);
    }
}
