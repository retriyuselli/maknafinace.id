<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyLogo;
use App\Models\DocumentCategory;
use App\Models\Documentation;
use App\Models\Expense;
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
        ]);

        return view('profile.admin-tools.project-show', [
            'order' => $order,
        ]);
    }

    public function helpCenter()
    {
        return view('profile.admin-tools.help-center');
    }
}
