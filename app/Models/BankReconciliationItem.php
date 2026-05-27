<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class BankReconciliationItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'bank_reconciliation_id',
        'date',
        'description',
        'debit',
        'credit',
        'row_number',
    ];

    protected $casts = [
        'date' => 'date',
        'debit' => 'integer',
        'credit' => 'integer',
    ];

    // ========================
    // DEBIT/CREDIT MAPPING METHODS
    // ========================

    /**
     * Get transaction direction (user-friendly)
     * Returns: 'masuk' or 'keluar'
     */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['date', 'description', 'debit', 'credit'])
            ->logOnlyDirty()   // hindari log perubahan yang tidak nyata
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('bank_reconciliation');
    }

    public function getDirectionAttribute(): string
    {
        return $this->debit > 0 ? 'keluar' : 'masuk';
    }

    /**
     * Get direction label (user-friendly)
     * Returns: 'Uang Masuk' or 'Uang Keluar'
     */
    public function getDirectionLabelAttribute(): string
    {
        return $this->debit > 0 ? 'Uang Keluar' : 'Uang Masuk';
    }

    /**
     * Get transaction amount (regardless of debit/credit)
     */
    public function getAmountAttribute(): float
    {
        return $this->debit > 0 ? $this->debit : $this->credit;
    }

    /**
     * Get formatted amount with direction
     */
    public function getFormattedAmountAttribute(): string
    {
        $amount = number_format($this->amount, 0, ',', '.');
        $prefix = $this->debit > 0 ? '- Rp' : '+ Rp';

        return $prefix.' '.$amount;
    }

    /**
     * Get bank terminology (technical)
     */
    public function getBankTermAttribute(): string
    {
        return $this->debit > 0 ? 'Debit' : 'Credit';
    }

    /**
     * Check if this is a debit transaction
     */
    public function getIsDebitAttribute(): bool
    {
        return $this->debit > 0;
    }

    /**
     * Check if this is a credit transaction
     */
    public function getIsCreditAttribute(): bool
    {
        return $this->credit > 0;
    }

    // Relasi ke BankStatement induk
    public function bankStatement(): BelongsTo
    {
        return $this->belongsTo(BankStatement::class, 'bank_reconciliation_id', 'id');
    }
}
