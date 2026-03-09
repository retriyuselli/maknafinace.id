<?php

namespace App\Http\Controllers;

use App\Exports\ReconciliationExport;
use App\Models\BankReconciliationItem;
use App\Models\PaymentMethod;
use App\Services\ReconciliationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReconciliationController extends Controller
{
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
            \Illuminate\Support\Facades\Gate::authorize('view', $paymentMethod);

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
                'user' => \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->name : 'System',
            ])->setPaper('a4', 'landscape');

            $filename = 'Reconciliation_Report_' . str_replace([' ', '/'], '_', $paymentMethod->no_rekening) . '_' . $request->start_date . '.pdf';

            return $pdf->download($filename);

        } catch (Exception $e) {
            return back()->with('error', 'Gagal generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Mark individual transaction as matched
     */
    public function markMatched(Request $request)
    {
        $request->validate([
            'source_id' => 'required|integer',
            'source_table' => 'required|string',
            'bank_item_id' => 'required|integer',
            'confidence' => 'required|numeric',
        ]);

        try {
            // Get bank item
            $bankItem = BankReconciliationItem::findOrFail($request->bank_item_id);
            
            // Authorization check
            \Illuminate\Support\Facades\Gate::authorize('update', $bankItem);

            // Create a mock transaction object for the service
            $mockTransaction = (object) [
                'source_table' => $request->source_table,
                'source_id' => $request->source_id,
            ];

            // Mark as matched
            $this->reconciliationService->markAsMatched(
                $mockTransaction,
                $bankItem,
                $request->confidence,
                ['manual_match']
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil ditandai sebagai cocok',
            ]);

        } catch (Exception $e) {
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
        ]);

        try {
            $paymentMethod = PaymentMethod::findOrFail($request->payment_method_id);
            \Illuminate\Support\Facades\Gate::authorize('view', $paymentMethod);

            $results = $this->reconciliationService->reconcile(
                $request->payment_method_id,
                $request->start_date,
                $request->end_date
            );

            $matchedCount = 0;

            foreach ($results['matched'] as $match) {
                if ($match['confidence'] >= ReconciliationService::HIGH_CONFIDENCE) {
                    $this->reconciliationService->markAsMatched(
                        $match['app_transaction'],
                        $match['bank_item'],
                        $match['confidence'],
                        $match['match_criteria']
                    );
                    $matchedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'matched_count' => $matchedCount,
                'message' => "$matchedCount transaksi berhasil di-match otomatis",
            ]);

        } catch (Exception $e) {
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
            'source_table' => 'required|string',
            'bank_item_id' => 'required|integer',
        ]);

        try {
            // Get bank item and authorize
            $bankItem = BankReconciliationItem::findOrFail($request->bank_item_id);
            \Illuminate\Support\Facades\Gate::authorize('update', $bankItem);

            // Reset reconciliation status in source table
            $table = $request->source_table;

            // Check if the table has reconciliation fields
            if (! Schema::hasColumn($table, 'reconciliation_status')) {
                return response()->json([
                    'success' => false,
                    'message' => "Table {$table} does not have reconciliation_status field",
                ], 400);
            }

            // Reset the source transaction
            $updated = DB::table($table)
                ->where('id', $request->source_id)
                ->update([
                    'reconciliation_status' => 'unmatched',
                    'matched_bank_item_id' => null,
                    'match_confidence' => null,
                    'reconciliation_notes' => 'Manually unmarked at '.now()->format('Y-m-d H:i:s'),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found or already unmatched',
                ], 404);
            }

            // Note: bank_reconciliation_items table doesn't need to be updated
            // as it doesn't track match status - only source tables do

            return response()->json([
                'success' => true,
                'message' => 'Match berhasil dibatalkan',
            ]);

        } catch (Exception $e) {
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
}
