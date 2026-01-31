<?php

namespace App\Http\Controllers;

use App\Models\ExpenseOps;
use App\Models\Order;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LaporanKeuanganController extends Controller
{
    public function downloadPdf(Request $request)
    {
        $startDate = $request->query('startDate', now()->startOfMonth()->toDateString());
        $endDate = $request->query('endDate', now()->endOfMonth()->toDateString());

        // Query orders using All Event dates (Lamaran, Akad, Reception)
        $query = Order::with(['prospect', 'dataPembayaran', 'expenses'])
            ->whereHas('prospect', function ($prospectQuery) use ($startDate, $endDate) {
                $prospectQuery->where(function ($dateQuery) use ($startDate, $endDate) {
                    // Filter by Lamaran Date
                    $dateQuery->whereBetween('date_lamaran', [$startDate, $endDate])
                        // OR Filter by Akad Date
                        ->orWhereBetween('date_akad', [$startDate, $endDate])
                        // OR Filter by Reception Date
                        ->orWhereBetween('date_resepsi', [$startDate, $endDate]);
                });
            });

        $orders = $query->get();

        // --- Sanitize Potential UTF-8 Issues ---
        foreach ($orders as $order) {
            if ($order->prospect && ! mb_check_encoding($order->prospect->name_event ?? '', 'UTF-8')) {
                Log::warning('Malformed UTF-8 in prospect->name_event for Order ID: '.$order->id);
                $order->prospect->name_event = iconv('UTF-8', 'UTF-8//IGNORE', $order->prospect->name_event ?? '');
            }
        }

        // Calculate totals for profit/loss report
        $totalPaymentsReceived = $orders->sum(function ($order) {
            return $order->dataPembayaran->sum('nominal');
        });
        $totalOrderValue = $orders->sum('grand_total');
        $totalActualExpenses = $orders->sum(function ($order) {
            return $order->expenses->sum('amount');
        });

        // Get additional expenses data (ExpenseOps and PengeluaranLain)
        $expenseOps = ExpenseOps::with('vendor')->whereBetween('date_expense', [$startDate, $endDate])
            ->orderBy('date_expense', 'desc')
            ->get();

        $pengeluaranLain = PengeluaranLain::with('vendor')->whereBetween('date_expense', [$startDate, $endDate])
            ->orderBy('date_expense', 'desc')
            ->get();

        // Get pendapatan lain data
        $pendapatanLain = PendapatanLain::with('vendor')->whereBetween('tgl_bayar', [$startDate, $endDate])
            ->orderBy('tgl_bayar', 'desc')
            ->get();

        $totalExpenseOps = $expenseOps->sum('amount');
        $totalPengeluaranLain = $pengeluaranLain->sum('amount');
        $totalPendapatanLain = $pendapatanLain->sum('nominal');

        // Net profit calculation (grand_total - actual expenses)
        $netProfitCalculation = $totalOrderValue - $totalActualExpenses;

        // Prepare data for the PDF view
        $reportData = [
            'orders' => $orders,
            'totalIncome' => $totalPaymentsReceived,
            'totalExpenses' => $totalOrderValue, // Grand Total column
            'sumAllOrdersPengeluaran' => $totalActualExpenses,
            'netProfit' => $netProfitCalculation,
            // Additional expenses data
            'expenseOps' => $expenseOps,
            'pengeluaranLain' => $pengeluaranLain,
            'pendapatanLain' => $pendapatanLain,
            'totalExpenseOps' => $totalExpenseOps,
            'totalPengeluaranLain' => $totalPengeluaranLain,
            'totalPendapatanLain' => $totalPendapatanLain,
            'filterStartDate' => $startDate,
            'filterEndDate' => $endDate,
            'generatedDate' => now()->format('d M Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.profit_loss_report', $reportData);
        
        return $pdf->download('laporan_laba_rugi_' . now()->format('YmdHis') . '.pdf');
    }
}
