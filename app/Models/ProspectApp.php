<?php

namespace App\Models;

use App\Enums\ProspectAppStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ProspectApp extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'full_name',
        'email',
        'position',
        'phone',
        'company_name',
        'industry_id',
        'name_of_website',
        'service',
        'notes',
        'user_size',
        'harga',
        'bayar',
        'tgl_bayar',
        'reason_for_interest',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'tgl_bayar'    => 'date',
        'harga'        => 'integer',
        'bayar'        => 'integer',
        'status'       => ProspectAppStatus::class,
    ];

    // Relationships

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['full_name', 'email', 'company_name', 'service'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('prospect_app');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Accessors
    public function getStatusBadgeColorAttribute(): string
    {
        return $this->status->getColor();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->getLabel();
    }

    public function getSisaBayarAttribute(): int
    {
        return max(0, ($this->harga ?? 0) - ($this->bayar ?? 0));
    }
}
