<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ProductVendor extends Model
{
    use LogsActivity;

    protected $fillable = [
        'product_id',
        'vendor_id',
        'kontrak_kerjasama',
        'simulasi_produk_id',
        'harga_publish',
        'description',
        'quantity',
        'price_public', // Total dari harga publish * quantity
        'total_price', // Total dari harga product
        'harga_vendor', // Harga dari vendor
    ];

    protected $casts = [
        'harga_publish' => 'integer',
        'quantity' => 'integer',
        'price_public' => 'integer',
        'total_price' => 'integer',
        'harga_vendor' => 'integer',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'vendor_id', 'harga_publish', 'quantity'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('product_vendor');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function simulasiProduk(): BelongsTo
    {
        return $this->belongsTo(SimulasiProduk::class);
    }

    public function getCalculatePriceVendorAttribute()
    {
        return $this->harga_vendor * $this->quantity;
    }

    public function getCalculatePricePublicAttribute()
    {
        return $this->harga_publish * $this->quantity;
    }
}
