<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class BankTransaction extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'bank_statement_id',
        'transaction_date',
        'value_date',
        'description',
        'reference_number',
        'debit_amount',
        'credit_amount',
        'balance',
        'transaction_type',
        'category',
        'is_matched',
        'matched_with_transaction_id',
        'matching_confidence',
        'notes',
    ];

    protected $casts = [
        'transaction_date'    => 'date',
        'value_date'          => 'date',
        'debit_amount'        => 'decimal:0',
        'credit_amount'       => 'decimal:0',
        'balance'             => 'decimal:0',
        'is_matched'          => 'boolean',
        'matching_confidence' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Activity Log
    // -------------------------------------------------------------------------

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Fix: gunakan nama kolom yang sebenarnya ada di tabel
            ->logOnly([
                'bank_statement_id',
                'transaction_date',
                'debit_amount',
                'credit_amount',
                'transaction_type',
                'description',
                'is_matched',
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => $eventName)
            ->useLogName('bank_transaction');
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class);
    }

    public function matchedTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'matched_with_transaction_id');
    }

    // -------------------------------------------------------------------------
    // Static Option Helpers
    // -------------------------------------------------------------------------

    public static function getTransactionTypes(): array
    {
        return [
            'debit'  => 'Debit - Uang Keluar',
            'credit' => 'Credit - Uang Masuk',
        ];
    }

    public static function getCategories(): array
    {
        return [
            'transfer'   => 'Transfer',
            'deposit'    => 'Setoran',
            'withdrawal' => 'Penarikan',
            'fee'        => 'Biaya Admin',
            'interest'   => 'Bunga',
            'charge'     => 'Biaya Lainnya',
            'correction' => 'Koreksi',
            'other'      => 'Lainnya',
        ];
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /** Jumlah transaksi (ambil debit atau credit mana yang ada nilainya). */
    public function getAmountAttribute(): float
    {
        return (float) ($this->debit_amount ?: $this->credit_amount);
    }

    /** Net amount: credit positif, debit negatif. */
    public function getNetAmountAttribute(): float
    {
        return (float) ($this->credit_amount - $this->debit_amount);
    }

    public function getIsDebitAttribute(): bool
    {
        return $this->debit_amount > 0;
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->credit_amount > 0;
    }

    public function getDirectionAttribute(): string
    {
        return $this->is_debit ? 'Keluar' : 'Masuk';
    }

    public function getFormattedAmountAttribute(): string
    {
        $amount = number_format($this->amount, 0, ',', '.');
        $prefix = $this->is_debit ? '-' : '+';

        return $prefix . 'Rp ' . $amount;
    }

    // -------------------------------------------------------------------------
    // Query Scopes
    // -------------------------------------------------------------------------

    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->where('is_matched', false);
    }

    public function scopeMatched(Builder $query): Builder
    {
        return $query->where('is_matched', true);
    }

    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('debit_amount', '>', 0);
    }

    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('credit_amount', '>', 0);
    }

    public function scopeInDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // -------------------------------------------------------------------------
    // Business Logic
    // -------------------------------------------------------------------------

    /** Tandai transaksi ini sudah dicocokkan dengan transaksi sistem. */
    public function markAsMatched(int $transactionId, int $confidence = 100, ?string $notes = null): void
    {
        $this->update([
            'is_matched'                  => true,
            'matched_with_transaction_id' => $transactionId,
            'matching_confidence'         => $confidence,
            'notes'                       => $notes,
        ]);
    }

    /** Batalkan pencocokan transaksi. */
    public function unmarkAsMatched(): void
    {
        $this->update([
            'is_matched'                  => false,
            'matched_with_transaction_id' => null,
            'matching_confidence'         => null,
            'notes'                       => null,
        ]);
    }
}
