<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class BankStatement extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'payment_method_id',
        'period_start',
        'period_end',
        'file_path',
        'original_filename',
        'source_type',
        'status',
        'uploaded_at',
        'processed_at',

        // Detail rekening
        'branch',
        'opening_balance',
        'closing_balance',
        'no_of_debit',
        'tot_debit',
        'no_of_credit',
        'tot_credit',

        // Rekonsiliasi bank
        'title',
        'description',
        'reconciliation_file',
        'reconciliation_original_filename',
        'total_records',
        'total_debit_reconciliation',
        'total_credit_reconciliation',
        'reconciliation_status',

        // Audit trail — set programatik, bukan dari user input
        'uploaded_by',
        'last_edited_by',
    ];

    protected $casts = [
        'period_start'                 => 'date',
        'period_end'                   => 'date',
        'uploaded_at'                  => 'datetime',
        'processed_at'                 => 'datetime',

        // Kolom finansial pakai decimal:0 agar aman untuk nilai besar (unsignedBigInteger di DB)
        'opening_balance'              => 'decimal:0',
        'closing_balance'              => 'decimal:0',
        'tot_debit'                    => 'decimal:0',
        'tot_credit'                   => 'decimal:0',
        'total_debit_reconciliation'   => 'decimal:0',
        'total_credit_reconciliation'  => 'decimal:0',

        // Kolom integer biasa
        'no_of_debit'                  => 'integer',
        'no_of_credit'                 => 'integer',
        'total_records'                => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Activity Log
    // -------------------------------------------------------------------------

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['payment_method_id', 'period_start', 'period_end', 'status', 'reconciliation_status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => $eventName)
            ->useLogName('bank_statement');
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function lastEditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    /**
     * Relasi ke item rekonsiliasi bank.
     * Gunakan nama 'reconciliationItems' (konsisten dengan eager loading dan RelationManager).
     * 'bankReconciliationItems' dihapus — duplikat identik.
     */
    public function reconciliationItems(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class, 'bank_reconciliation_id', 'id');
    }

    // -------------------------------------------------------------------------
    // Query Scopes
    // -------------------------------------------------------------------------

    /** Scope: hanya statement berstatus pending. */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** Scope: hanya statement yang sudah berhasil di-parse. */
    public function scopeParsed($query)
    {
        return $query->where('status', 'parsed');
    }

    /** Scope: hanya statement yang sedang diproses. */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /** Scope: hanya statement yang gagal diproses. */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /** Scope: filter berdasarkan status tertentu. */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /** Scope: filter berdasarkan periode (period_start >= $start dan period_end <= $end). */
    public function scopeInPeriod($query, $start, $end)
    {
        return $query->where('period_start', '>=', $start)
                     ->where('period_end', '<=', $end);
    }

    /** Scope: hanya statement yang belum direkonsiliasi. */
    public function scopeUnreconciled($query)
    {
        return $query->whereNull('reconciliation_file');
    }

    // -------------------------------------------------------------------------
    // Static Option Helpers
    // -------------------------------------------------------------------------

    public static function getStatusOptions(): array
    {
        return [
            'pending'    => 'Pending',
            'processing' => 'Processing',
            'parsed'     => 'Parsed',
            'failed'     => 'Failed',
        ];
    }

    public static function getSourceTypeOptions(): array
    {
        return [
            'pdf'          => 'PDF',
            'excel'        => 'Excel',
            'manual_input' => 'Manual Input',
        ];
    }

    public static function getReconciliationStatusOptions(): array
    {
        return [
            'uploaded'   => 'Uploaded',
            'processing' => 'Processing',
            'completed'  => 'Completed',
            'failed'     => 'Failed',
        ];
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return Arr::get(self::getStatusOptions(), $this->status, $this->status);
    }

    public function getReconciliationStatusLabelAttribute(): string
    {
        return Arr::get(self::getReconciliationStatusOptions(), $this->reconciliation_status, $this->reconciliation_status ?? '-');
    }
}
