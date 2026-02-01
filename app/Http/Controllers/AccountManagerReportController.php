<?php

namespace App\Http\Controllers;

use App\Models\AccountManagerTarget;
use App\Models\LeaveRequest;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class AccountManagerReportController extends Controller
{
    /**
     * Download HTML Report for Account Manager
     */
    public function downloadHtmlReport(Request $request)
    {
        $userId = $request->input('userId');
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        if (!$userId || !$year || !$month) {
            abort(400, 'Missing required parameters');
        }

        try {
            // Get Account Manager user data
            $accountManager = User::with(['roles'])->find($userId);

            if (! $accountManager || ! $accountManager->hasRole('Account Manager')) {
                return response()->make('Account Manager tidak ditemukan atau tidak memiliki role yang sesuai.', 404);
            }

            // Authorization check
            $currentUser = Auth::user();
            $isSuperAdmin = $currentUser && $currentUser->roles->where('name', 'super_admin')->count() > 0;

            if (! $isSuperAdmin && $userId != $currentUser->id) {
                abort(403, 'Anda tidak memiliki akses untuk melihat report ini.');
            }

            // Get target data for the period
            $target = AccountManagerTarget::where('user_id', $userId)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            // Get orders data for the period
            $orders = Order::where('user_id', $userId)
                ->whereNotNull('closing_date')
                ->whereYear('closing_date', $year)
                ->whereMonth('closing_date', $month)
                ->with(['prospect'])
                ->get();

            // Calculate sales statistics
            $totalRevenue = $orders->sum('total_price');
            $totalOrders = $orders->count();
            $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

            // Get payroll data
            $payrollData = null;
            if (class_exists(Payroll::class)) {
                $payrollData = Payroll::where('user_id', $userId)
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->first();
            }

            // Get leave data
            $leaveData = collect();
            if (class_exists(LeaveRequest::class)) {
                $leaveData = LeaveRequest::where('user_id', $userId)
                    ->where(function ($query) use ($year, $month) {
                        $query->whereYear('start_date', $year)
                            ->whereMonth('start_date', $month);
                    })
                    ->orWhere(function ($query) use ($year, $month) {
                        $query->whereYear('end_date', $year)
                            ->whereMonth('end_date', $month);
                    })
                    ->with('leaveType')
                    ->get();
            }

            // Generate the report view
            return response()->streamDownload(function () use ($accountManager, $target, $orders, $payrollData, $leaveData, $year, $month, $totalRevenue, $totalOrders, $averageOrderValue) {
                echo view('reports.account-manager-report', [
                    'accountManager' => $accountManager,
                    'target' => $target,
                    'orders' => $orders,
                    'payrollData' => $payrollData,
                    'leaveData' => $leaveData,
                    'year' => $year,
                    'month' => $month,
                    'monthName' => Carbon::create()->month($month)->format('F'),
                    'totalRevenue' => $totalRevenue,
                    'totalOrders' => $totalOrders,
                    'averageOrderValue' => $averageOrderValue,
                    'achievementPercentage' => $target ? ($target->target_amount > 0 ? ($totalRevenue / $target->target_amount) * 100 : 0) : 0,
                ])->render();
            }, "AM_Report_{$accountManager->name}_{$year}_{$month}.html", [
                'Content-Type' => 'text/html',
            ]);

        } catch (Exception $e) {
            return response()->make('Terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Download PDF Report for Account Manager
     */
    public function downloadPdfReport(Request $request)
    {
        $userId = $request->input('userId');
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        if (!$userId || !$year || !$month) {
            abort(400, 'Missing required parameters');
        }

        try {
            // Get Account Manager user data
            $accountManager = User::with(['roles'])->find($userId);

            if (! $accountManager || ! $accountManager->hasRole('Account Manager')) {
                return response()->make('Account Manager tidak ditemukan atau tidak memiliki role yang sesuai.', 404);
            }

            // Authorization check
            $currentUser = Auth::user();
            $isSuperAdmin = $currentUser && $currentUser->roles->where('name', 'super_admin')->count() > 0;

            if (! $isSuperAdmin && $userId != $currentUser->id) {
                abort(403, 'Anda tidak memiliki akses untuk melihat report ini.');
            }

            // Get target data for the period
            $target = AccountManagerTarget::where('user_id', $userId)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            // Get orders data for the period
            $orders = Order::where('user_id', $userId)
                ->whereNotNull('closing_date')
                ->whereYear('closing_date', $year)
                ->whereMonth('closing_date', $month)
                ->with(['prospect'])
                ->get();

            // Calculate sales statistics
            $totalRevenue = $orders->sum('total_price');
            $totalOrders = $orders->count();
            $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

            // Get payroll data
            $payrollData = null;
            if (class_exists(Payroll::class)) {
                $payrollData = Payroll::where('user_id', $userId)
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->first();
            }

            // Get leave data
            $leaveData = collect();
            if (class_exists(LeaveRequest::class)) {
                $leaveData = LeaveRequest::where('user_id', $userId)
                    ->where(function ($query) use ($year, $month) {
                        $query->whereYear('start_date', $year)
                            ->whereMonth('start_date', $month);
                    })
                    ->orWhere(function ($query) use ($year, $month) {
                        $query->whereYear('end_date', $year)
                            ->whereMonth('end_date', $month);
                    })
                    ->with('leaveType')
                    ->get();
            }

            // Get Yearly Performance Data
            $currentYear = (int) date('Y');
            $currentMonth = (int) date('n');
            
            // Detail Tahun Berjalan (Current Year)
            $currentYearData = $this->getYearlyPerformanceData($userId, $currentYear, $currentMonth);
            
            // Detail Tahun Sebelumnya (Previous Year)
            $previousYearData = $this->getYearlyPerformanceData($userId, $currentYear - 1, 12);

            $pdf = Pdf::loadView('reports.account-manager-report-new', [
                'accountManager' => $accountManager,
                'target' => $target,
                'orders' => $orders,
                'payrollData' => $payrollData,
                'leaveData' => $leaveData,
                'year' => $year,
                'month' => $month,
                'monthName' => Carbon::create()->month($month)->format('F'),
                'totalRevenue' => $totalRevenue,
                'totalOrders' => $totalOrders,
                'averageOrderValue' => $averageOrderValue,
                'achievementPercentage' => $target ? ($target->target_amount > 0 ? ($totalRevenue / $target->target_amount) * 100 : 0) : 0,
                'currentYearData' => $currentYearData,
                'previousYearData' => $previousYearData,
                'currentYear' => $currentYear,
                'currentMonth' => $currentMonth,
            ]);

            $pdf->setPaper('a4', 'portrait');

            return $pdf->download("AM_Report_{$accountManager->name}_{$year}_{$month}.pdf");

        } catch (Exception $e) {
            return response()->make('Terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Stream PDF Report for Account Manager (Preview)
     */
    public function streamPdfReport(Request $request)
    {
        $userId = $request->input('userId');
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        if (!$userId || !$year || !$month) {
            abort(400, 'Missing required parameters');
        }

        try {
            // Get Account Manager user data
            $accountManager = User::with(['roles'])->find($userId);

            if (! $accountManager || ! $accountManager->hasRole('Account Manager')) {
                return response()->make('Account Manager tidak ditemukan atau tidak memiliki role yang sesuai.', 404);
            }

            // Authorization check
            $currentUser = Auth::user();
            $isSuperAdmin = $currentUser && $currentUser->roles->where('name', 'super_admin')->count() > 0;

            if (! $isSuperAdmin && $userId != $currentUser->id) {
                abort(403, 'Anda tidak memiliki akses untuk melihat report ini.');
            }

            // Get target data for the period
            $target = AccountManagerTarget::where('user_id', $userId)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            // Get orders data for the period
            $orders = Order::where('user_id', $userId)
                ->whereNotNull('closing_date')
                ->whereYear('closing_date', $year)
                ->whereMonth('closing_date', $month)
                ->with(['prospect'])
                ->get();

            // Calculate sales statistics
            $totalRevenue = $orders->sum('total_price');
            $totalOrders = $orders->count();
            $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

            // Get payroll data
            $payrollData = null;
            if (class_exists(Payroll::class)) {
                $payrollData = Payroll::where('user_id', $userId)
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->first();
            }

            // Get leave data
            $leaveData = collect();
            if (class_exists(LeaveRequest::class)) {
                $leaveData = LeaveRequest::where('user_id', $userId)
                    ->where(function ($query) use ($year, $month) {
                        $query->whereYear('start_date', $year)
                            ->whereMonth('start_date', $month);
                    })
                    ->orWhere(function ($query) use ($year, $month) {
                        $query->whereYear('end_date', $year)
                            ->whereMonth('end_date', $month);
                    })
                    ->with('leaveType')
                    ->get();
            }

            // Get Yearly Performance Data
            $currentYear = (int) date('Y');
            $currentMonth = (int) date('n');
            
            // Detail Tahun Berjalan (Current Year)
            $currentYearData = $this->getYearlyPerformanceData($userId, $currentYear, $currentMonth);
            
            // Detail Tahun Sebelumnya (Previous Year)
            $previousYearData = $this->getYearlyPerformanceData($userId, $currentYear - 1, 12);

            $pdf = Pdf::loadView('reports.account-manager-report-new', [
                'accountManager' => $accountManager,
                'target' => $target,
                'orders' => $orders,
                'payrollData' => $payrollData,
                'leaveData' => $leaveData,
                'year' => $year,
                'month' => $month,
                'monthName' => Carbon::create()->month($month)->format('F'),
                'totalRevenue' => $totalRevenue,
                'totalOrders' => $totalOrders,
                'averageOrderValue' => $averageOrderValue,
                'achievementPercentage' => $target ? ($target->target_amount > 0 ? ($totalRevenue / $target->target_amount) * 100 : 0) : 0,
                'currentYearData' => $currentYearData,
                'previousYearData' => $previousYearData,
                'currentYear' => $currentYear,
                'currentMonth' => $currentMonth,
            ]);

            $pdf->setPaper('a4', 'portrait');

            return $pdf->stream("AM_Report_{$accountManager->name}_{$year}_{$month}.pdf");

        } catch (Exception $e) {
            return response()->make('Terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Show Report Preview for Account Manager
     */
    public function showReport(Request $request)
    {
        $userId = $request->input('userId');
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        if (!$userId || !$year || !$month) {
            abort(400, 'Missing required parameters');
        }

        try {
            // Get Account Manager user data
            $accountManager = User::with(['roles'])->find($userId);

            if (! $accountManager || ! $accountManager->hasRole('Account Manager')) {
                return response()->make('Account Manager tidak ditemukan atau tidak memiliki role yang sesuai.', 404);
            }

            // Authorization check
            $currentUser = Auth::user();
            $isSuperAdmin = $currentUser && $currentUser->roles->where('name', 'super_admin')->count() > 0;

            if (! $isSuperAdmin && $userId != $currentUser->id) {
                abort(403, 'Anda tidak memiliki akses untuk melihat report ini.');
            }

            // Get target data for the period
            $target = AccountManagerTarget::where('user_id', $userId)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            // Get orders data for the period
            $orders = Order::where('user_id', $userId)
                ->whereNotNull('closing_date')
                ->whereYear('closing_date', $year)
                ->whereMonth('closing_date', $month)
                ->with(['prospect'])
                ->get();

            // Calculate sales statistics
            $totalRevenue = $orders->sum('total_price');
            $totalOrders = $orders->count();
            $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
            $achievementPercentage = $target ? ($target->target_amount > 0 ? ($totalRevenue / $target->target_amount) * 100 : 0) : 0;

            // Get payroll data
            $payrollData = null;
            if (class_exists(Payroll::class)) {
                $payrollData = Payroll::where('user_id', $userId)
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->first();
            }

            // Get leave data
            $leaveData = collect();
            if (class_exists(LeaveRequest::class)) {
                $leaveData = LeaveRequest::where('user_id', $userId)
                    ->where(function ($query) use ($year, $month) {
                        $query->whereYear('start_date', $year)
                        ->whereMonth('start_date', $month);
                    })
                    ->orWhere(function ($query) use ($year, $month) {
                        $query->whereYear('end_date', $year)
                        ->whereMonth('end_date', $month);
                    })
                    ->with('leaveType')
                    ->get();
            }

            // Get Yearly Performance Data
            $currentYear = (int) date('Y');
            $currentMonth = (int) date('n');
            
            // Detail Tahun Berjalan (Current Year)
            $currentYearData = $this->getYearlyPerformanceData($userId, $currentYear, $currentMonth);
            
            // Detail Tahun Sebelumnya (Previous Year)
            // For previous year, we show full 12 months
            $previousYearData = $this->getYearlyPerformanceData($userId, $currentYear - 1, 12);

            $reportData = [
                'target' => $target,
                'orders' => $orders,
                'payrollData' => $payrollData,
                'leaveData' => $leaveData,
                'totalRevenue' => $totalRevenue,
                'totalOrders' => $totalOrders,
                'averageOrderValue' => $averageOrderValue,
                'achievementPercentage' => $achievementPercentage,
            ];

            return view('account-manager-show', [
                'accountManager' => $accountManager,
                'year' => $year,
                'month' => $month,
                'monthName' => Carbon::create()->month($month)->format('F'),
                'reportData' => $reportData,
                'currentYearData' => $currentYearData,
                'previousYearData' => $previousYearData,
                'currentYear' => $currentYear,
                'currentMonth' => $currentMonth,
            ]);

        } catch (Exception $e) {
            return response()->make('Terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper to get yearly performance data
     */
    private function getYearlyPerformanceData($userId, $year, $limitMonth = 12)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $fixedTargetAmount = 1000000000; // Target tetap
        $yearlyData = [];

        // Pre-fetch all orders for the year to avoid N+1 queries
        $yearOrders = Order::where('user_id', $userId)
            ->whereNotNull('closing_date')
            ->whereYear('closing_date', $year)
            ->get();

        for ($month = 1; $month <= 12; $month++) {
            if ($month <= $limitMonth) {
                $monthlyOrders = $yearOrders->filter(function ($order) use ($month) {
                    return Carbon::parse($order->closing_date)->month == $month;
                });

                $monthlyRevenue = $monthlyOrders->sum('total_price') ?? 0;
                $monthlyOrderCount = $monthlyOrders->count();
            } else {
                $monthlyRevenue = 0;
                $monthlyOrderCount = 0;
            }

            $yearlyData[$month] = [
                'name' => $months[$month],
                'orders' => $monthlyOrderCount,
                'revenue' => $monthlyRevenue,
                'target' => $fixedTargetAmount,
                'achievement' => $fixedTargetAmount > 0 ? ($monthlyRevenue / $fixedTargetAmount) * 100 : 0,
            ];
        }

        // Calculate totals
        $totalOrders = 0;
        $totalRevenue = 0;
        $totalTarget = 0;

        for ($month = 1; $month <= $limitMonth; $month++) {
            $totalOrders += $yearlyData[$month]['orders'];
            $totalRevenue += $yearlyData[$month]['revenue'];
            $totalTarget += $yearlyData[$month]['target'];
        }

        return [
            'monthly' => $yearlyData,
            'summary' => [
                'orders' => $totalOrders,
                'revenue' => $totalRevenue,
                'target' => $totalTarget,
                'achievement' => $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0,
            ]
        ];
    }
}