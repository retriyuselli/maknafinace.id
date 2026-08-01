<?php

namespace App\Observers;

use App\Models\LeaveRequest;
use App\Services\AbsensiRekapService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LeaveRequestObserver
{
    public function __construct(private AbsensiRekapService $absensiRekapService) {}

    /**
     * Handle the LeaveRequest "creating" event.
     */
    public function creating(LeaveRequest $leaveRequest): void
    {
        if (empty($leaveRequest->user_id) && Auth::check()) {
            $leaveRequest->user_id = Auth::id();
        }
    }

    /**
     * Handle the LeaveRequest "created" event.
     */
    public function created(LeaveRequest $leaveRequest): void
    {
        $this->sinkronkanAbsensiJikaDisetujui($leaveRequest);
    }

    /**
     * Handle the LeaveRequest "updated" event.
     */
    public function updated(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->wasChanged('status') || $leaveRequest->wasChanged(['start_date', 'end_date'])) {
            $this->sinkronkanAbsensiJikaDisetujui($leaveRequest);
        }
    }

    protected function sinkronkanAbsensiJikaDisetujui(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status !== 'approved') {
            return;
        }

        try {
            $updated = $this->absensiRekapService->sinkronkanCuti($leaveRequest);

            Log::info('Absensi cuti disinkronkan dari LeaveRequest', [
                'leave_request_id' => $leaveRequest->id,
                'user_id' => $leaveRequest->user_id,
                'hari_diperbarui' => $updated,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal sinkron absensi dari cuti', [
                'leave_request_id' => $leaveRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
