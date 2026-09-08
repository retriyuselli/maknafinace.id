<?php

namespace App\Models;

use App\Enums\ItemPurchaseCodeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ItemPurchaseCode extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'prospect_app_id',
        'code',
        'company_name',
        'package',
        'domain',
        'activated_domain',
        'starts_at',
        'ends_at',
        'status',
        'activated_at',
        'last_verified_at',
        'revoked_at',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'status' => ItemPurchaseCodeStatus::class,
        'activated_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'company_name', 'status', 'starts_at', 'ends_at', 'activated_domain'])
            ->setDescriptionForEvent(fn (string $eventName) => $eventName)
            ->useLogName('item_purchase_code');
    }

    public function prospectApp(): BelongsTo
    {
        return $this->belongsTo(ProspectApp::class);
    }

    public function isWithinPeriod(?\DateTimeInterface $at = null): bool
    {
        $at = $at ? \Carbon\Carbon::parse($at)->startOfDay() : now()->startOfDay();

        return $this->starts_at->startOfDay()->lte($at)
            && $this->ends_at->startOfDay()->gte($at);
    }

    public function resolvedStatus(): ItemPurchaseCodeStatus
    {
        if ($this->status === ItemPurchaseCodeStatus::Revoked) {
            return ItemPurchaseCodeStatus::Revoked;
        }

        if ($this->ends_at->startOfDay()->lt(now()->startOfDay())) {
            return ItemPurchaseCodeStatus::Expired;
        }

        if ($this->status === ItemPurchaseCodeStatus::Expired) {
            return $this->activated_at
                ? ItemPurchaseCodeStatus::Active
                : ItemPurchaseCodeStatus::Unused;
        }

        return $this->status;
    }
}
