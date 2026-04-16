<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    private function profileViewData(): array
    {
        $user = Auth::user();
        if ($user instanceof \App\Models\User) {
            $user->load(['status', 'roles']);
        }

        $viewData = [];
        if ($user instanceof User) {
            $viewData = array_merge(
                $this->upcomingEventsViewData($user),
                $this->hrSalaryLeaveViewData($user),
            );
        }
        return array_merge(compact('user'), $viewData);
    }

    private function upcomingEventsViewData(User $user): array
    {
        $currentDate = now();

        $upcomingLeaves = $user
            ->leaveRequests()
            ->with('leaveType')
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_date', '>=', $currentDate)
            ->orderBy('start_date', 'asc')
            ->take(5)
            ->get();

        $recentLeaves = $user
            ->leaveRequests()
            ->with('leaveType')
            ->where('start_date', '<', $currentDate)
            ->orderBy('start_date', 'desc')
            ->take(3)
            ->get();

        $nextLeave = $upcomingLeaves->first();
        $daysUntilNextLeave = $nextLeave ? (int) $currentDate->diffInDays($nextLeave->start_date, false) : null;

        $statusTranslations = [
            'approved' => 'Disetujui',
            'pending' => 'Menunggu',
            'rejected' => 'Ditolak',
        ];

        $leaveTypeTranslations = [
            'Annual Leave' => 'Cuti Tahunan',
            'Sick Leave' => 'Cuti Sakit',
            'Emergency Leave' => 'Cuti Darurat',
            'Unpaid Leave' => 'Cuti Tanpa Gaji',
            'Maternity Leave' => 'Cuti Melahirkan',
            'Paternity Leave' => 'Cuti Ayah',
            'Marriage Leave' => 'Cuti Menikah',
            'Bereavement Leave' => 'Cuti Duka',
        ];

        return compact(
            'currentDate',
            'upcomingLeaves',
            'recentLeaves',
            'nextLeave',
            'daysUntilNextLeave',
            'statusTranslations',
            'leaveTypeTranslations',
        );
    }

    private function hrSalaryLeaveViewData(User $user): array
    {
        $latestPayroll = $user->payrolls()->latest()->first();
        $currentYear = (int) date('Y');

        $period = request()->query('period', 'year');
        $leaveQueryForPeriod = function () use ($user, $period, $currentYear) {
            $q = $user->leaveRequests();
            if ($period === 'year') {
                $q->whereYear('start_date', $currentYear);
            } elseif ($period === 'last_year') {
                $q->whereYear('start_date', (int) $currentYear - 1);
            }
            return $q;
        };

        $leaveStats = [
            'approved' => $leaveQueryForPeriod()->where('status', 'approved')->sum('total_days'),
            'pending' => $leaveQueryForPeriod()->where('status', 'pending')->sum('total_days'),
            'rejected' => $leaveQueryForPeriod()->where('status', 'rejected')->sum('total_days'),
        ];

        $leaveByType = $leaveQueryForPeriod()
            ->with('leaveType')
            ->where('status', 'approved')
            ->get()
            ->groupBy('leaveType.name')
            ->map(function ($leaves) {
                return $leaves->sum('total_days');
            });

        $annualLeaveAllowance = $user->annual_leave_quota ?? 12;
        if ($annualLeaveAllowance < 12) {
            $annualLeaveAllowance = 12;
        }

        $usedLeave = $leaveStats['approved'];
        $remainingLeave = max(0, $annualLeaveAllowance - $usedLeave);

        if ($usedLeave > $annualLeaveAllowance) {
            $displayUsedLeave = $usedLeave;
            $remainingLeave = 0;
        } else {
            $displayUsedLeave = $usedLeave;
        }

        $prevYear = (int) $currentYear - 1;
        $prevUsedLeave = $user->leaveRequests()
            ->where('status', 'approved')
            ->whereYear('start_date', $prevYear)
            ->sum('total_days');
        $prevUsagePercentage = $annualLeaveAllowance > 0 ? round(($prevUsedLeave / $annualLeaveAllowance) * 100) : 0;

        $currentMonth = (int) date('n');
        $prevRemaining = max(0, $annualLeaveAllowance - $prevUsedLeave);
        $carryOver = $currentMonth <= 2 ? $prevRemaining : 0;
        $effectiveAllowanceYear = $annualLeaveAllowance + $carryOver;

        $leaveTypeTranslations = [
            'Annual Leave' => 'Cuti Tahunan',
            'Sick Leave' => 'Cuti Sakit',
            'Emergency Leave' => 'Cuti Darurat',
            'Unpaid Leave' => 'Cuti Tanpa Gaji',
            'Maternity Leave' => 'Cuti Melahirkan',
            'Paternity Leave' => 'Cuti Ayah',
            'Marriage Leave' => 'Cuti Menikah',
            'Bereavement Leave' => 'Cuti Duka',
        ];

        return compact(
            'latestPayroll',
            'currentYear',
            'period',
            'leaveStats',
            'leaveByType',
            'annualLeaveAllowance',
            'usedLeave',
            'displayUsedLeave',
            'remainingLeave',
            'prevYear',
            'prevUsedLeave',
            'prevUsagePercentage',
            'carryOver',
            'effectiveAllowanceYear',
            'leaveTypeTranslations',
        );
    }

    /**
     * Show the user's profile.
     */
    public function show()
    {
        return $this->overview();
    }

    public function overview()
    {
        return view('profile.show', $this->profileViewData());
    }

    public function compensation()
    {
        return view('profile.compensation', $this->profileViewData());
    }

    public function schedule()
    {
        return view('profile.schedule', $this->profileViewData());
    }

    public function financialReport(Request $request)
    {
        $user = Auth::user();
        if (! ($user instanceof User)) {
            abort(403);
        }

        if (! $user->hasRole('super_admin')) {
            abort(403);
        }

        $monthParam = (string) $request->query('month', now()->format('Y-m'));
        $selectedMonth = preg_match('/^\d{4}-\d{2}$/', $monthParam) ? $monthParam : now()->format('Y-m');

        try {
            $selectedDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable) {
            $selectedDate = now()->startOfMonth();
            $selectedMonth = $selectedDate->format('Y-m');
        }

        $availableMonths = collect(range(0, 11))
            ->map(function (int $i) {
                $d = now()->startOfMonth()->subMonths($i)->startOfMonth();
                return [
                    'value' => $d->format('Y-m'),
                    'label' => $d->copy()->locale('id')->translatedFormat('F Y'),
                ];
            })
            ->values()
            ->all();

        $start = $selectedDate->copy()->startOfMonth();
        $end = $selectedDate->copy()->endOfMonth();

        $weddingIncome = (int) DataPembayaran::query()
            ->whereBetween('tgl_bayar', [$start, $end])
            ->sum('nominal');
        $otherIncome = (int) PendapatanLain::query()
            ->whereBetween('tgl_bayar', [$start, $end])
            ->sum('nominal');

        $weddingExpense = (int) Expense::query()
            ->whereBetween('date_expense', [$start, $end])
            ->sum('amount');
        $opsExpense = (int) ExpenseOps::query()
            ->whereBetween('date_expense', [$start, $end])
            ->sum('amount');
        $otherExpense = (int) PengeluaranLain::query()
            ->whereBetween('date_expense', [$start, $end])
            ->sum('amount');

        $incomeItems = [
            ['type' => 'income_wedding', 'label' => 'Pemasukan Wedding', 'amount' => $weddingIncome],
            ['type' => 'income_other', 'label' => 'Pendapatan Lain', 'amount' => $otherIncome],
        ];

        $expenseItems = [
            ['type' => 'expense_wedding', 'label' => 'Pengeluaran Wedding', 'amount' => $weddingExpense],
            ['type' => 'expense_ops', 'label' => 'Pengeluaran Operasional', 'amount' => $opsExpense],
            ['type' => 'expense_other', 'label' => 'Pengeluaran Lain', 'amount' => $otherExpense],
        ];

        $totalIncome = array_sum(array_map(fn ($row) => (int) ($row['amount'] ?? 0), $incomeItems));
        $totalExpense = array_sum(array_map(fn ($row) => (int) ($row['amount'] ?? 0), $expenseItems));
        $netCashFlow = $totalIncome - $totalExpense;

        return view('profile.financial-report', array_merge(
            $this->profileViewData(),
            [
                'selectedMonth' => $selectedMonth,
                'selectedMonthLabel' => $selectedDate->copy()->locale('id')->translatedFormat('F Y'),
                'availableMonths' => $availableMonths,
                'incomeItems' => $incomeItems,
                'expenseItems' => $expenseItems,
                'totalIncome' => $totalIncome,
                'totalExpense' => $totalExpense,
                'netCashFlow' => $netCashFlow,
            ],
        ));
    }

    public function financialReportDetail(Request $request, string $type)
    {
        $user = Auth::user();
        if (! ($user instanceof User)) {
            abort(403);
        }

        if (! $user->hasRole('super_admin')) {
            abort(403);
        }

        $typeMap = [
            'income_wedding' => ['label' => 'Pemasukan Wedding', 'kind' => 'income'],
            'income_other' => ['label' => 'Pendapatan Lain', 'kind' => 'income'],
            'expense_wedding' => ['label' => 'Pengeluaran Wedding', 'kind' => 'expense'],
            'expense_ops' => ['label' => 'Pengeluaran Operasional', 'kind' => 'expense'],
            'expense_other' => ['label' => 'Pengeluaran Lain', 'kind' => 'expense'],
        ];

        if (! array_key_exists($type, $typeMap)) {
            abort(404);
        }

        $monthParam = (string) $request->query('month', now()->format('Y-m'));
        $selectedMonth = preg_match('/^\d{4}-\d{2}$/', $monthParam) ? $monthParam : now()->format('Y-m');

        try {
            $selectedDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable) {
            $selectedDate = now()->startOfMonth();
            $selectedMonth = $selectedDate->format('Y-m');
        }

        $start = $selectedDate->copy()->startOfMonth();
        $end = $selectedDate->copy()->endOfMonth();

        $rows = collect();
        $total = 0;

        if ($type === 'income_wedding') {
            $rows = DataPembayaran::query()
                ->with(['order.prospect'])
                ->whereBetween('tgl_bayar', [$start, $end])
                ->orderBy('tgl_bayar')
                ->orderBy('id')
                ->get(['id', 'tgl_bayar', 'nominal', 'keterangan', 'order_id', 'kategori_transaksi']);
            $total = (int) $rows->sum('nominal');
            $rows = $rows->map(function (DataPembayaran $r) {
                return [
                    'id' => $r->id,
                    'date' => $r->tgl_bayar?->format('d/m/Y') ?? '-',
                    'reference' => ! empty($r->order_id) ? 'Order #'.$r->order_id : '-',
                    'prospect' => $r->order?->prospect?->name_event ?? '-',
                    'description' => $r->keterangan ?: '-',
                    'amount' => (int) $r->nominal,
                ];
            });
        } elseif ($type === 'income_other') {
            $rows = PendapatanLain::query()
                ->whereBetween('tgl_bayar', [$start, $end])
                ->orderBy('tgl_bayar')
                ->orderBy('id')
                ->get(['id', 'tgl_bayar', 'nominal', 'name', 'keterangan', 'vendor_id', 'kategori_transaksi']);
            $total = (int) $rows->sum('nominal');
            $rows = $rows->map(function (PendapatanLain $r) {
                $desc = $r->name ?: ($r->keterangan ?: '-');
                return [
                    'id' => $r->id,
                    'date' => $r->tgl_bayar?->format('d/m/Y') ?? '-',
                    'reference' => ! empty($r->vendor_id) ? 'Vendor #'.$r->vendor_id : '-',
                    'prospect' => $r->name ?: '-',
                    'description' => $desc,
                    'amount' => (int) $r->nominal,
                ];
            });
        } elseif ($type === 'expense_wedding') {
            $rows = Expense::query()
                ->with(['order.prospect'])
                ->whereBetween('date_expense', [$start, $end])
                ->orderBy('date_expense')
                ->orderBy('id')
                ->get(['id', 'date_expense', 'amount', 'note', 'order_id', 'vendor_id', 'kategori_transaksi']);
            $total = (int) $rows->sum('amount');
            $rows = $rows->map(function (Expense $r) {
                $refs = [];
                if (! empty($r->order_id)) {
                    $refs[] = 'Order #'.$r->order_id;
                }
                if (! empty($r->vendor_id)) {
                    $refs[] = 'Vendor #'.$r->vendor_id;
                }
                return [
                    'id' => $r->id,
                    'date' => $r->date_expense?->format('d/m/Y') ?? '-',
                    'reference' => ! empty($refs) ? implode(' • ', $refs) : '-',
                    'prospect' => $r->order?->prospect?->name_event ?? '-',
                    'description' => $r->note ?: '-',
                    'amount' => (int) $r->amount,
                ];
            });
        } elseif ($type === 'expense_ops') {
            $rows = ExpenseOps::query()
                ->whereBetween('date_expense', [$start, $end])
                ->orderBy('date_expense')
                ->orderBy('id')
                ->get(['id', 'date_expense', 'amount', 'name', 'note', 'vendor_id', 'kategori_transaksi']);
            $total = (int) $rows->sum('amount');
            $rows = $rows->map(function (ExpenseOps $r) {
                return [
                    'id' => $r->id,
                    'date' => $r->date_expense?->format('d/m/Y') ?? '-',
                    'reference' => ! empty($r->vendor_id) ? 'Vendor #'.$r->vendor_id : '-',
                    'prospect' => $r->name ?: '-',
                    'description' => $r->note ?: '-',
                    'amount' => (int) $r->amount,
                ];
            });
        } elseif ($type === 'expense_other') {
            $rows = PengeluaranLain::query()
                ->whereBetween('date_expense', [$start, $end])
                ->orderBy('date_expense')
                ->orderBy('id')
                ->get(['id', 'date_expense', 'amount', 'name', 'note', 'vendor_id', 'kategori_transaksi']);
            $total = (int) $rows->sum('amount');
            $rows = $rows->map(function (PengeluaranLain $r) {
                return [
                    'id' => $r->id,
                    'date' => $r->date_expense?->format('d/m/Y') ?? '-',
                    'reference' => ! empty($r->vendor_id) ? 'Vendor #'.$r->vendor_id : '-',
                    'prospect' => $r->name ?: '-',
                    'description' => $r->note ?: '-',
                    'amount' => (int) $r->amount,
                ];
            });
        }

        return view('profile.financial-report-detail', array_merge(
            $this->profileViewData(),
            [
                'selectedMonth' => $selectedMonth,
                'selectedMonthLabel' => $selectedDate->copy()->locale('id')->translatedFormat('F Y'),
                'type' => $type,
                'typeLabel' => $typeMap[$type]['label'],
                'kind' => $typeMap[$type]['kind'],
                'rows' => $rows->values()->all(),
                'total' => $total,
            ],
        ));
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = Auth::user();

        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'hire_date' => ['nullable', 'date'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'signature_url' => ['nullable', 'image', 'mimes:png', 'max:1024'],
        ];

        // Add password validation if password field is filled
        if ($request->filled('password')) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $request->validate($rules);

        // Debugging: Log request
        Log::info('Profile Update Request', [
            'method' => $request->method(),
            'has_avatar' => $request->hasFile('avatar'),
            'avatar_error' => $request->hasFile('avatar') ? $request->file('avatar')->getError() : null,
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
                Storage::disk('public')->delete($user->avatar_url);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = $avatarPath;
        }

        // Handle signature upload
        if ($request->hasFile('signature')) {
            // Delete old signature if exists
            if ($user->signature_url && Storage::disk('public')->exists($user->signature_url)) {
                Storage::disk('public')->delete($user->signature_url);
            }

            // Store new signature
            $signaturePath = $request->file('signature')->store('signatures', 'public');
            $user->signature_url = $signaturePath;
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'hire_date' => $request->hire_date,
            'emergency_contact' => $request->emergency_contact,
            'avatar_url' => $user->avatar_url,
            'signature_url' => $user->signature_url,
            'updated_at' => now(),
        ];

        // Update password if filled
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Update user data using DB
        DB::table('users')
            ->where('id', $user->id)
            ->update($updateData);

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'password' => Hash::make($request->password),
                'updated_at' => now(),
            ]);

        return redirect()->route('profile')->with('success', 'Password updated successfully!');
    }

    /**
     * Generate user performance report.
     */
    public function generateReport()
    {
        $user = Auth::user();

        $reportData = [
            'user' => $user,
            'period' => now()->format('F Y'),
            'projects_completed' => 23,
            'client_satisfaction' => 97,
            'revenue_generated' => 125000,
            'performance_score' => 'Excellent',
            'goals_achieved' => 15,
            'total_goals' => 18,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'data' => $reportData,
        ]);
    }

    /**
     * Get user's upcoming events.
     */
    public function getEvents()
    {
        $events = [
            [
                'id' => 1,
                'title' => 'Team Meeting',
                'date' => now()->format('Y-m-d H:i:s'),
                'type' => 'meeting',
                'status' => 'upcoming',
            ],
            [
                'id' => 2,
                'title' => 'Client Consultation',
                'date' => now()->addDay()->format('Y-m-d H:i:s'),
                'type' => 'consultation',
                'status' => 'scheduled',
            ],
            [
                'id' => 3,
                'title' => 'Wedding Event',
                'date' => now()->addDays(20)->format('Y-m-d H:i:s'),
                'type' => 'event',
                'status' => 'confirmed',
            ],
        ];

        return response()->json($events);
    }

    /**
     * Get user's HR benefits information.
     */
    public function getBenefits()
    {
        $benefits = [
            'health_insurance' => [
                'status' => 'Active',
                'provider' => 'Corporate Health Plus',
                'coverage' => 'Full Coverage',
                'expiry' => now()->addYear()->format('F d, Y'),
            ],
            'annual_leave' => [
                'total_days' => 24,
                'used_days' => 6,
                'remaining_days' => 18,
                'pending_requests' => 0,
            ],
            'performance_bonus' => [
                'eligibility' => 'Eligible',
                'last_bonus' => '$5,000',
                'next_review' => 'June 2024',
                'performance_score' => 97,
            ],
            'training_budget' => [
                'annual_budget' => 5000,
                'used_budget' => 2500,
                'remaining_budget' => 2500,
                'last_training' => 'Advanced Wedding Planning',
            ],
        ];

        return response()->json($benefits);
    }
}
