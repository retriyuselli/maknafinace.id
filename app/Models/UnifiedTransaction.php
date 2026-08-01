<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Virtual model — menggabungkan transaksi dari 5 tabel sumber ke satu struktur seragam.
 * Tidak memiliki tabel DB sendiri; hanya dibuat di memory dengan new self([...]).
 */
class UnifiedTransaction extends Model
{
    // Konstanta source_type — gunakan nilai ini secara konsisten di seluruh aplikasi
    public const SOURCE_WEDDING_PAYMENT     = 'wedding_payment';
    public const SOURCE_OTHER_INCOME        = 'other_income';
    public const SOURCE_WEDDING_EXPENSE     = 'wedding_expense';
    public const SOURCE_OPERATIONAL_EXPENSE = 'operational_expense';
    public const SOURCE_OTHER_EXPENSE       = 'other_expense';

    public $timestamps = false;

    protected $fillable = [
        'payment_method_id',
        'transaction_date',
        'description',
        'debit_amount',
        'credit_amount',
        'source_type',
        'source_id',
        'source_table',
        'reconciliation_status',
        'matched_bank_item_id',
        'match_confidence',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit_amount'     => 'decimal:2',
        'credit_amount'    => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // -------------------------------------------------------------------------
    // Static Builders
    // -------------------------------------------------------------------------

    /**
     * Ambil semua transaksi sistem (dari 5 tabel sumber) untuk satu PaymentMethod.
     * Status rekonsiliasi dibaca langsung dari record sumber — bukan hardcode 'unmatched'.
     */
    public static function getForPaymentMethod(
        int $paymentMethodId,
        ?string $startDate = null,
        ?string $endDate = null
    ): Collection {
        $transactions = collect();

        // 1. DataPembayaran (Pembayaran Wedding — Credit/Masuk)
        $weddingPayments = DataPembayaran::with(['order.prospect'])
            ->where('payment_method_id', $paymentMethodId)
            ->when($startDate, fn ($q) => $q->where('tgl_bayar', '>=', $startDate))
            ->when($endDate,   fn ($q) => $q->where('tgl_bayar', '<=', $endDate))
            ->whereNull('deleted_at')
            ->get()
            ->map(function (DataPembayaran $payment) {
                $description = $payment->keterangan ?? 'Wedding Payment';
                if ($payment->order?->prospect) {
                    $prospect    = $payment->order->prospect;
                    $customerName = $prospect->name_event
                        ?: ($prospect->name_cpp ?: $prospect->name_cpw);
                    if ($customerName) {
                        $description = "Payment - {$customerName}"
                            . ($payment->keterangan ? " ({$payment->keterangan})" : '');
                    }
                }

                return new self([
                    'payment_method_id'    => $payment->payment_method_id,
                    'transaction_date'     => $payment->tgl_bayar,
                    'description'          => $description,
                    'debit_amount'         => 0,
                    'credit_amount'        => $payment->nominal,
                    'source_type'          => self::SOURCE_WEDDING_PAYMENT,
                    'source_id'            => $payment->id,
                    'source_table'         => 'data_pembayarans',
                    // Baca status rekonsiliasi dari record asli, bukan hardcode
                    'reconciliation_status' => $payment->reconciliation_status ?? 'unmatched',
                    'matched_bank_item_id'  => $payment->matched_bank_item_id,
                    'match_confidence'      => $payment->match_confidence,
                ]);
            });

        // 2. PendapatanLain (Pendapatan Lain — Credit/Masuk)
        $otherIncome = PendapatanLain::where('payment_method_id', $paymentMethodId)
            ->when($startDate, fn ($q) => $q->where('tgl_bayar', '>=', $startDate))
            ->when($endDate,   fn ($q) => $q->where('tgl_bayar', '<=', $endDate))
            ->whereNull('deleted_at')
            ->get()
            ->map(function (PendapatanLain $income) {
                return new self([
                    'payment_method_id'     => $income->payment_method_id,
                    'transaction_date'      => $income->tgl_bayar,
                    'description'           => $income->keterangan ?? 'Other Income',
                    'debit_amount'          => 0,
                    'credit_amount'         => $income->nominal,
                    'source_type'           => self::SOURCE_OTHER_INCOME,
                    'source_id'             => $income->id,
                    'source_table'          => 'pendapatan_lains',
                    'reconciliation_status' => $income->reconciliation_status ?? 'unmatched',
                    'matched_bank_item_id'  => $income->matched_bank_item_id,
                    'match_confidence'      => $income->match_confidence,
                ]);
            });

        // 3. Expense (Pengeluaran Wedding — Debit/Keluar)
        $weddingExpenses = Expense::with(['order.prospect'])
            ->where('payment_method_id', $paymentMethodId)
            ->when($startDate, fn ($q) => $q->where('date_expense', '>=', $startDate))
            ->when($endDate,   fn ($q) => $q->where('date_expense', '<=', $endDate))
            ->whereNull('deleted_at')
            ->get()
            ->map(function (Expense $expense) {
                $description = $expense->note ?? 'Wedding Expense';
                if ($expense->order?->prospect) {
                    $prospect     = $expense->order->prospect;
                    $customerName = $prospect->name_event
                        ?: ($prospect->name_cpp ?: $prospect->name_cpw);
                    if ($customerName) {
                        $description = "Expense - {$customerName}"
                            . ($expense->note ? " ({$expense->note})" : '');
                    }
                }

                return new self([
                    'payment_method_id'     => $expense->payment_method_id,
                    'transaction_date'      => $expense->date_expense,
                    'description'           => $description,
                    'debit_amount'          => $expense->amount,
                    'credit_amount'         => 0,
                    'source_type'           => self::SOURCE_WEDDING_EXPENSE,
                    'source_id'             => $expense->id,
                    'source_table'          => 'expenses',
                    'reconciliation_status' => $expense->reconciliation_status ?? 'unmatched',
                    'matched_bank_item_id'  => $expense->matched_bank_item_id,
                    'match_confidence'      => $expense->match_confidence,
                ]);
            });

        // 4. ExpenseOps (Pengeluaran Operasional — Debit/Keluar)
        $operationalExpenses = ExpenseOps::where('payment_method_id', $paymentMethodId)
            ->when($startDate, fn ($q) => $q->where('date_expense', '>=', $startDate))
            ->when($endDate,   fn ($q) => $q->where('date_expense', '<=', $endDate))
            ->whereNull('deleted_at')
            ->get()
            ->map(function (ExpenseOps $expense) {
                return new self([
                    'payment_method_id'     => $expense->payment_method_id,
                    'transaction_date'      => $expense->date_expense,
                    'description'           => $expense->name ?? $expense->note ?? 'Operational Expense',
                    'debit_amount'          => $expense->amount,
                    'credit_amount'         => 0,
                    'source_type'           => self::SOURCE_OPERATIONAL_EXPENSE,
                    'source_id'             => $expense->id,
                    'source_table'          => 'expense_ops',
                    'reconciliation_status' => $expense->reconciliation_status ?? 'unmatched',
                    'matched_bank_item_id'  => $expense->matched_bank_item_id,
                    'match_confidence'      => $expense->match_confidence,
                ]);
            });

        // 5. PengeluaranLain (Pengeluaran Lain — Debit/Keluar)
        $otherExpenses = PengeluaranLain::where('payment_method_id', $paymentMethodId)
            ->when($startDate, fn ($q) => $q->where('date_expense', '>=', $startDate))
            ->when($endDate,   fn ($q) => $q->where('date_expense', '<=', $endDate))
            ->whereNull('deleted_at')
            ->get()
            ->map(function (PengeluaranLain $expense) {
                return new self([
                    'payment_method_id'     => $expense->payment_method_id,
                    'transaction_date'      => $expense->date_expense,
                    'description'           => $expense->name ?? $expense->note ?? 'Other Expense',
                    'debit_amount'          => $expense->amount,
                    'credit_amount'         => 0,
                    'source_type'           => self::SOURCE_OTHER_EXPENSE,
                    'source_id'             => $expense->id,
                    'source_table'          => 'pengeluaran_lains',
                    'reconciliation_status' => $expense->reconciliation_status ?? 'unmatched',
                    'matched_bank_item_id'  => $expense->matched_bank_item_id,
                    'match_confidence'      => $expense->match_confidence,
                ]);
            });

        return $transactions
            ->concat($weddingPayments)
            ->concat($otherIncome)
            ->concat($weddingExpenses)
            ->concat($operationalExpenses)
            ->concat($otherExpenses)
            ->sortBy('transaction_date')
            ->values();
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /** Net amount: credit positif, debit negatif. */
    public function getNetAmountAttribute(): float
    {
        return (float) ($this->credit_amount - $this->debit_amount);
    }

    /** Apakah transaksi ini adalah penerimaan (credit > 0). */
    public function getIsIncomeAttribute(): bool
    {
        return $this->credit_amount > 0;
    }

    /** Apakah transaksi ini adalah pengeluaran (debit > 0). */
    public function getIsExpenseAttribute(): bool
    {
        return $this->debit_amount > 0;
    }

    /** Apakah transaksi ini sudah dicocokkan. */
    public function getIsMatchedAttribute(): bool
    {
        return $this->reconciliation_status === 'matched';
    }
}
