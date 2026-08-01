<?php

namespace App\Http\Controllers;

use App\Models\AccountManagerTarget;
use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AccountManagerReportController extends Controller
{
    /**
     * Download HTML Report for Account Manager
     */
    public function downloadHtmlReport(Request $request)
    {
        $validated = $request->validate([
            'userId' => ['required', 'integer', 'exists:users,id'],
            'year'   => ['required', 'integer', 'min:2000', 'max:2100'],
            'month'  => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $userId = (int) $validated['userId'];
        $year   = (int) $validated['year'];
        $month  = (int) $validated['month'];

        try {
            $data = $this->fetchReportData($userId, $year, $month, ['prospect', 'items.product']);
            extract($data);

            return response()->streamDownload(function () use ($accountManager, $target, $orders, $payrollData, $leaveData, $year, $month, $totalRevenue, $totalOrders, $averageOrderValue, $achievementPercentage) {
                echo view('reports.account-manager-report', [
                    'accountManager'       => $accountManager,
                    'target'               => $target,
                    'orders'               => $orders,
                    'payrollData'          => $payrollData,
                    'leaveData'            => $leaveData,
                    'year'                 => $year,
                    'month'                => $month,
                    'monthName'            => Carbon::create()->month($month)->format('F'),
                    'totalRevenue'         => $totalRevenue,
                    'totalOrders'          => $totalOrders,
                    'averageOrderValue'    => $averageOrderValue,
                    'achievementPercentage' => $achievementPercentage,
                ])->render();
            }, "AM_Report_{$accountManager->name}_{$year}_{$month}.html", [
                'Content-Type' => 'text/html',
            ]);

        } catch (Exception $e) {
            return response()->make('Terjadi kesalahan: '.$e->getMessage(), 500);
        }
    }

    /**
     * Download PDF Report for Account Manager
     */
    public function downloadPdfReport(Request $request)
    {
        $validated = $request->validate([
            'userId' => ['required', 'integer', 'exists:users,id'],
            'year'   => ['required', 'integer', 'min:2000', 'max:2100'],
            'month'  => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $userId = (int) $validated['userId'];
        $year   = (int) $validated['year'];
        $month  = (int) $validated['month'];

        try {
            $data = $this->fetchReportData($userId, $year, $month);
            extract($data);

            [$currentYearData, $previousYearData, $currentYear, $currentMonth] = $this->fetchYearlyData($userId);
            [$company, $companyName, $companyAddress, $companyEmail, $companyPhone, $logoBase64] = $this->fetchCompanyData();

            $pdf = Pdf::loadView('reports.account-manager-report-dompdf', [
                'accountManager'       => $accountManager,
                'target'               => $target,
                'orders'               => $orders,
                'payrollData'          => $payrollData,
                'leaveData'            => $leaveData,
                'year'                 => $year,
                'month'                => $month,
                'monthName'            => Carbon::create()->month($month)->format('F'),
                'totalRevenue'         => $totalRevenue,
                'totalOrders'          => $totalOrders,
                'averageOrderValue'    => $averageOrderValue,
                'achievementPercentage' => $achievementPercentage,
                'currentYearData'      => $currentYearData,
                'previousYearData'     => $previousYearData,
                'currentYear'          => $currentYear,
                'currentMonth'         => $currentMonth,
                'companyName'          => $companyName,
                'companyAddress'       => $companyAddress,
                'companyEmail'         => $companyEmail,
                'companyPhone'         => $companyPhone,
                'logoBase64'           => $logoBase64,
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'dpi'                     => 96,
                'defaultFont'             => 'DejaVu Sans',
                'isHtml5ParserEnabled'    => true,
                'isRemoteEnabled'         => false,
                'isPhpEnabled'            => false,
                'isFontSubsettingEnabled' => true,
            ]);

            return $pdf->download("AM_Report_{$accountManager->name}_{$year}_{$month}.pdf");

        } catch (Exception $e) {
            return response()->make('Terjadi kesalahan: '.$e->getMessage(), 500);
        }
    }

    /**
     * Stream PDF Report for Account Manager (Preview)
     */
    public function streamPdfReport(Request $request)
    {
        $validated = $request->validate([
            'userId' => ['required', 'integer', 'exists:users,id'],
            'year'   => ['required', 'integer', 'min:2000', 'max:2100'],
            'month'  => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $userId = (int) $validated['userId'];
        $year   = (int) $validated['year'];
        $month  = (int) $validated['month'];

        try {
            $data = $this->fetchReportData($userId, $year, $month);
            extract($data);

            [$currentYearData, $previousYearData, $currentYear, $currentMonth] = $this->fetchYearlyData($userId);
            [$company, $companyName, $companyAddress, $companyEmail, $companyPhone, $logoBase64] = $this->fetchCompanyData();

            $pdf = Pdf::loadView('reports.account-manager-report-dompdf', [
                'accountManager'       => $accountManager,
                'target'               => $target,
                'orders'               => $orders,
                'payrollData'          => $payrollData,
                'leaveData'            => $leaveData,
                'year'                 => $year,
                'month'                => $month,
                'monthName'            => Carbon::create()->month($month)->format('F'),
                'totalRevenue'         => $totalRevenue,
                'totalOrders'          => $totalOrders,
                'averageOrderValue'    => $averageOrderValue,
                'achievementPercentage' => $achievementPercentage,
                'currentYearData'      => $currentYearData,
                'previousYearData'     => $previousYearData,
                'currentYear'          => $currentYear,
                'currentMonth'         => $currentMonth,
                'companyName'          => $companyName,
                'companyAddress'       => $companyAddress,
                'companyEmail'         => $companyEmail,
                'companyPhone'         => $companyPhone,
                'logoBase64'           => $logoBase64,
            ]);

            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'dpi'                     => 96,
                'defaultFont'             => 'DejaVu Sans',
                'isHtml5ParserEnabled'    => true,
                'isRemoteEnabled'         => false,
                'isPhpEnabled'            => false,
                'isFontSubsettingEnabled' => true,
            ]);

            return $pdf->stream("AM_Report_{$accountManager->name}_{$year}_{$month}.pdf");

        } catch (Exception $e) {
            return response()->make('Terjadi kesalahan: '.$e->getMessage(), 500);
        }
    }

    /**
     * Show Report Preview for Account Manager
     */
    public function showReport(Request $request)
    {
        $userId = $request->input('userId');
        $year   = (int) $request->input('year');
        $month  = (int) $request->input('month');

        if (! $userId || ! $year || ! $month) {
            abort(400, 'Missing required parameters');
        }

        try {
            $data = $this->fetchReportData((int) $userId, $year, $month);
            extract($data);

            [$currentYearData, $previousYearData, $currentYear, $currentMonth] = $this->fetchYearlyData((int) $userId);

            return view('account-manager-show', [
                'accountManager'  => $accountManager,
                'year'            => $year,
                'month'           => $month,
                'monthName'       => Carbon::create()->month($month)->format('F'),
                'reportData'      => [
                    'target'               => $target,
                    'orders'               => $orders,
                    'payrollData'          => $payrollData,
                    'leaveData'            => $leaveData,
                    'totalRevenue'         => $totalRevenue,
                    'totalOrders'          => $totalOrders,
                    'averageOrderValue'    => $averageOrderValue,
                    'achievementPercentage' => $achievementPercentage,
                ],
                'currentYearData'  => $currentYearData,
                'previousYearData' => $previousYearData,
                'currentYear'      => $currentYear,
                'currentMonth'     => $currentMonth,
            ]);

        } catch (Exception $e) {
            return response()->make('Terjadi kesalahan: '.$e->getMessage(), 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch all common report data: accountManager, target, orders, stats,
     * payroll, and leave — with authorization check baked in.
     */
    private function fetchReportData(int $userId, int $year, int $month, array $orderWith = ['prospect']): array
    {
        $accountManager = User::with(['roles'])->find($userId);

        if (! $accountManager || ! $accountManager->hasRole('Account Manager')) {
            abort(404, 'Account Manager tidak ditemukan atau tidak memiliki role yang sesuai.');
        }

        // Authorization
        $currentUser   = Auth::user();
        $isSuperAdmin  = $currentUser && $currentUser->roles->where('name', 'super_admin')->count() > 0;

        if (! $isSuperAdmin && $userId != $currentUser->id) {
            abort(403, 'Anda tidak memiliki akses untuk melihat report ini.');
        }

        // Target
        $target = AccountManagerTarget::where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        // Orders
        $orders = Order::where('user_id', $userId)
            ->whereNotNull('closing_date')
            ->whereYear('closing_date', $year)
            ->whereMonth('closing_date', $month)
            ->with($orderWith)
            ->get();

        // Sales statistics
        $totalRevenue         = $orders->sum('total_price');
        $totalOrders          = $orders->count();
        $averageOrderValue    = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        $achievementPercentage = $target
            ? ($target->target_amount > 0 ? ($totalRevenue / $target->target_amount) * 100 : 0)
            : 0;

        // Payroll
        $payrollData = null;
        if (class_exists(Payroll::class)) {
            $payrollData = Payroll::where('user_id', $userId)
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->first();
        }

        // Leave
        $leaveData = collect();
        if (class_exists(LeaveRequest::class)) {
            $leaveData = LeaveRequest::where('user_id', $userId)
                ->where(function ($query) use ($year, $month) {
                    $query->whereYear('start_date', $year)->whereMonth('start_date', $month);
                })
                ->orWhere(function ($query) use ($year, $month) {
                    $query->whereYear('end_date', $year)->whereMonth('end_date', $month);
                })
                ->with('leaveType')
                ->get();
        }

        return compact(
            'accountManager', 'target', 'orders',
            'totalRevenue', 'totalOrders', 'averageOrderValue', 'achievementPercentage',
            'payrollData', 'leaveData'
        );
    }

    /**
     * Fetch current-year and previous-year performance data.
     * Returns [$currentYearData, $previousYearData, $currentYear, $currentMonth].
     */
    private function fetchYearlyData(int $userId): array
    {
        $currentYear  = (int) date('Y');
        $currentMonth = (int) date('n');

        $currentYearData  = $this->getYearlyPerformanceData($userId, $currentYear, $currentMonth);
        $previousYearData = $this->getYearlyPerformanceData($userId, $currentYear - 1, 12);

        return [$currentYearData, $previousYearData, $currentYear, $currentMonth];
    }

    /**
     * Fetch company info and build a cached base64 logo string.
     * Returns [$company, $companyName, $companyAddress, $companyEmail, $companyPhone, $logoBase64].
     */
    private function fetchCompanyData(): array
    {
        $company = null;
        if (Schema::hasTable('companies')) {
            $company = Company::query()->first();
        }

        $companyName    = $company?->company_name;
        $companyAddress = $company?->address;
        $companyEmail   = $company?->email;
        $companyPhone   = $company?->phone;

        $logoCacheKey = 'am_report:logo:'.
            ($company?->id ?? 'none').':'.
            ($company?->updated_at?->timestamp ?? '0').':'.
            md5((string) ($company?->logo_url ?? ''));

        $logoBase64 = Cache::remember($logoCacheKey, 3600, function () use ($company): string {
            $logoPath = $company && $company->logo_url
                ? Storage::disk('public')->path($company->logo_url)
                : public_path('images/logomki.png');

            if (! is_string($logoPath) || ! file_exists($logoPath)) {
                return '';
            }

            $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoData = @file_get_contents($logoPath);
            if (! is_string($logoData) || $logoData === '') {
                return '';
            }

            return 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
        });

        return [$company, $companyName, $companyAddress, $companyEmail, $companyPhone, $logoBase64];
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

                $monthlyRevenue    = $monthlyOrders->sum('total_price') ?? 0;
                $monthlyOrderCount = $monthlyOrders->count();
            } else {
                $monthlyRevenue    = 0;
                $monthlyOrderCount = 0;
            }

            $yearlyData[$month] = [
                'name'        => $months[$month],
                'orders'      => $monthlyOrderCount,
                'revenue'     => $monthlyRevenue,
                'target'      => $fixedTargetAmount,
                'achievement' => $fixedTargetAmount > 0 ? ($monthlyRevenue / $fixedTargetAmount) * 100 : 0,
            ];
        }

        $totalOrders  = 0;
        $totalRevenue = 0;
        $totalTarget  = 0;

        for ($month = 1; $month <= $limitMonth; $month++) {
            $totalOrders  += $yearlyData[$month]['orders'];
            $totalRevenue += $yearlyData[$month]['revenue'];
            $totalTarget  += $yearlyData[$month]['target'];
        }

        return [
            'monthly' => $yearlyData,
            'summary' => [
                'orders'      => $totalOrders,
                'revenue'     => $totalRevenue,
                'target'      => $totalTarget,
                'achievement' => $totalTarget > 0 ? ($totalRevenue / $totalTarget) * 100 : 0,
            ],
        ];
    }
}
