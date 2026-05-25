<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CompanyLogo extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_name',
        'website_url',
        'logo_path',
        'category',
        'display_order',
        'is_active',
        'alt_text',
        'description',
        'contact_email',
        'partnership_type',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the full URL for the logo
     */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['company_name', 'is_active', 'display_order'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('company_logo');
    }

    public function getLogoUrlAttribute(): string
    {
        if (! $this->logo_path) {
            return asset('images/placeholder-logo.png');
        }

        // If it's already a full URL, return as is
        if (filter_var($this->logo_path, FILTER_VALIDATE_URL)) {
            return $this->logo_path;
        }

        // If it's a local path, prepend the asset URL
        return asset('storage/'.$this->logo_path);
    }

    /**
     * Scope for active logos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for ordered display
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('company_name');
    }
}
