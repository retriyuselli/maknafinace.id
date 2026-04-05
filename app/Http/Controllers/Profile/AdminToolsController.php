<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyLogo;
use App\Models\BankReconciliationItem;
use App\Models\BankStatement;
use App\Models\BankTransaction;
use App\Models\DocumentCategory;
use App\Models\Documentation;
use App\Models\Expense;
use App\Models\NotaDinas;
use App\Models\NotaDinasDetail;
use App\Models\Order;
use App\Models\Sop;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminToolsController extends Controller
{
    public function index()
    {
        return view('profile.admin-tools.index', [
            'usersCount' => User::query()->count(),
            'rolesCount' => Role::query()->count(),
            'companiesCount' => Company::query()->count(),
            'logosCount' => CompanyLogo::query()->count(),
            'sopsCount' => Sop::query()->count(),
            'documentationsCount' => Documentation::query()->count(),
            'documentCategoriesCount' => DocumentCategory::query()->count(),
            'projectsCount' => Order::query()->count(),
            'notaDinasCount' => NotaDinas::query()->count(),
            'notaDinasDetailsCount' => NotaDinasDetail::query()->count(),
            'bankStatementsCount' => BankStatement::query()->count(),
        ]);
    }

    public function users(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $usersQuery = User::query()
            ->with('roles')
            ->orderBy('name');

        if ($q !== '') {
            $usersQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return view('profile.admin-tools.users', [
            'q' => $q,
            'users' => $usersQuery->paginate(20)->withQueryString(),
        ]);
    }

    public function roles()
    {
        return view('profile.admin-tools.roles', [
            'roles' => Role::query()->withCount('permissions')->orderBy('name')->get(),
        ]);
    }

    public function company()
    {
        return view('profile.admin-tools.company', [
            'company' => Company::query()->latest('id')->first(),
        ]);
    }

    public function branding()
    {
        return view('profile.admin-tools.branding', [
            'logos' => CompanyLogo::query()->ordered()->paginate(20),
        ]);
    }

    public function sops(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $sopsQuery = Sop::query()
            ->with('category')
            ->orderByDesc('updated_at');

        if ($q !== '') {
            $sopsQuery->search($q);
        }

        return view('profile.admin-tools.sops', [
            'q' => $q,
            'sops' => $sopsQuery->paginate(15)->withQueryString(),
        ]);
    }

    public function documentations(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $docsQuery = Documentation::query()
            ->with('category')
            ->orderBy('order')
            ->orderBy('title');

        if ($q !== '') {
            $docsQuery->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('keywords', 'like', "%{$q}%");
            });
        }

        return view('profile.admin-tools.documentations', [
            'q' => $q,
            'docs' => $docsQuery->paginate(20)->withQueryString(),
        ]);
    }

    public function documentCategories()
    {
        return view('profile.admin-tools.document-categories', [
            'categories' => DocumentCategory::query()
                ->with('parent')
                ->orderBy('type')
                ->orderBy('name')
                ->paginate(30),
        ]);
    }

    public function projects(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $period = (string) $request->get('period', 'all');
        $month = trim((string) $request->get('month', ''));
        $monthYear = null;
        $monthMonth = null;

        if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            [$monthYear, $monthMonth] = array_map('intval', explode('-', $month, 2));
            if ($monthYear > 0 && $monthMonth >= 1 && $monthMonth <= 12) {
                $period = 'custom';
            } else {
                $monthYear = null;
                $monthMonth = null;
                $month = '';
            }
        } else {
            $month = '';
        }

        $projectsQuery = Order::query()
            ->with([
                'prospect',
                'user',
                'employee',
                'items:id,order_id,product_id,quantity,unit_price',
                'dataPengeluaran:id,order_id,amount',
            ])
            ->orderByDesc('created_at');

        if ($q !== '') {
            $projectsQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('number', 'like', "%{$q}%")
                    ->orWhere('no_kontrak', 'like', "%{$q}%");
            });
        }

        if ($period === 'year') {
            $projectsQuery->whereHas('prospect', function ($q) {
                $q->whereYear('date_resepsi', now()->year)
                  ->orWhereYear('date_akad', now()->year)
                  ->orWhereYear('date_lamaran', now()->year);
            });
        } elseif ($period === 'month') {
            $projectsQuery->whereHas('prospect', function ($q) {
                $q->where(function ($m) {
                    $m->whereYear('date_resepsi', now()->year)
                      ->whereMonth('date_resepsi', now()->month);
                })->orWhere(function ($m) {
                    $m->whereYear('date_akad', now()->year)
                      ->whereMonth('date_akad', now()->month);
                })->orWhere(function ($m) {
                    $m->whereYear('date_lamaran', now()->year)
                      ->whereMonth('date_lamaran', now()->month);
                });
            });
        } elseif ($period === 'custom' && $monthYear !== null && $monthMonth !== null) {
            $projectsQuery->whereHas('prospect', function ($q) use ($monthYear, $monthMonth) {
                $q->where(function ($m) use ($monthYear, $monthMonth) {
                    $m->whereYear('date_resepsi', $monthYear)
                      ->whereMonth('date_resepsi', $monthMonth);
                })->orWhere(function ($m) use ($monthYear, $monthMonth) {
                    $m->whereYear('date_akad', $monthYear)
                      ->whereMonth('date_akad', $monthMonth);
                })->orWhere(function ($m) use ($monthYear, $monthMonth) {
                    $m->whereYear('date_lamaran', $monthYear)
                      ->whereMonth('date_lamaran', $monthMonth);
                });
            });
        } else {
            $period = 'all';
        }

        $projectsCount = (int) (clone $projectsQuery)->count();
        $grandTotalSum = (int) (clone $projectsQuery)->sum('grand_total');
        $orderIdsQuery = (clone $projectsQuery)->reorder()->select('id');
        $expensesSum = (int) Expense::query()->whereIn('order_id', $orderIdsQuery)->sum('amount');
        $profitSum = $grandTotalSum - $expensesSum;
        $profitAvg = $projectsCount > 0 ? (int) round($profitSum / $projectsCount) : 0;

        return view('profile.admin-tools.projects', [
            'q' => $q,
            'projects' => $projectsQuery->paginate(20)->withQueryString(),
            'projectsCount' => $projectsCount,
            'grandTotalSum' => $grandTotalSum,
            'expensesSum' => $expensesSum,
            'profitSum' => $profitSum,
            'profitAvg' => $profitAvg,
            'period' => $period,
            'month' => $month,
        ]);
    }

    public function project(Order $order)
    {
        $order->loadMissing([
            'prospect',
            'user',
            'employee',
            'items.product',
            'dataPengeluaran.vendor',
            'dataPengeluaran.paymentMethod',
            'dataPengeluaran.notaDinasDetail.vendor',
            'dataPengeluaran.notaDinasDetail.notaDinas',
        ]);

        return view('profile.admin-tools.project-show', [
            'order' => $order,
        ]);
    }

    public function notaDinas(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $month = trim((string) $request->get('month', ''));
        $monthYear = null;
        $monthMonth = null;

        if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            [$monthYear, $monthMonth] = array_map('intval', explode('-', $month, 2));
            if ($monthYear <= 0 || $monthMonth < 1 || $monthMonth > 12) {
                $monthYear = null;
                $monthMonth = null;
                $month = '';
            }
        } else {
            $month = '';
        }

        $notaDinasQuery = NotaDinas::query()
            ->with(['pengirim:id,name', 'penerima:id,name', 'approver:id,name'])
            ->withCount('details')
            ->withCount(['details as details_paid_count' => fn ($q) => $q->where('status_invoice', 'sudah_dibayar')])
            ->withSum('details as details_sum', 'jumlah_transfer')
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        if ($q !== '') {
            $notaDinasQuery->where(function ($sub) use ($q) {
                $sub->where('no_nd', 'like', "%{$q}%")
                    ->orWhere('hal', 'like', "%{$q}%")
                    ->orWhere('catatan', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $notaDinasQuery->where('status', $status);
        }

        if ($monthYear !== null && $monthMonth !== null) {
            $notaDinasQuery->whereYear('tanggal', $monthYear)->whereMonth('tanggal', $monthMonth);
        }

        $notaDinasCount = (int) (clone $notaDinasQuery)->count();
        $notaDinasIdsQuery = (clone $notaDinasQuery)->reorder()->select('id');
        $detailsCount = (int) NotaDinasDetail::query()->whereIn('nota_dinas_id', $notaDinasIdsQuery)->count();
        $detailsPaidCount = (int) NotaDinasDetail::query()
            ->whereIn('nota_dinas_id', $notaDinasIdsQuery)
            ->where('status_invoice', 'sudah_dibayar')
            ->count();
        $detailsSum = (float) NotaDinasDetail::query()->whereIn('nota_dinas_id', $notaDinasIdsQuery)->sum('jumlah_transfer');

        $statusSummary = NotaDinas::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        return view('profile.admin-tools.nota-dinas.index', [
            'q' => $q,
            'status' => $status,
            'month' => $month,
            'notaDinas' => $notaDinasQuery->paginate(20)->withQueryString(),
            'notaDinasCount' => $notaDinasCount,
            'detailsCount' => $detailsCount,
            'detailsPaidCount' => $detailsPaidCount,
            'detailsSum' => $detailsSum,
            'statusSummary' => $statusSummary,
        ]);
    }

    public function notaDinasShow(NotaDinas $notaDinas, Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $statusInvoice = trim((string) $request->get('status_invoice', ''));

        $notaDinas->loadMissing(['pengirim:id,name', 'penerima:id,name', 'approver:id,name']);

        $detailsQuery = $notaDinas->details()
            ->with(['vendor:id,name', 'order:id,name'])
            ->orderByDesc('id');

        if ($q !== '') {
            $detailsQuery->where(function ($sub) use ($q) {
                $sub->where('keperluan', 'like', "%{$q}%")
                    ->orWhere('event', 'like', "%{$q}%")
                    ->orWhere('invoice_number', 'like', "%{$q}%")
                    ->orWhereHas('vendor', fn ($v) => $v->where('name', 'like', "%{$q}%"));
            });
        }

        if ($statusInvoice !== '') {
            $detailsQuery->where('status_invoice', $statusInvoice);
        }

        $detailsCount = (int) (clone $detailsQuery)->count();
        $detailsPaidCount = (int) (clone $detailsQuery)->where('status_invoice', 'sudah_dibayar')->count();
        $detailsSum = (float) (clone $detailsQuery)->sum('jumlah_transfer');

        $paymentStageSummary = (clone $detailsQuery)
            ->reorder()
            ->selectRaw('payment_stage, COUNT(*) as c')
            ->groupBy('payment_stage')
            ->pluck('c', 'payment_stage')
            ->toArray();

        return view('profile.admin-tools.nota-dinas.show', [
            'notaDinas' => $notaDinas,
            'q' => $q,
            'statusInvoice' => $statusInvoice,
            'details' => $detailsQuery->paginate(30)->withQueryString(),
            'detailsCount' => $detailsCount,
            'detailsPaidCount' => $detailsPaidCount,
            'detailsSum' => $detailsSum,
            'paymentStageSummary' => $paymentStageSummary,
        ]);
    }

    public function bankStatements()
    {
        $baseQuery = BankStatement::query();

        $totalCount = (int) (clone $baseQuery)->count();
        $pendingCount = (int) (clone $baseQuery)->where('status', 'pending')->count();
        $processingCount = (int) (clone $baseQuery)->where('status', 'processing')->count();
        $parsedCount = (int) (clone $baseQuery)->where('status', 'parsed')->count();
        $failedCount = (int) (clone $baseQuery)->where('status', 'failed')->count();

        $reconUploadedCount = (int) (clone $baseQuery)->where('reconciliation_status', 'uploaded')->count();
        $reconProcessingCount = (int) (clone $baseQuery)->where('reconciliation_status', 'processing')->count();
        $reconCompletedCount = (int) (clone $baseQuery)->where('reconciliation_status', 'completed')->count();
        $reconFailedCount = (int) (clone $baseQuery)->where('reconciliation_status', 'failed')->count();

        $latestStatements = BankStatement::query()
            ->with('paymentMethod')
            ->withCount(['transactions', 'reconciliationItems'])
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $monthlySummary = BankStatement::query()
            ->selectRaw("DATE_FORMAT(period_end, '%Y-%m') as ym")
            ->selectRaw('COUNT(*) as statements_count')
            ->selectRaw('SUM(COALESCE(tot_debit, 0)) as tot_debit_sum')
            ->selectRaw('SUM(COALESCE(tot_credit, 0)) as tot_credit_sum')
            ->selectRaw("SUM(status = 'failed') as failed_count")
            ->selectRaw("SUM(reconciliation_status = 'failed') as recon_failed_count")
            ->whereNotNull('period_end')
            ->groupBy('ym')
            ->orderByDesc('ym')
            ->limit(12)
            ->get();

        return view('profile.admin-tools.bank-statements.index', [
            'totalCount' => $totalCount,
            'pendingCount' => $pendingCount,
            'processingCount' => $processingCount,
            'parsedCount' => $parsedCount,
            'failedCount' => $failedCount,
            'reconUploadedCount' => $reconUploadedCount,
            'reconProcessingCount' => $reconProcessingCount,
            'reconCompletedCount' => $reconCompletedCount,
            'reconFailedCount' => $reconFailedCount,
            'latestStatements' => $latestStatements,
            'monthlySummary' => $monthlySummary,
        ]);
    }

    public function bankStatementsGuide()
    {
        return view('profile.admin-tools.bank-statements.guide');
    }

    public function bankStatementsFailed()
    {
        $statements = BankStatement::query()
            ->with('paymentMethod')
            ->withCount(['transactions', 'reconciliationItems'])
            ->where(function ($q) {
                $q->where('status', 'failed')
                    ->orWhere('reconciliation_status', 'failed');
            })
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->paginate(20);

        return view('profile.admin-tools.bank-statements.failed', [
            'statements' => $statements,
        ]);
    }

    public function bankStatementsReconciliation()
    {
        $statements = BankStatement::query()
            ->with('paymentMethod')
            ->withCount(['transactions', 'reconciliationItems'])
            ->where(function ($q) {
                $q->whereIn('reconciliation_status', ['processing', 'completed', 'failed'])
                    ->orWhereHas('reconciliationItems');
            })
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->paginate(20);

        return view('profile.admin-tools.bank-statements.reconciliation', [
            'statements' => $statements,
        ]);
    }

    public function bankStatementShow(BankStatement $bankStatement)
    {
        $bankStatement->loadMissing(['paymentMethod']);

        $transactions = BankTransaction::query()
            ->where('bank_statement_id', $bankStatement->id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $reconciliationItems = BankReconciliationItem::query()
            ->where('bank_reconciliation_id', $bankStatement->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('profile.admin-tools.bank-statements.show', [
            'bankStatement' => $bankStatement,
            'transactions' => $transactions,
            'reconciliationItems' => $reconciliationItems,
        ]);
    }

    public function helpCenter()
    {
        return view('profile.admin-tools.help-center');
    }
}
