<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyLogo;
use App\Models\DocumentCategory;
use App\Models\Documentation;
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

        return view('profile.admin-tools.projects', [
            'q' => $q,
            'projects' => $projectsQuery->paginate(20)->withQueryString(),
        ]);
    }

    public function project(Order $order)
    {
        $order->loadMissing(['prospect', 'user', 'employee', 'items.product', 'dataPengeluaran:id,order_id,amount']);

        return view('profile.admin-tools.project-show', [
            'order' => $order,
        ]);
    }

    public function helpCenter()
    {
        return view('profile.admin-tools.help-center');
    }
}
