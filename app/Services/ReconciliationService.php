<?php

namespace App\Services;

use App\Models\BankReconciliationItem;
use App\Models\BankStatement;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use App\Models\UnifiedTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ReconciliationService
{
    // Confidence thresholds
    const EXACT_MATCH_CONFIDENCE  = 100;
    const HIGH_CONFIDENCE         = 85;
    const MEDIUM_CONFIDENCE       = 70;
    const LOW_CONFIDENCE          = 50;

    // -------------------------------------------------------------------------
    // PUBLIC API
    // -------------------------------------------------------------------------

    /**
     * Jalankan algoritma pencocokan antara transaksi sistem dan mutasi bank.
     *
     * @param  bool  $saveMatches  Jika true, hasil match disimpan ke DB.
     *                             Gunakan false untuk preview tanpa menulis ke DB.
     */
    public function reconcile(
        int $paymentMethodId,
        ?string $startDate = null,
        ?string $endDate   = null,
        bool $saveMatches  = false          // default false — caller harus eksplisit meminta save
    ): array {
        $appTransactions = UnifiedTransaction::getForPaymentMethod($paymentMethodId, $startDate, $endDate);
        $bankItems       = $this->getBankItemsForPaymentMethod($paymentMethodId, $startDate, $endDate);

        $results = [
            'matched'       => [],
            'unmatched_app' => [],
            'unmatched_bank' => [],
            'disputed'      => [],
            'statistics'    => [
                'total_app_transactions' => $appTransactions->count(),
                'total_bank_items'       => $bankItems->count(),
                'matched_count'          => 0,
                'unmatched_app_count'    => 0,
                'unmatched_bank_count'   => 0,
                'disputed_count'         => 0,
            ],
        ];

        $matchedBankIds = [];
        $matchedAppKeys = [];

        // ── Phase 1: Exact match (tanggal sama + nominal sama persis) ──────────
        foreach ($appTransactions as $appTx) {
            $exactMatch = $this->findExactMatch($appTx, $bankItems, $matchedBankIds);

            if ($exactMatch) {
                Log::info('EXACT_MATCH_FOUND', [
                    'app'  => $appTx->description,
                    'bank' => $exactMatch->description,
                ]);

                if ($saveMatches) {
                    $this->saveMatch($appTx, $exactMatch, self::EXACT_MATCH_CONFIDENCE, ['date', 'amount']);
                }

                $results['matched'][] = [
                    'app_transaction' => $appTx,
                    'bank_item'       => $exactMatch,
                    'confidence'      => self::EXACT_MATCH_CONFIDENCE,
                    'match_type'      => 'exact',
                    'match_criteria'  => ['date', 'amount'],
                ];

                $matchedBankIds[] = $exactMatch->id;
                $matchedAppKeys[] = $this->txKey($appTx);
            }
        }

        // ── Phase 2: Fuzzy match (toleransi tanggal ≤3 hari + nominal ≤2%) ────
        foreach ($appTransactions as $appTx) {
            if (in_array($this->txKey($appTx), $matchedAppKeys)) {
                continue;
            }

            $fuzzy = $this->findFuzzyMatch($appTx, $bankItems, $matchedBankIds);

            if ($fuzzy['item'] && $fuzzy['confidence'] >= self::LOW_CONFIDENCE) {
                if ($saveMatches) {
                    $this->saveMatch($appTx, $fuzzy['item'], $fuzzy['confidence'], $fuzzy['criteria']);
                }

                $results['matched'][] = [
                    'app_transaction' => $appTx,
                    'bank_item'       => $fuzzy['item'],
                    'confidence'      => $fuzzy['confidence'],
                    'match_type'      => 'fuzzy',
                    'match_criteria'  => $fuzzy['criteria'],
                ];

                $matchedBankIds[] = $fuzzy['item']->id;
                $matchedAppKeys[] = $this->txKey($appTx);
            }
        }

        // ── Kumpulkan unmatched ────────────────────────────────────────────────
        foreach ($appTransactions as $appTx) {
            if (! in_array($this->txKey($appTx), $matchedAppKeys)) {
                $results['unmatched_app'][] = $appTx;
            }
        }

        foreach ($bankItems as $bankItem) {
            if (! in_array($bankItem->id, $matchedBankIds)) {
                $results['unmatched_bank'][] = $bankItem;
            }
        }

        // ── Statistik ─────────────────────────────────────────────────────────
        $total = $results['statistics']['total_app_transactions'];

        $results['statistics']['matched_count']       = count($results['matched']);
        $results['statistics']['unmatched_app_count'] = count($results['unmatched_app']);
        $results['statistics']['unmatched_bank_count'] = count($results['unmatched_bank']);
        $results['statistics']['match_percentage']    = $total > 0
            ? round((count($results['matched']) / $total) * 100, 1)
            : 0;
        $results['statistics']['total_app_debit']    = $appTransactions->sum('debit_amount');
        $results['statistics']['total_app_credit']   = $appTransactions->sum('credit_amount');
        $results['statistics']['total_bank_debit']   = $bankItems->sum('debit');
        $results['statistics']['total_bank_credit']  = $bankItems->sum('credit');

        return $results;
    }

    /**
     * Baca hasil rekonsiliasi yang sudah tersimpan di DB.
     * Digunakan untuk menampilkan halaman rekonsiliasi — tidak menulis ke DB.
     */
    public function getStoredMatches(
        int $paymentMethodId,
        ?string $startDate = null,
        ?string $endDate   = null
    ): array {
        $bankItems = $this->getBankItemsForPaymentMethod($paymentMethodId, $startDate, $endDate);

        // Baca semua transaksi dari 5 tabel sumber (dengan status asli dari DB)
        $allAppTransactions = $this->buildStoredTransactions($paymentMethodId, $startDate, $endDate);

        $matchedAppTxs    = $allAppTransactions->filter(fn (UnifiedTransaction $tx) => $tx->reconciliation_status === 'matched'
            && ! empty($tx->matched_bank_item_id));
        $unmatchedAppTxs  = $allAppTransactions->filter(fn (UnifiedTransaction $tx) => $tx->reconciliation_status !== 'matched');

        $matchedBankIds   = $matchedAppTxs->pluck('matched_bank_item_id')->filter()->unique()->values();
        $matchedBankItems = $bankItems->whereIn('id', $matchedBankIds);
        $unmatchedBankItems = $bankItems->whereNotIn('id', $matchedBankIds);

        // Bangun array matched dengan pasangan app ↔ bank
        $matches = [];
        foreach ($matchedAppTxs as $appTx) {
            $bankItem = $matchedBankItems->firstWhere('id', $appTx->matched_bank_item_id);
            if ($bankItem) {
                $matches[] = [
                    'app_transaction' => $appTx,
                    'bank_item'       => $bankItem,
                    'confidence'      => $appTx->match_confidence ?? self::EXACT_MATCH_CONFIDENCE,
                    'match_type'      => 'stored',
                    'match_criteria'  => ['database'],
                ];
            }
        }

        $total = $allAppTransactions->count();

        return [
            'matched'       => collect($matches),
            'unmatched_app' => $unmatchedAppTxs,
            'unmatched_bank' => $unmatchedBankItems,
            'disputed'      => collect(),
            'statistics'    => [
                'total_app_transactions'  => $total,
                'total_bank_items'        => $bankItems->count(),
                'matched_count'           => count($matches),
                'unmatched_app_count'     => $unmatchedAppTxs->count(),
                'unmatched_bank_count'    => $unmatchedBankItems->count(),
                'disputed_count'          => 0,
                'match_percentage'        => $total > 0
                    ? round((count($matches) / $total) * 100, 1)
                    : 0,
                'avg_confidence'          => count($matches) > 0
                    ? collect($matches)->avg('confidence')
                    : 0,
                'total_app_debit'         => $allAppTransactions->sum('debit_amount'),
                'total_app_credit'        => $allAppTransactions->sum('credit_amount'),
                'total_bank_debit'        => $bankItems->sum('debit'),
                'total_bank_credit'       => $bankItems->sum('credit'),
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // PRIVATE — Matching Logic
    // -------------------------------------------------------------------------

    /**
     * Exact match: tanggal sama DAN nominal identik (tidak ada toleransi desimal
     * karena Rupiah selalu integer).
     */
    private function findExactMatch(
        UnifiedTransaction $appTx,
        Collection $bankItems,
        array $excludeIds
    ): ?BankReconciliationItem {
        return $bankItems->first(function (BankReconciliationItem $bankItem) use ($appTx, $excludeIds) {
            if (in_array($bankItem->id, $excludeIds)) {
                return false;
            }

            $dateMatch = $appTx->transaction_date->isSameDay($bankItem->date);
            if (! $dateMatch) {
                return false;
            }

            if ($appTx->is_income) {
                $appAmount  = (int) ($appTx->credit_amount ?? 0);
                $bankAmount = (int) ($bankItem->credit ?? 0);
            } else {
                $appAmount  = (int) ($appTx->debit_amount ?? 0);
                $bankAmount = (int) ($bankItem->debit ?? 0);
            }

            // Rupiah = integer — tolak jika salah satu nol
            if ($appAmount <= 0 || $bankAmount <= 0) {
                return false;
            }

            // Exact integer match (tidak ada toleransi floating point)
            return $appAmount === $bankAmount;
        });
    }

    /**
     * Fuzzy match: toleransi tanggal ≤3 hari + nominal ≤2% + kemiripan deskripsi.
     */
    private function findFuzzyMatch(
        UnifiedTransaction $appTx,
        Collection $bankItems,
        array $excludeIds
    ): array {
        $best           = null;
        $bestConfidence = 0;
        $bestCriteria   = [];

        foreach ($bankItems as $bankItem) {
            if (in_array($bankItem->id, $excludeIds)) {
                continue;
            }

            $confidence = 0;
            $criteria   = [];

            // ── Poin tanggal (max +40) ────────────────────────────────────────
            $daysDiff = abs($appTx->transaction_date->diffInDays($bankItem->date));

            if ($daysDiff === 0) {
                $confidence += 40;
                $criteria[]  = 'same_date';
            } elseif ($daysDiff === 1) {
                $confidence += 30;
                $criteria[]  = 'close_date';
            } elseif ($daysDiff <= 3) {
                $confidence += 15;
                $criteria[]  = 'near_date';
            } else {
                continue; // Terlalu jauh
            }

            // ── Poin nominal (max +40) ────────────────────────────────────────
            if ($appTx->is_income) {
                $appAmount  = (int) ($appTx->credit_amount ?? 0);
                $bankAmount = (int) ($bankItem->credit ?? 0);
            } else {
                $appAmount  = (int) ($appTx->debit_amount ?? 0);
                $bankAmount = (int) ($bankItem->debit ?? 0);
            }

            // Tolak jika salah satu nol
            if ($appAmount <= 0 || $bankAmount <= 0) {
                continue;
            }

            $amountDiff = abs($appAmount - $bankAmount);

            if ($amountDiff === 0) {
                $confidence += 40;
                $criteria[]  = 'exact_amount';
            } elseif ($appAmount > 0 && ($amountDiff / $appAmount) <= 0.02) {
                // Toleransi 2% — untuk kasus pembulatan antar sistem
                $confidence += 25;
                $criteria[]  = 'close_amount';
            } else {
                continue; // Selisih terlalu besar
            }

            // ── Poin deskripsi (max +20) ──────────────────────────────────────
            $similarity = $this->descriptionSimilarity(
                $appTx->description ?? '',
                $bankItem->description ?? ''
            );

            if ($similarity > 0.8) {
                $confidence += 20;
                $criteria[]  = 'high_desc_similarity';
            } elseif ($similarity > 0.6) {
                $confidence += 10;
                $criteria[]  = 'medium_desc_similarity';
            } elseif ($similarity > 0.4) {
                $confidence += 5;
                $criteria[]  = 'low_desc_similarity';
            }

            if ($confidence > $bestConfidence) {
                $bestConfidence = $confidence;
                $best           = $bankItem;
                $bestCriteria   = $criteria;
            }
        }

        return ['item' => $best, 'confidence' => $bestConfidence, 'criteria' => $bestCriteria];
    }

    // -------------------------------------------------------------------------
    // PRIVATE — Data Access
    // -------------------------------------------------------------------------

    /**
     * Ambil BankReconciliationItem untuk PaymentMethod dan rentang tanggal tertentu.
     */
    private function getBankItemsForPaymentMethod(
        int $paymentMethodId,
        ?string $startDate,
        ?string $endDate
    ): Collection {
        $statementIds = BankStatement::where('payment_method_id', $paymentMethodId)
            ->when($startDate && $endDate, fn (Builder $q) => $q->where(function (Builder $q) use ($startDate, $endDate) {
                $q->where('period_start', '<=', $endDate)
                  ->where('period_end',   '>=', $startDate);
            }))
            ->when($startDate && ! $endDate, fn (Builder $q) => $q->where('period_end', '>=', $startDate))
            ->when(! $startDate && $endDate, fn (Builder $q) => $q->where('period_start', '<=', $endDate))
            ->pluck('id');

        return BankReconciliationItem::whereIn('bank_reconciliation_id', $statementIds)
            ->when($startDate, fn (Builder $q) => $q->where('date', '>=', $startDate))
            ->when($endDate,   fn (Builder $q) => $q->where('date', '<=', $endDate))
            ->orderBy('date')
            ->get();
    }

    /**
     * Bangun UnifiedTransaction langsung dari tabel sumber dengan status asli.
     * Digunakan oleh getStoredMatches() — konsisten dengan UnifiedTransaction::getForPaymentMethod().
     */
    private function buildStoredTransactions(
        int $paymentMethodId,
        ?string $startDate,
        ?string $endDate
    ): Collection {
        $all = collect();

        $sources = [
            [
                'model'        => DataPembayaran::class,
                'date_field'   => 'tgl_bayar',
                'amount_field' => 'nominal',
                'is_credit'    => true,
                'source_type'  => UnifiedTransaction::SOURCE_WEDDING_PAYMENT,
                'source_table' => 'data_pembayarans',
                'desc_field'   => 'keterangan',
            ],
            [
                'model'        => PendapatanLain::class,
                'date_field'   => 'tgl_bayar',
                'amount_field' => 'nominal',
                'is_credit'    => true,
                'source_type'  => UnifiedTransaction::SOURCE_OTHER_INCOME,
                'source_table' => 'pendapatan_lains',
                'desc_field'   => 'keterangan',
            ],
            [
                'model'        => Expense::class,
                'date_field'   => 'date_expense',
                'amount_field' => 'amount',
                'is_credit'    => false,
                'source_type'  => UnifiedTransaction::SOURCE_WEDDING_EXPENSE,  // konsisten dengan UnifiedTransaction
                'source_table' => 'expenses',
                'desc_field'   => 'note',
            ],
            [
                'model'        => ExpenseOps::class,
                'date_field'   => 'date_expense',
                'amount_field' => 'amount',
                'is_credit'    => false,
                'source_type'  => UnifiedTransaction::SOURCE_OPERATIONAL_EXPENSE,
                'source_table' => 'expense_ops',
                'desc_field'   => 'name',
            ],
            [
                'model'        => PengeluaranLain::class,
                'date_field'   => 'date_expense',
                'amount_field' => 'amount',
                'is_credit'    => false,
                'source_type'  => UnifiedTransaction::SOURCE_OTHER_EXPENSE,
                'source_table' => 'pengeluaran_lains',
                'desc_field'   => 'name',
            ],
        ];

        foreach ($sources as $cfg) {
            if (! class_exists($cfg['model'])) {
                continue;
            }

            $records = $cfg['model']::where('payment_method_id', $paymentMethodId)
                ->when($startDate, fn (Builder $q) => $q->where($cfg['date_field'], '>=', $startDate))
                ->when($endDate,   fn (Builder $q) => $q->where($cfg['date_field'], '<=', $endDate))
                ->get();

            foreach ($records as $record) {
                $amount = $record->{$cfg['amount_field']} ?? 0;
                $all->push(new UnifiedTransaction([
                    'payment_method_id'     => $record->payment_method_id,
                    'transaction_date'      => Carbon::parse($record->{$cfg['date_field']}),
                    'description'           => $record->{$cfg['desc_field']} ?? $cfg['source_type'],
                    'debit_amount'          => $cfg['is_credit'] ? 0 : $amount,
                    'credit_amount'         => $cfg['is_credit'] ? $amount : 0,
                    'source_type'           => $cfg['source_type'],
                    'source_id'             => $record->id,
                    'source_table'          => $cfg['source_table'],
                    'reconciliation_status' => $record->reconciliation_status ?? 'unmatched',
                    'matched_bank_item_id'  => $record->matched_bank_item_id ?? null,
                    'match_confidence'      => $record->match_confidence ?? null,
                ]));
            }
        }

        return $all->sortBy('transaction_date')->values();
    }

    // -------------------------------------------------------------------------
    // PRIVATE — Helpers
    // -------------------------------------------------------------------------

    /**
     * Simpan hasil pencocokan ke record sumber (DataPembayaran, Expense, dll.).
     * Dipanggil ketika $saveMatches = true, atau langsung dari ReconciliationController.
     */
    public function saveMatch(
        UnifiedTransaction $appTx,
        BankReconciliationItem $bankItem,
        int $confidence,
        array $criteria
    ): void {
        $model = $this->resolveSourceModel($appTx);

        if (! $model) {
            Log::warning('ReconciliationService: source model not found', [
                'source_table' => $appTx->source_table,
                'source_id'    => $appTx->source_id,
            ]);
            return;
        }

        $model->update([
            'reconciliation_status' => 'matched',
            'matched_bank_item_id'  => $bankItem->id,
            'match_confidence'      => $confidence,
            'reconciliation_notes'  => 'Auto-matched: ' . implode(', ', $criteria),
        ]);
    }

    /**
     * Resolve model sumber dari source_table dan source_id.
     */
    private function resolveSourceModel(UnifiedTransaction $appTx): ?object
    {
        $map = [
            'data_pembayarans'  => DataPembayaran::class,
            'pendapatan_lains'  => PendapatanLain::class,
            'expenses'          => Expense::class,
            'expense_ops'       => ExpenseOps::class,
            'pengeluaran_lains' => PengeluaranLain::class,
        ];

        $class = $map[$appTx->source_table] ?? null;

        return $class ? $class::find($appTx->source_id) : null;
    }

    /** Unique key untuk satu app transaction (source_table + source_id). */
    private function txKey(UnifiedTransaction $appTx): string
    {
        return $appTx->source_table . '_' . $appTx->source_id;
    }

    /** Kemiripan dua string menggunakan Levenshtein distance (0.0 – 1.0). */
    private function descriptionSimilarity(string $a, string $b): float
    {
        $a = strtolower(trim($a));
        $b = strtolower(trim($b));

        if ($a === '' || $b === '') {
            return 0.0;
        }

        $maxLen = max(strlen($a), strlen($b));

        return $maxLen > 0 ? 1 - (levenshtein($a, $b) / $maxLen) : 1.0;
    }
}
