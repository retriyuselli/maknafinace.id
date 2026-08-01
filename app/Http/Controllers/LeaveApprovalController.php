<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveApprovalController extends Controller
{
    public function show(LeaveRequest $leaveRequest)
    {
        // Check if the leave request is approved
        if ($leaveRequest->status !== 'approved') {
            abort(404, 'Approval detail not found or request not approved.');
        }

        $leaveRequest->load(['user', 'leaveType', 'approver', 'leaveBalanceHistory', 'replacementEmployee']);

        $year = $leaveRequest->start_date ? $leaveRequest->start_date->year : now()->year;
        $leaveBalance = LeaveBalance::where('user_id', $leaveRequest->user_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $year)
            ->first();

        $start = \Carbon\Carbon::parse($leaveRequest->start_date);
        $end = \Carbon\Carbon::parse($leaveRequest->end_date);
        $workingDays = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dayIso = (int) $date->isoWeekday();
            if (! in_array($dayIso, [6, 7], true)) {
                $workingDays++;
            }
        }

        $carryOver = $leaveBalance->carried_over_days ?? 0;
        $usedJanMar = \App\Models\LeaveRequest::query()
            ->where('user_id', $leaveRequest->user_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', '<=', 3)
            ->sum('total_days');
        $cutoffDate = \Carbon\Carbon::create($year, 3, 31)->endOfDay();
        $effectiveCarryOver = now()->gt($cutoffDate) ? min($carryOver, $usedJanMar) : $carryOver;

        return view('leave-approval.detail', [
            'record' => $leaveRequest,
            'leaveBalance' => $leaveBalance,
            'workingDays' => $workingDays,
            'carryOver' => $carryOver,
            'usedJanMar' => $usedJanMar,
            'effectiveCarryOver' => $effectiveCarryOver,
        ]);
    }
}
