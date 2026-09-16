<?php

namespace App\Http\Controllers;

use App\Models\BankReconciliationItem;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\PaymentMethod;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use App\Services\ReconciliationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ReconciliationController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const SOURCE_MODELS = [
        'data_pembayarans' => DataPembayaran::class,
        'pendapatan_lains' => PendapatanLain::class,
        'expenses' => Expense::class,
        'expense_ops' => ExpenseOps::class,
        'pengeluaran_lains' => PengeluaranLain::class,
    ];

    protected $reconciliationService;

    public function __construct()
    {
        $this->reconciliationService = new ReconciliationService;
    }

    /**
     * Download Reconciliation Report as PDF
     */
    public function downloadPdf(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        try {
            $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);
            Gate::authorize('update', $paymentMethod);

            $results = $this->reconciliationService->reconcile(
                $request->payment_method_id,
                $request->start_date,
                $request->end_date
            );

            // Get matched data with bank items and app transactions
            $matched = $results['matched'];
            $unmatchedApp = $results['unmatched_app'];
            $unmatchedBank = $results['unmatched_bank'];
            $statistics = $results['statistics'];

            $pdf = Pdf::loadView('pdf.reconciliation-report', [
                'paymentMethod' => $paymentMethod,
                'startDate' => $request->start_date,
                'endDate' => $request->end_date,
                'matched' => $matched,
                'unmatchedApp' => $unmatchedApp,
                'unmatchedBank' => $unmatchedBank,
                'statistics' => $statistics,
                'timestamp' => now()->format('d F Y H:i:s'),
                'user' => Auth::check() ? Auth::user()->name : 'System',
            ])->setPaper('a4', 'portrait');

            $filename = 'Reconciliation_Report_'.str_replace([' ', '/'], '_', $paymentMethod->no_rekening).'_'.$request->start_date.'.pdf';

            return $pdf->download($filename);

        } catch (Exception $e) {
            return back()->with('error', 'Gagal generate PDF: '.$e->getMessage());
        }
    }

    /**
     * Mark individual transaction as matched (manual match from UI)
     */
    public function markMatched(Request $request)
    {
        $request->validate([
            'source_id' => 'required|integer',
            'source_table' => 'required|string|in:'.implode(',', array_keys(self::SOURCE_MODELS)),
            'bank_item_id' => 'required|integer',
            'confidence' => 'required|integer|min:0|max:100',
        ]);

        try {
            DB::transaction(function () use ($request): void {
                $bankItem = BankReconciliationItem::query()
                    ->with('bankStatement')
                    ->lockForUpdate()
                    ->findOrFail($request->integer('bank_item_id'));
                Gate::authorize('update', $bankItem);

                $source = $this->authorizedSource($request, $bankItem, true);
                $source->forceFill([
                    'reconciliation_status' => 'matched',
                    'matched_bank_item_id' => $bankItem->id,
                    'match_confidence' => $request->integer('confidence'),
                    'reconciliation_notes' => 'Manually matched at '.now()->format('Y-m-d H:i:s'),
                ])->save();
            }, 3);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil ditandai sebagai cocok',
            ]);

        } catch (Exception $e) {
            if ($e instanceof HttpExceptionInterface
                || $e instanceof AuthorizationException) {
                throw $e;
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menandai transaksi: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto match high confidence transactions
     */
    public function autoMatch(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'threshold' => 'nullable|integer|in:85,90,95,100',
        ]);

        try {
            $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);
            Gate::authorize('view', $paymentMethod);

            $results = $this->reconciliationService->reconcile(
                $request->payment_method_id,
                $request->start_date,
                $request->end_date
            );

            $threshold = (int) ($request->input('threshold') ?? 90);
            $matchedCount = 0;

            DB::transaction(function () use ($results, $threshold, $paymentMethod, &$matchedCount): void {
                foreach ($results['matched'] as $match) {
                    if ($match['confidence'] >= $threshold) {
                        $this->authorizeUnifiedSource($match['app_transaction'], $paymentMethod);
                        $this->reconciliationService->saveMatch(
                            $match['app_transaction'],
                            $match['bank_item'],
                            $match['confidence'],
                            $match['match_criteria']
                        );
                        $matchedCount++;
                    }
                }
            }, 3);

            return response()->json([
                'success' => true,
                'matched_count' => $matchedCount,
                'threshold' => $threshold,
                'message' => "$matchedCount transaksi berhasil di-match otomatis (threshold {$threshold}%+)",
            ]);

        } catch (Exception $e) {
            if ($e instanceof HttpExceptionInterface || $e instanceof AuthorizationException) {
                throw $e;
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan auto match: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unmark matched transaction
     */
    public function unmarkMatched(Request $request)
    {
        $request->validate([
            'source_id' => 'required|integer',
            'source_table' => 'required|string|in:'.implode(',', array_keys(self::SOURCE_MODELS)),
            'bank_item_id' => 'required|integer',
        ]);

        try {
            DB::transaction(function () use ($request): void {
                $bankItem = BankReconciliationItem::query()
                    ->with('bankStatement')
                    ->lockForUpdate()
                    ->findOrFail($request->integer('bank_item_id'));
                Gate::authorize('update', $bankItem);

                $source = $this->authorizedSource($request, $bankItem, true);
                abort_unless((int) $source->matched_bank_item_id === $bankItem->id, 422, 'Transaction is not matched to this bank item.');
                $source->forceFill([
                    'reconciliation_status' => 'unmatched',
                    'matched_bank_item_id' => null,
                    'match_confidence' => null,
                    'reconciliation_notes' => 'Manually unmarked at '.now()->format('Y-m-d H:i:s'),
                ])->save();
            }, 3);

            return response()->json([
                'success' => true,
                'message' => 'Match berhasil dibatalkan',
            ]);

        } catch (Exception $e) {
            if ($e instanceof HttpExceptionInterface
                || $e instanceof AuthorizationException) {
                throw $e;
            }

            Log::error('Unmark failed: '.$e->getMessage(), [
                'source_id' => $request->source_id,
                'source_table' => $request->source_table,
                'bank_item_id' => $request->bank_item_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan match: '.$e->getMessage(),
            ], 500);
        }
    }

    private function authorizedSource(Request $request, BankReconciliationItem $bankItem, bool $forUpdate): Model
    {
        $modelClass = self::SOURCE_MODELS[$request->string('source_table')->toString()];
        $query = $modelClass::query();
        if ($forUpdate) {
            $query->lockForUpdate();
        }

        $source = $query->findOrFail($request->integer('source_id'));
        Gate::authorize('update', $source);

        $paymentMethodId = (int) ($bankItem->bankStatement?->payment_method_id ?? 0);
        abort_unless($paymentMethodId > 0 && (int) $source->payment_method_id === $paymentMethodId, 422, 'Transaction is outside this reconciliation account.');

        return $source;
    }

    private function authorizeUnifiedSource(object $transaction, PaymentMethod $paymentMethod): void
    {
        $modelClass = self::SOURCE_MODELS[(string) ($transaction->source_table ?? '')] ?? null;
        abort_unless($modelClass !== null, 422, 'Unsupported reconciliation source.');

        $source = $modelClass::query()->lockForUpdate()->findOrFail((int) ($transaction->source_id ?? 0));
        Gate::authorize('update', $source);
        abort_unless((int) $source->payment_method_id === (int) $paymentMethod->id, 422, 'Transaction is outside this reconciliation account.');
    }
}
