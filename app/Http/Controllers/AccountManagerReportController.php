<?php

namespace App\Http\Controllers;

use App\Models\AccountManagerTarget;
use App\Models\LeaveRequest;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\User;
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
}