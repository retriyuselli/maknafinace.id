<?php

namespace App\Models;

use App\Services\ProductPricingCalculator;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ProductPengurangan extends Model
{
    use LogsActivity;

    protected $fillable = [
        'product_id',
        'description',
        'amount',
        'publish_only',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
    ];

    protected function publishOnly(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): bool => ProductPricingCalculator::isPublishOnly($value),
            set: fn (mixed $value): int => ProductPricingCalculator::isPublishOnly($value) ? 1 : 0,
        );
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'description', 'amount', 'publish_only'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('product_pengurangan');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
