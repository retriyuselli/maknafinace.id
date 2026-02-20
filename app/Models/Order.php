<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Services\OrderFinance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    public static function computeGrandTotalFromValues(float $totalPrice, float $penambahan, float $promo, float $pengurangan): float
    {
        return OrderFinance::computeGrandTotalFromValues($totalPrice, $penambahan, $promo, $pengurangan);
    }

    protected $fillable = [
        'prospect_id',
        'slug',
        'name',
        'number',
        'user_id',
        'employee_id',
        'last_edited_by',
        'no_kontrak',
        'doc_kontrak',
        'pax',
        'note',
        'total_price',
        'paid_amount',
        'promo',
        'penambahan',
        'pengurangan',
        'grand_total',
        'change_amount',
        'is_paid',
        'closing_date',
        'status',
        'kategori_transaksi',
    ];

    protected $casts = [
        'bukti' => 'array',
        'status' => OrderStatus::class,
        'is_paid' => 'boolean',
        'total_price' => 'decimal:2',
        'promo' => 'decimal:2',
        'penambahan' => 'decimal:2',
        'pengurangan' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'bayar' => 'decimal:2',
        'closing_date' => 'date',
        'kategori_transaksi' => 'string',
    ];

    protected function finance(): OrderFinance
    {
        return OrderFinance::for($this);
    }

    protected static function booted(): void
    {
        static::deleting(function (Order $order) {
            // Saat sebuah Order dihapus, hapus juga semua relasi terkait.
            // Ini memastikan tidak ada data 'yatim' (orphaned records) di database.
            $order->expenses()->each(fn (Expense $expense) => $expense->delete());
            $order->dataPembayaran()->each(fn (DataPembayaran $pembayaran) => $pembayaran->delete());
            $order->items()->each(fn (OrderProduct $item) => $item->delete());
            if (Schema::hasTable('order_penambahans')) {
                $order->orderPenambahans()->each(fn (OrderPenambahan $penambahan) => $penambahan->delete());
            }
            if (Schema::hasTable('order_pengurangans')) {
                $order->orderPengurangans()->each(fn (OrderPengurangan $pengurangan) => $pengurangan->delete());
            }
        });
    }

    public function getPendapatanDpAttribute()
    {
        return $this->getBayarAttribute();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lastEditedBy()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function orderPenambahans()
    {
        return $this->hasMany(OrderPenambahan::class);
    }

    public function orderPengurangans()
    {
        return $this->hasMany(OrderPengurangan::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function grandTotalBase(): float
    {
        return $this->finance()->grandTotalBase();
    }

    protected function paymentsTotal(): float
    {
        return $this->finance()->paymentsTotal();
    }

    protected function expensesTotal(): float
    {
        return $this->finance()->expensesTotal();
    }

    public function calculateTotalPrice(): float
    {
        $totalPrice = 0;
        foreach ($this->items as $item) {
            $totalPrice += $item->quantity * $item->unit_price;
        }

        return $totalPrice;
    }

    public function dataPembayaran(): HasMany
    {
        return $this->hasMany(DataPembayaran::class);
    }

    public function getBayarAttribute()
    {
        return $this->finance()->bayar();
    }

    public function getSisaAttribute()
    {
        return $this->finance()->sisa();
    }

    public function dataPengeluaran(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getTotPengeluaranAttribute()
    {
        return $this->finance()->totPengeluaran();
    }

    public function getGrandTotalAttribute()
    {
        return $this->finance()->grandTotal();
    }

    public function getTotSisaAttribute()
    {
        return $this->finance()->totSisa();
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function setProspectAttribute($value)
    {
        $prospect = Prospect::find($value);
        $slug = $this->generateUniqueSlug($prospect->name_event);
        $this->attributes['prospect_id'] = $value;
        $this->attributes['slug'] = $slug;
    }

    public function getPendapatanAttribute()
    {
        return $this->finance()->pendapatan();
    }

    public function getPengeluaranAttribute()
    {
        return $this->finance()->pengeluaran();
    }

    // Laba Kotor
    public function getLabaKotorAttribute()
    {
        return $this->finance()->labaKotor();
    }

    public function getLabaBersihAttribute()
    {
        return $this->finance()->labaBersih();
    }

    public function calculateProfit()
    {
        return $this->finance()->grandTotal();
    }

    public function getUangDiterimaAttribute()
    {
        return $this->finance()->uangDiterima();
    }

    public function calculateAndSetGrandTotal()
    {
        $this->grand_total = $this->grandTotalBase();
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($order) {
            // Auto calculate grand_total before saving
            $order->calculateAndSetGrandTotal();
        });
    }
}