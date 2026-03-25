<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class NotaDinasDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nota_dinas_id',
        'nama_rekening',
        'vendor_id',
        'keperluan',
        'event',
        'jumlah_transfer',
        'invoice_number',
        'invoice_file',
        'bank_name',
        'bank_account',
        'account_holder',
        'status_invoice', // belum dibayar, sudah dibayar, dsb
        'jenis_pengeluaran',
        'payment_stage',
        'order_id',
        'order_product_id',
        'product_vendor_id',
    ];

    protected $casts = [
        'jumlah_transfer' => 'integer',
        'payment_stage' => 'string',
        'order_id' => 'integer',
        'order_product_id' => 'integer',
        'product_vendor_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (NotaDinasDetail $record): void {
            if ($record->jenis_pengeluaran !== 'wedding') {
                return;
            }

            if (blank($record->order_product_id) || (blank($record->product_vendor_id) && blank($record->vendor_id))) {
                return;
            }

            if (blank($record->order_id)) {
                throw ValidationException::withMessages([
                    'order_id' => 'Order wajib dipilih untuk Wedding.',
                ]);
            }

            $orderProduct = OrderProduct::query()
                ->whereKey($record->order_product_id)
                ->where('order_id', $record->order_id)
                ->first();

            if (! $orderProduct) {
                throw ValidationException::withMessages([
                    'order_product_id' => 'Produk tidak valid untuk order yang dipilih.',
                ]);
            }

            // If we have product_vendor_id, it's from basic facility. Otherwise, if vendor_id is filled but product_vendor_id is blank, it's from Penambahan (Additional).
            if (filled($record->product_vendor_id)) {
                $productVendor = ProductVendor::query()
                    ->whereKey($record->product_vendor_id)
                    ->where('product_id', $orderProduct->product_id)
                    ->first();

                if (! $productVendor) {
                    throw ValidationException::withMessages([
                        'product_vendor_id' => 'Vendor produk tidak valid untuk produk yang dipilih.',
                    ]);
                }

                $record->vendor_id = $productVendor->vendor_id;
            } else if (blank($record->vendor_id)) {
                throw ValidationException::withMessages([
                    'product_vendor_id' => 'Vendor produk atau penambahan tidak valid untuk produk yang dipilih.',
                ]);
            }

            if (blank($record->payment_stage)) {
                $record->payment_stage = 'DP';
            }

            $stage = (string) $record->payment_stage;

            $queryBase = static::query()
                ->where('jenis_pengeluaran', 'wedding')
                ->where('order_id', $record->order_id);

            // Group by either product_vendor_id or just vendor_id to avoid payment duplication
            if (filled($record->product_vendor_id)) {
                $queryBase->where('product_vendor_id', $record->product_vendor_id);
            } else {
                $queryBase->whereNull('product_vendor_id')->where('vendor_id', $record->vendor_id);
            }

            $finalExists = (clone $queryBase)
                ->where('payment_stage', 'Final Payment')
                ->when($record->exists, fn ($q) => $q->whereKeyNot($record->id))
                ->exists();

            if (! $record->exists && $finalExists) {
                throw ValidationException::withMessages([
                    'payment_stage' => 'Pembayaran sudah Final untuk item ini. Tidak boleh input lagi.',
                ]);
            }

            if ($record->exists && $finalExists && ($record->isDirty('payment_stage') || $record->isDirty('product_vendor_id') || $record->isDirty('order_id') || $record->isDirty('order_product_id'))) {
                throw ValidationException::withMessages([
                    'payment_stage' => 'Pembayaran sudah Final untuk item ini. Tahap tidak boleh diubah.',
                ]);
            }

            if ($stage !== 'Additional') {
                $duplicateStage = (clone $queryBase)
                    ->where('payment_stage', $stage)
                    ->when($record->exists, fn ($q) => $q->whereKeyNot($record->id))
                    ->exists();

                if ($duplicateStage) {
                    throw ValidationException::withMessages([
                        'payment_stage' => 'Tahap pembayaran ini sudah ada untuk item tersebut.',
                    ]);
                }
            }
        });
    }

    public function notaDinas(): BelongsTo
    {
        return $this->belongsTo(NotaDinas::class, 'nota_dinas_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function productVendor(): BelongsTo
    {
        return $this->belongsTo(ProductVendor::class, 'product_vendor_id');
    }

    public function expense()
    {
        return $this->hasOne(Expense::class, 'nota_dinas_detail_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'nota_dinas_detail_id');
    }

    public function expenseOps()
    {
        return $this->hasMany(ExpenseOps::class, 'nota_dinas_detail_id');
    }

    public function pengeluaranLains()
    {
        return $this->hasMany(PengeluaranLain::class, 'nota_dinas_detail_id');
    }

    public function getFormattedLabelAttribute()
    {
        $vendorName = $this->vendor->name ?? 'N/A';
        $keperluan = $this->keperluan ?? 'N/A';
        $jumlah = number_format($this->jumlah_transfer, 0, ',', '.');
        $paymentStage = $this->payment_stage ? " | {$this->payment_stage}" : '';

        return "{$this->notaDinas->no_nd} | {$vendorName} | {$keperluan}{$paymentStage} | Rp {$jumlah}";
    }
}
