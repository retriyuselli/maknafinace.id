<?php

namespace App\Services;

use App\Enums\JenisPiutang;
use App\Enums\MonthEnum;
use App\Enums\StatusPiutang;
use App\Enums\StatusVendor;
use App\Models\AccountManagerTarget;
use App\Models\AssetDepreciation;
use App\Models\BankStatement;
use App\Models\BankTransaction;
use App\Models\Category;
use App\Models\DataPribadi;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Documentation;
use App\Models\DocumentationCategory;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\FixedAsset;
use App\Models\NotaDinas;
use App\Models\NotaDinasDetail;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Payroll;
use App\Models\PembayaranPiutang;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use App\Models\Piutang;
use App\Models\Product;
use App\Models\Prospect;
use App\Models\SimulasiProduk;
use App\Models\Sop;
use App\Models\SopCategory;
use App\Models\User;
use App\Models\Vendor;
use App\Support\CompanySubscription;
use App\Support\PricingPlans;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileModuleService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function catalog(?User $user): array
    {
        $items = [];

        foreach ($this->definitions() as $key => $def) {
            $allowed = $this->allows($user, $def);
            $canCreate = $allowed && $this->canCreate($def);
            $count = $allowed ? $this->scopedQuery($def)->count() : 0;

            $items[] = [
                'key' => $key,
                'title' => $def['title'],
                'subtitle' => $def['subtitle'],
                'icon' => $def['icon'],
                'group' => $def['group'],
                'group_label' => $def['group_label'],
                'feature' => $def['feature'] ?? null,
                'allowed' => $allowed,
                'can_create' => $canCreate,
                'plan_badge' => $allowed ? null : ($def['plan_badge'] ?? null),
                'count' => $count,
            ];
        }

        return $items;
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function paginate(?User $user, string $key, ?string $search, int $perPage): array
    {
        $def = $this->definition($key);
        $this->assertAllowed($user, $def);

        $query = $this->scopedQuery($def);
        $search = trim((string) $search);

        if ($search !== '') {
            $columns = $def['search'] ?? [$def['title_attr'] ?? 'name'];
            $query->where(function (Builder $q) use ($columns, $search, $def) {
                $table = (new $def['model'])->getTable();
                foreach ($columns as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $q->{$method}($table.'.'.$column, 'like', '%'.$search.'%');
                }
            });
        }

        if (! empty($def['with'])) {
            $query->with($def['with']);
        }

        $paginator = $query
            ->latest('id')
            ->paginate(min(max($perPage, 1), 50));

        return [
            'data' => collect($paginator->items())
                ->map(fn (Model $model) => $this->mapRecord($key, $def, $model, false))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'title' => $def['title'],
                'can_create' => $this->canCreate($def),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function form(?User $user, string $key, ?int $id = null): array
    {
        $def = $this->definition($key);
        $this->assertAllowed($user, $def);

        if ($id === null && ! $this->canCreate($def)) {
            $quota = $def['quota'] ?? null;
            throw new HttpResponseException(response()->json([
                'message' => $quota
                    ? CompanySubscription::fullMessage($quota)
                    : 'Penambahan data tidak tersedia untuk modul ini.',
            ], 403));
        }

        $record = $id ? $this->findModel($user, $key, $id) : null;
        if ($id && ! $record) {
            throw new HttpResponseException(response()->json([
                'message' => 'Data tidak ditemukan.',
            ], 404));
        }

        $fields = [];
        foreach ($def['fields'] ?? [] as $field) {
            $row = [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['type'],
                'required' => (bool) ($field['required'] ?? false),
                'placeholder' => $field['placeholder'] ?? null,
            ];

            if (($field['type'] ?? '') === 'select') {
                $row['options'] = $key === 'simulasi' && ($field['options'] ?? '') === 'products'
                    ? $this->simulasiProductOptions()
                    : $this->options($field['options'] ?? []);
            }
            if (! empty($field['helper'])) {
                $row['helper'] = $field['helper'];
            }
            if (! empty($field['readonly'])) {
                $row['readonly'] = true;
            }
            if (! empty($field['section'])) {
                $row['section'] = $field['section'];
            }

            $fields[] = $row;
        }

        if ($record instanceof SimulasiProduk) {
            $fields = $this->ensureSimulasiProspectOption($fields, $record);
        }

        $payload = [
            'title' => ($id ? 'Edit ' : 'Tambah ').$def['title'],
            'can_create' => $id !== null || $this->canCreate($def),
            'fields' => $fields,
        ];

        if ($key === 'simulasi') {
            $payload['defaults'] = [
                'user_id' => $user?->id ? (string) $user->id : null,
            ];
            $payload['months'] = collect(MonthEnum::cases())
                ->map(fn (MonthEnum $month) => [
                    'value' => $month->value,
                    'label' => (string) $month->getLabel(),
                ])
                ->values()
                ->all();
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function store(?User $user, string $key, array $input): array
    {
        $def = $this->definition($key);
        $this->assertAllowed($user, $def);

        if (! $this->canCreate($def)) {
            $quota = $def['quota'] ?? null;
            throw new HttpResponseException(response()->json([
                'message' => $quota
                    ? CompanySubscription::fullMessage($quota)
                    : 'Penambahan data tidak tersedia untuk modul ini.',
            ], 403));
        }

        $data = $this->validated($def, $input);
        if ($key === 'simulasi') {
            $data['payment_simulation'] = $input['payment_simulation'] ?? [];
        }
        $data = $this->prepare($user, $key, $def, $data, null);

        $modelClass = $def['model'];
        /** @var Model $model */
        $model = $modelClass::query()->create($data);
        $this->afterSave($key, $model, true);

        return $this->detail($user, $key, (int) $model->getKey()) ?? $this->mapRecord($key, $def, $model->fresh(), true);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|null
     */
    public function update(?User $user, string $key, int $id, array $input): ?array
    {
        $def = $this->definition($key);
        $this->assertAllowed($user, $def);

        /** @var Model|null $model */
        $model = $this->scopedQuery($def)->find($id);
        if (! $model) {
            return null;
        }

        $data = $this->validated($def, $input, $id);
        if ($key === 'simulasi' && array_key_exists('payment_simulation', $input)) {
            $data['payment_simulation'] = $input['payment_simulation'];
        }
        $data = $this->prepare($user, $key, $def, $data, $model);
        $model->fill($data);
        $model->save();
        $this->afterSave($key, $model, false);

        return $this->detail($user, $key, $id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(?User $user, string $key, int $id): ?array
    {
        $def = $this->definition($key);
        $this->assertAllowed($user, $def);

        $query = $this->scopedQuery($def);
        if (! empty($def['with'])) {
            $query->with($def['with']);
        }
        if (! empty($def['detail_with'])) {
            $query->with($def['detail_with']);
        }

        /** @var Model|null $model */
        $model = $query->find($id);

        return $model ? $this->mapRecord($key, $def, $model, true) : null;
    }

    public function findModel(?User $user, string $key, int $id): ?Model
    {
        $def = $this->definition($key);
        $this->assertAllowed($user, $def);

        return $this->scopedQuery($def)->find($id);
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(string $key): array
    {
        $definitions = $this->definitions();
        if (! isset($definitions[$key])) {
            throw new HttpResponseException(response()->json([
                'message' => 'Modul tidak ditemukan.',
            ], 404));
        }

        return $definitions[$key];
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private function allows(?User $user, array $def): bool
    {
        if (UserVisibility::companyId($user) === null) {
            return false;
        }

        if (! empty($def['allowed_team'])) {
            if (ProFeatures::actorIsSuperAdmin()) {
                return true;
            }
            if (ProFeatures::allows(PricingPlans::FEATURE_ROLE_MANAGEMENT)) {
                return true;
            }
            $limit = CompanySubscription::seatLimit();

            return $limit === null || $limit > 1;
        }

        $feature = $def['feature'] ?? null;
        if ($feature === null) {
            return true;
        }

        return ProFeatures::allows($feature);
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private function assertAllowed(?User $user, array $def): void
    {
        if ($this->allows($user, $def)) {
            return;
        }

        $feature = $def['feature'] ?? PricingPlans::FEATURE_PROJECTS;

        throw new HttpResponseException(response()->json([
            'message' => CompanySubscription::upgradeMessage($feature),
        ], 403));
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private function canCreate(array $def): bool
    {
        if (($def['can_create'] ?? true) === false) {
            return false;
        }

        $quota = $def['quota'] ?? null;
        if ($quota) {
            return CompanySubscription::canCreate($quota);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private function scopedQuery(array $def): Builder
    {
        $modelClass = $def['model'];
        $query = $modelClass::query();
        $table = (new $modelClass)->getTable();
        $companyId = UserVisibility::companyId();

        if (! empty($def['company_scope']) && Schema::hasColumn($table, 'company_id')) {
            if ($companyId === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where($table.'.company_id', $companyId);
            }
        } elseif (ProFeatures::actorIsSuperAdmin() && Schema::hasColumn($table, 'company_id')) {
            if ($companyId === null) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where($table.'.company_id', $companyId);
            }
        }

        if (! empty($def['scope']) && is_callable($def['scope'])) {
            $def['scope']($query, $companyId);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validated(array $def, array $input, ?int $ignoreId = null): array
    {
        $companyId = UserVisibility::companyId();
        $rules = [];

        foreach ($def['fields'] ?? [] as $field) {
            $name = $field['name'];
            $rule = [];
            $rule[] = ($field['required'] ?? false) ? 'required' : 'nullable';

            switch ($field['type']) {
                case 'number':
                    $rule[] = 'numeric';
                    $rule[] = 'min:0';
                    break;
                case 'date':
                    $rule[] = 'date';
                    break;
                case 'toggle':
                    $rule[] = 'boolean';
                    break;
                case 'textarea':
                    $rule[] = 'string';
                    $rule[] = 'max:5000';
                    break;
                case 'email':
                    $rule[] = 'email';
                    $rule[] = 'max:255';
                    break;
                case 'select':
                    break;
                default:
                    $rule[] = 'string';
                    $rule[] = 'max:255';
                    break;
            }

            if (($field['type'] ?? '') === 'select' && is_string($field['options'] ?? null)) {
                $exists = $this->existsRule($field['options'], $companyId);
                if ($exists) {
                    $rule[] = $exists;
                }
            }

            if (! empty($field['in']) && is_array($field['in'])) {
                $rule[] = Rule::in($field['in']);
            }

            if (! empty($field['unique'])) {
                $unique = Rule::unique($field['unique'][0], $field['unique'][1] ?? $name);
                if ($companyId && Schema::hasColumn($field['unique'][0], 'company_id')) {
                    $unique->where('company_id', $companyId);
                }
                if ($ignoreId) {
                    $unique->ignore($ignoreId);
                }
                $rule[] = $unique;
            }

            $rules[$name] = $rule;
        }

        $validator = validator($input, $rules);
        $data = $validator->validate();

        foreach ($data as $key => $value) {
            if (is_string($value) && trim($value) === '') {
                $data[$key] = null;
            }
        }

        foreach ($def['fields'] ?? [] as $field) {
            $name = $field['name'];
            if (! array_key_exists($name, $data)) {
                continue;
            }
            $cast = $field['cast'] ?? match ($field['type']) {
                'number' => 'int',
                'toggle' => 'bool',
                default => null,
            };
            if ($cast === 'int' && $data[$name] !== null) {
                $data[$name] = (int) round((float) $data[$name]);
            }
            if ($cast === 'bool' && $data[$name] !== null) {
                $data[$name] = filter_var($data[$name], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $data;
    }

    private function existsRule(string $optionsKey, ?int $companyId): ?object
    {
        $table = match ($optionsKey) {
            'payment_methods' => 'payment_methods',
            'vendors' => 'vendors',
            'orders' => 'orders',
            'prospects' => 'prospects',
            'open_prospects' => 'prospects',
            'products' => 'products',
            'employees' => 'employees',
            'users' => 'users',
            'account_managers' => 'users',
            'categories' => 'categories',
            'nota_dinas' => 'nota_dinas',
            'piutangs' => 'piutangs',
            'document_categories' => 'document_categories',
            'sop_categories' => 'sop_categories',
            'documentation_categories' => 'documentation_categories',
            default => null,
        };

        if (! $table) {
            return null;
        }

        $rule = Rule::exists($table, 'id');
        if ($companyId && Schema::hasColumn($table, 'company_id')) {
            $rule->where('company_id', $companyId);
        }

        return $rule;
    }

    /**
     * @param  array<string, mixed>  $def
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepare(?User $user, string $key, array $def, array $data, ?Model $existing): array
    {
        unset($data['company_id'], $data['id']);

        $companyId = UserVisibility::companyId($user);
        $modelClass = $def['model'];
        $table = (new $modelClass)->getTable();

        if ($companyId && Schema::hasColumn($table, 'company_id')) {
            $data['company_id'] = $companyId;
        }

        return match ($key) {
            'products' => $this->prepareProduct($user, $data, $existing),
            'vendors' => $this->prepareVendor($user, $data, $existing),
            'categories' => $this->prepareCategory($data, $existing),
            'nota_dinas' => $this->prepareNotaDinas($user, $data, $existing),
            'nota_dinas_details' => $this->prepareNotaDinasDetail($data),
            'simulasi' => $this->prepareSimulasi($user, $data, $existing),
            'payment_methods' => $this->preparePaymentMethod($data),
            'piutangs' => $this->preparePiutang($user, $data, $existing),
            'pembayaran_piutangs' => $this->preparePembayaranPiutang($user, $data, $existing),
            'expenses' => $this->prepareExpense($data),
            'expense_ops' => $this->prepareNamedExpense($data, 'uang_keluar'),
            'pengeluaran_lains' => $this->prepareNamedExpense($data, 'uang_keluar'),
            'pendapatan_lains' => $this->preparePendapatanLain($data),
            'fixed_assets' => $this->prepareFixedAsset($data, $existing),
            'bank_statements' => $this->prepareBankStatement($data, $existing),
            'employees' => $this->prepareEmployee($data),
            'payrolls' => $this->preparePayroll($data),
            'documents' => $this->prepareDocument($user, $data, $existing),
            'sops' => $this->prepareSop($user, $data, $existing),
            'documentations' => $this->prepareDocumentation($data, $existing),
            'documentation_categories' => $this->prepareDocumentationCategory($data, $existing),
            'data_pribadis' => $this->prepareDataPribadi($data),
            'team' => $this->prepareTeam($user, $data, $existing),
            default => $data,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareProduct(?User $user, array $data, ?Model $existing): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Product::generateUniqueSlug((string) ($data['name'] ?? 'paket'));
        }
        if (! $existing) {
            $data['created_by'] = $user?->id;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['product_price'] = $data['product_price'] ?? ($data['price'] ?? 0);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareVendor(?User $user, array $data, ?Model $existing): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Vendor::generateUniqueSlug((string) ($data['name'] ?? 'vendor'));
        }
        if (! $existing) {
            $data['created_by'] = $user?->id;
            $data['status'] = $data['status'] ?? StatusVendor::VENDOR->value;
        }
        $data['harga_publish'] = (int) ($data['harga_publish'] ?? 0);
        $data['harga_vendor'] = (int) ($data['harga_vendor'] ?? 0);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareCategory(array $data, ?Model $existing): array
    {
        if (empty($data['slug'])) {
            $base = Str::slug((string) ($data['name'] ?? 'kategori')) ?: 'kategori';
            $slug = $base;
            $i = 1;
            while (Category::query()->where('slug', $slug)->when($existing, fn ($q) => $q->whereKeyNot($existing->getKey()))->exists()) {
                $slug = $base.'-'.$i++;
            }
            $data['slug'] = $slug;
        }
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareNotaDinas(?User $user, array $data, ?Model $existing): array
    {
        if (! $existing) {
            $kategori = strtoupper((string) ($data['kategori_nd'] ?? 'BIS'));
            $data['kategori_nd'] = $kategori;
            $data['no_nd'] = NotaDinas::generateNomorND($kategori);
            $data['pengirim_id'] = $user?->id;
            $data['penerima_id'] = $data['penerima_id'] ?? $user?->id;
            $data['status'] = $data['status'] ?? 'draft';
            $data['sifat'] = $data['sifat'] ?? 'biasa';
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareNotaDinasDetail(array $data): array
    {
        $data['jenis_pengeluaran'] = $data['jenis_pengeluaran'] ?? 'lain';
        $data['status_invoice'] = $data['status_invoice'] ?? 'belum dibayar';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareSimulasi(?User $user, array $data, ?Model $existing): array
    {
        unset($data['customer_name']);

        $product = ! empty($data['product_id']) ? Product::query()->find($data['product_id']) : null;
        [$totalPrice, $penambahan, $pengurangan] = $product
            ? $this->productPricing($product)
            : [0, 0, 0];

        $promo = (int) ($data['promo'] ?? $existing?->promo ?? 0);
        $grand = max(0, $totalPrice + $penambahan - $promo - $pengurangan);

        $data['total_price'] = $totalPrice;
        $data['penambahan'] = $penambahan;
        $data['pengurangan'] = $pengurangan;
        $data['promo'] = $promo;
        $data['grand_total'] = $grand;
        $data['payment_dp_amount'] = (int) ($data['payment_dp_amount'] ?? 0);

        $terms = $data['payment_simulation'] ?? [];
        if (is_string($terms)) {
            $decoded = json_decode($terms, true);
            $terms = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($terms)) {
            $terms = [];
        }

        $normalized = [];
        $termsTotal = 0;
        foreach ($terms as $term) {
            if (! is_array($term)) {
                continue;
            }
            $nominal = (int) preg_replace('/[^\d]/', '', (string) ($term['nominal'] ?? 0));
            $normalized[] = [
                'persen' => $term['persen'] ?? null,
                'nominal' => $nominal,
                'bulan' => $term['bulan'] ?? null,
                'tahun' => isset($term['tahun']) ? (int) $term['tahun'] : (int) now()->year,
            ];
            $termsTotal += $nominal;
        }
        $data['payment_simulation'] = $normalized;
        $data['total_simulation'] = $data['payment_dp_amount'] + $termsTotal;

        if (! empty($data['notes']) && ! str_contains((string) $data['notes'], '<')) {
            $data['notes'] = '<p>'.e(trim((string) $data['notes'])).'</p>';
        }

        if (! $existing) {
            $data['user_id'] = $data['user_id'] ?? $user?->id;
            $prospect = ! empty($data['prospect_id']) ? Prospect::query()->find($data['prospect_id']) : null;
            $base = $prospect?->name_event ?: ($product?->name ?: 'simulasi');
            $data['slug'] = SimulasiProduk::generateUniqueSlug((string) $base);
        }

        return $data;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function productPricing(Product $product): array
    {
        $totalPrice = (int) ($product->product_price ?? 0);
        $penambahan = (int) ($product->penambahan_publish ?? 0);
        $pengurangan = (int) ($product->pengurangan ?? 0);

        if ($totalPrice === 0) {
            $totalPrice = (int) $product->items()->sum('price_public');
        }
        if ($penambahan === 0) {
            $penambahan = (int) $product->penambahanHarga()->sum('harga_publish');
        }
        if ($pengurangan === 0) {
            $pengurangan = (int) $product->pengurangans()->sum('amount');
        }

        return [$totalPrice, $penambahan, $pengurangan];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function simulasiProductOptions(): array
    {
        return Product::query()
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                [$total, $add, $cut] = $this->productPricing($product);

                return [
                    'value' => (string) $product->id,
                    'label' => (string) $product->name,
                    'total_price' => $total,
                    'penambahan' => $add,
                    'pengurangan' => $cut,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function accountManagerOptions(): array
    {
        $query = User::query();
        UserVisibility::constrainUsersQuery($query);

        $amQuery = (clone $query)->role('Account Manager');
        if ($amQuery->exists()) {
            $query->role('Account Manager');
        }

        return $query->orderBy('name')->get(['id', 'name'])->map(fn (User $row) => [
            'value' => (string) $row->id,
            'label' => (string) $row->name,
        ])->values()->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePaymentMethod(array $data): array
    {
        $data['is_cash'] = (bool) ($data['is_cash'] ?? false);
        $data['opening_balance'] = (int) ($data['opening_balance'] ?? 0);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePiutang(?User $user, array $data, ?Model $existing): array
    {
        if (! $existing) {
            $data['nomor_piutang'] = Piutang::generateNomorPiutang();
            $data['dibuat_oleh'] = $user?->id;
            $data['status'] = StatusPiutang::AKTIF->value;
            $data['jenis_piutang'] = $data['jenis_piutang'] ?? JenisPiutang::BISNIS->value;
            $data['sudah_dibayar'] = 0;
        }
        $pokok = (int) ($data['jumlah_pokok'] ?? 0);
        $bunga = (int) ($data['persentase_bunga'] ?? 0);
        $total = $pokok + (int) round($pokok * $bunga / 100);
        $data['total_piutang'] = $total;
        $paid = (int) ($existing?->sudah_dibayar ?? 0);
        $data['sisa_piutang'] = max(0, $total - $paid);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePembayaranPiutang(?User $user, array $data, ?Model $existing): array
    {
        if (! $existing) {
            $data['nomor_pembayaran'] = PembayaranPiutang::generateNomorPembayaran();
            $data['dibayar_oleh'] = $user?->id;
            $data['status'] = $data['status'] ?? 'confirmed';
            $data['tanggal_dicatat'] = now()->toDateString();
        }
        $jumlah = (int) ($data['jumlah_pembayaran'] ?? 0);
        $bunga = (int) ($data['jumlah_bunga'] ?? 0);
        $denda = (int) ($data['denda'] ?? 0);
        $data['total_pembayaran'] = $jumlah + $bunga + $denda;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareExpense(array $data): array
    {
        $data['kategori_transaksi'] = $data['kategori_transaksi'] ?? 'uang_keluar';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareNamedExpense(array $data, string $kategori): array
    {
        $data['kategori_transaksi'] = $data['kategori_transaksi'] ?? $kategori;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePendapatanLain(array $data): array
    {
        $data['kategori_transaksi'] = $data['kategori_transaksi'] ?? 'uang_masuk';

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareFixedAsset(array $data, ?Model $existing): array
    {
        if (! $existing && empty($data['asset_code'])) {
            $seq = FixedAsset::query()->withTrashed()->count() + 1;
            $data['asset_code'] = 'FA-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        }
        $price = (int) ($data['purchase_price'] ?? 0);
        $data['current_book_value'] = $data['current_book_value'] ?? $price;
        $data['accumulated_depreciation'] = $data['accumulated_depreciation'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['depreciation_method'] = $data['depreciation_method'] ?? 'STRAIGHT_LINE';
        $data['useful_life_years'] = $data['useful_life_years'] ?? 5;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareBankStatement(array $data, ?Model $existing): array
    {
        if (! $existing) {
            $data['status'] = $data['status'] ?? 'draft';
            $data['source_type'] = $data['source_type'] ?? 'manual';
            $data['uploaded_at'] = now();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareEmployee(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayroll(array $data): array
    {
        $pokok = (int) ($data['gaji_pokok'] ?? 0);
        $tunjangan = (int) ($data['tunjangan'] ?? 0);
        $pengurangan = (int) ($data['pengurangan'] ?? 0);
        $monthly = $pokok + $tunjangan - $pengurangan;
        $data['monthly_salary'] = $monthly;
        $data['annual_salary'] = $monthly * 12;
        $data['bonus'] = (int) ($data['bonus'] ?? 0);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareDocument(?User $user, array $data, ?Model $existing): array
    {
        if (! $existing) {
            $data['created_by'] = $user?->id;
            $data['status'] = $data['status'] ?? 'draft';
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareSop(?User $user, array $data, ?Model $existing): array
    {
        if (! $existing) {
            $data['created_by'] = $user?->id;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['version'] = $data['version'] ?? '1.0';
        }
        $data['updated_by'] = $user?->id;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareDocumentation(array $data, ?Model $existing): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug((string) ($data['title'] ?? 'artikel')).'-'.Str::lower(Str::random(4));
        }
        $data['is_published'] = $data['is_published'] ?? true;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareDocumentationCategory(array $data, ?Model $existing): array
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug((string) ($data['name'] ?? 'kategori'));
        }
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareDataPribadi(array $data): array
    {
        unset($data['gaji'], $data['gaji_encrypted']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareTeam(?User $user, array $data, ?Model $existing): array
    {
        if ($existing) {
            unset($data['password']);
        }
        if (! $existing) {
            $data['created_by'] = $user?->id;
            $data['status'] = $data['status'] ?? 'active';
            $data['email_verified_at'] = now();
        }

        return $data;
    }

    private function afterSave(string $key, Model $model, bool $created): void
    {
        if ($key === 'piutangs' && $model instanceof Piutang) {
            $model->hitungTotalPiutang();
        }

        if ($key === 'pembayaran_piutangs' && $model instanceof PembayaranPiutang) {
            $piutang = $model->piutang;
            if ($piutang) {
                $piutang->sudah_dibayar = (int) $piutang->pembayaranPiutangs()->sum('jumlah_pembayaran');
                $piutang->hitungTotalPiutang();
                $piutang->updateStatus();
            }
        }

        if ($key === 'team' && $created && $model instanceof User) {
            try {
                $model->assignRole('pengunjung');
            } catch (\Throwable) {
                // Role may not exist on a given tenant install.
            }
        }
    }

    /**
     * @param  array<string, mixed>  $def
     * @return array<string, mixed>
     */
    private function mapRecord(string $key, array $def, Model $model, bool $detailed): array
    {
        $titleAttr = $def['title_attr'] ?? 'name';
        $status = $this->scalar($model->{$def['status_attr'] ?? 'status'} ?? null);
        $amountAttr = $def['amount_attr'] ?? null;

        $title = $this->stringValue($this->value($model, $titleAttr));

        $payload = [
            'id' => (int) $model->getKey(),
            'title' => $title !== '' ? $title : ($def['title'].' #'.$model->getKey()),
            'subtitle' => $this->subtitle($def, $model),
            'amount' => $amountAttr ? (int) ($this->value($model, $amountAttr) ?? 0) : null,
            'status' => $status,
            'date' => $this->dateValue($model, $def['date_attr'] ?? null),
        ];

        if ($detailed) {
            $payload['fields'] = $this->detailFields($def, $model);
            $payload['children'] = $this->children($key, $model);
            $payload['values'] = $this->formValues($def, $model);
            if ($key === 'simulasi' && $model instanceof SimulasiProduk) {
                $payload['payment_simulation'] = collect($model->payment_simulation ?? [])
                    ->map(function ($term) {
                        $term = is_array($term) ? $term : [];

                        return [
                            'persen' => isset($term['persen']) ? (string) $term['persen'] : null,
                            'nominal' => (int) ($term['nominal'] ?? 0),
                            'bulan' => $term['bulan'] ?? null,
                            'tahun' => isset($term['tahun']) ? (int) $term['tahun'] : null,
                        ];
                    })
                    ->values()
                    ->all();
            }
            if ($key === 'bank_statements' && $model instanceof BankStatement) {
                $this->applyBankStatementDetail($payload, $model);
            }
        }

        if ($key === 'products' && $model instanceof Product) {
            try {
                if ($detailed) {
                    $serialized = app(FinanceSummaryService::class)->serializeProductDetail($model);
                    $payload['product'] = $serialized;
                    $this->applyProductTotals($payload, $serialized);
                } else {
                    $this->applyLiveProductAmount($payload, $model);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $payload;
    }

    /**
     * List paket: amount = Total Paket (final_publish), bukan kolom `price` / Subtotal.
     *
     * @param  array<string, mixed>  $payload
     */
    private function applyLiveProductAmount(array &$payload, Product $product): void
    {
        $pricing = ProductPricingCalculator::calculateForProduct($product);
        $payload['amount'] = (int) ($pricing['final_publish'] ?? 0);
    }

    /**
     * Header & field harga mengikuti Total Paket (final_publish), bukan kolom `price` mentah.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $serialized
     */
    private function applyProductTotals(array &$payload, array $serialized): void
    {
        $totalPublish = (int) ($serialized['pricing']['total_publish'] ?? $serialized['price'] ?? 0);
        $totalVendor = (int) ($serialized['pricing']['total_vendor'] ?? $serialized['vendor_price'] ?? 0);
        $payload['amount'] = $totalPublish;

        $payload['fields'] = array_map(function (array $field) use ($totalPublish, $totalVendor) {
            $label = strtolower(trim((string) ($field['label'] ?? '')));
            if ($label === 'harga') {
                return [
                    'label' => 'Total Paket',
                    'value' => $this->displayValue($totalPublish, 'money'),
                ];
            }
            if ($label === 'harga vendor') {
                return [
                    'label' => 'Total Vendor',
                    'value' => $this->displayValue($totalVendor, 'money'),
                ];
            }

            return $field;
        }, $payload['fields'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $def
     */
    private function subtitle(array $def, Model $model): ?string
    {
        if (! empty($def['subtitle_attr'])) {
            $value = $this->stringValue($this->value($model, $def['subtitle_attr']));

            return $value !== '' ? $value : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $def
     * @return list<array{label: string, value: string|null}>
     */
    private function detailFields(array $def, Model $model): array
    {
        $rows = [];
        foreach ($def['detail'] ?? [] as $item) {
            $rows[] = [
                'label' => $item['label'],
                'value' => $this->displayValue($this->value($model, $item['attr']), $item['format'] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $def
     * @return array<string, string>
     */
    private function formValues(array $def, Model $model): array
    {
        $values = [];
        foreach ($def['fields'] ?? [] as $field) {
            $name = $field['name'] ?? null;
            if (! is_string($name) || $name === '') {
                continue;
            }

            $raw = $model->{$name} ?? null;
            if ($raw === null || $raw === '') {
                continue;
            }

            if ($name === 'notes') {
                $plain = $this->plainText((string) $raw);
                if ($plain) {
                    $values[$name] = $plain;
                }

                continue;
            }

            if ($raw instanceof \DateTimeInterface) {
                $values[$name] = $raw->format('Y-m-d');

                continue;
            }

            if (is_bool($raw)) {
                $values[$name] = $raw ? '1' : '0';

                continue;
            }

            $values[$name] = (string) $raw;
        }

        return $values;
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @return list<array<string, mixed>>
     */
    private function ensureSimulasiProspectOption(array $fields, SimulasiProduk $record): array
    {
        $record->loadMissing('prospect:id,name_event');
        if (! $record->prospect_id || ! $record->prospect) {
            return $fields;
        }

        foreach ($fields as $index => $field) {
            if (($field['name'] ?? '') !== 'prospect_id') {
                continue;
            }

            $options = $field['options'] ?? [];
            $exists = collect($options)->contains(
                fn ($option) => (string) ($option['value'] ?? '') === (string) $record->prospect_id
            );
            if (! $exists) {
                array_unshift($options, [
                    'value' => (string) $record->prospect_id,
                    'label' => (string) $record->prospect->name_event,
                ]);
                $fields[$index]['options'] = $options;
            }
        }

        return $fields;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function children(string $key, Model $model): array
    {
        return match ($key) {
            'nota_dinas' => $model instanceof NotaDinas
                ? $model->details()->with('vendor:id,name')->get()->map(function (NotaDinasDetail $detail) {
                    return [
                        'id' => $detail->id,
                        'title' => $detail->keperluan ?: ($detail->vendor?->name ?: 'Detail'),
                        'subtitle' => $detail->vendor?->name,
                        'amount' => (int) $detail->jumlah_transfer,
                        'status' => $detail->status_invoice,
                    ];
                })->values()->all()
                : [],
            'piutangs' => $model instanceof Piutang
                ? $model->pembayaranPiutangs()->latest('id')->get()->map(function (PembayaranPiutang $bayar) {
                    return [
                        'id' => $bayar->id,
                        'title' => $bayar->nomor_pembayaran ?: 'Pembayaran',
                        'subtitle' => optional($bayar->tanggal_pembayaran)?->toDateString(),
                        'amount' => (int) $bayar->total_pembayaran,
                        'status' => $bayar->status,
                    ];
                })->values()->all()
                : [],
            'fixed_assets' => $model instanceof FixedAsset
                ? $model->depreciations()->latest('id')->get()->map(function (AssetDepreciation $row) {
                    return [
                        'id' => $row->id,
                        'title' => 'Penyusutan',
                        'subtitle' => optional($row->depreciation_date)?->toDateString(),
                        'amount' => (int) $row->depreciation_amount,
                        'status' => $row->is_adjustment ? 'adjustment' : null,
                    ];
                })->values()->all()
                : [],
            'bank_statements' => $model instanceof BankStatement
                ? $model->transactions()->latest('transaction_date')->latest('id')->limit(100)->get()->map(function (BankTransaction $row) {
                    $debit = (int) $row->debit_amount;
                    $credit = (int) $row->credit_amount;
                    $fields = [];
                    if ($debit > 0) {
                        $fields[] = ['label' => 'Debit', 'value' => $this->displayValue($debit, 'money')];
                    }
                    if ($credit > 0) {
                        $fields[] = ['label' => 'Kredit', 'value' => $this->displayValue($credit, 'money')];
                    }
                    if ($ref = $this->stringValue($row->reference_number)) {
                        $fields[] = ['label' => 'Referensi', 'value' => $ref];
                    }
                    if ($row->balance !== null && $row->balance !== '') {
                        $fields[] = ['label' => 'Saldo', 'value' => $this->displayValue($row->balance, 'money')];
                    }

                    return [
                        'id' => (int) $row->id,
                        'title' => $this->stringValue($row->description)
                            ?: ($this->stringValue($row->reference_number) ?: 'Transaksi bank'),
                        'subtitle' => optional($row->transaction_date)?->format('d M Y'),
                        'amount' => $credit > 0 ? $credit : $debit,
                        'status' => $row->is_matched ? 'matched' : 'unmatched',
                        'fields' => $fields,
                    ];
                })->values()->all()
                : [],
            'simulasi' => $model instanceof SimulasiProduk
                ? collect($model->payment_simulation ?? [])->values()->map(function ($term, $index) {
                    $term = is_array($term) ? $term : [];
                    $bulan = trim((string) ($term['bulan'] ?? 'Termin'));
                    $tahun = (string) ($term['tahun'] ?? '');

                    return [
                        'id' => $index + 1,
                        'title' => trim($bulan.' '.$tahun) ?: 'Termin',
                        'subtitle' => isset($term['persen']) ? $term['persen'].'%' : null,
                        'amount' => (int) ($term['nominal'] ?? 0),
                    ];
                })->all()
                : [],
            'products' => $model instanceof Product
                ? $this->productFacilityChildren($model)
                : [],
            default => [],
        };
    }

    /**
     * Lengkapi payload detail rekonsiliasi (saldo, debit/kredit, rekening, label status).
     *
     * @param  array<string, mixed>  $payload
     */
    private function applyBankStatementDetail(array &$payload, BankStatement $model): void
    {
        $model->loadMissing(['paymentMethod:id,name,bank_name,no_rekening']);

        $pm = $model->paymentMethod;
        $rekeningParts = array_values(array_filter([
            $this->stringValue($pm?->bank_name),
            $this->stringValue($pm?->no_rekening),
            $this->stringValue($pm?->name),
        ], fn (string $value) => $value !== ''));
        $rekening = implode(' · ', $rekeningParts);

        $customTitle = $this->stringValue($model->title);
        if ($customTitle === '' && $rekening !== '') {
            $start = optional($model->period_start)?->format('d M Y');
            $end = optional($model->period_end)?->format('d M Y');
            $period = ($start && $end) ? "{$start} – {$end}" : ($start ?: $end);
            $payload['title'] = $period ? "{$rekening} · {$period}" : $rekening;
        }

        if ($rekening !== '') {
            $payload['subtitle'] = $rekening;
        }

        $statusLabels = BankStatement::getStatusOptions();
        $reconLabels = BankStatement::getReconciliationStatusOptions();
        $sourceLabels = BankStatement::getSourceTypeOptions();

        $fields = [
            ['label' => 'Judul', 'value' => $customTitle !== '' ? $customTitle : null],
            ['label' => 'Rekening', 'value' => $rekening !== '' ? $rekening : null],
            ['label' => 'Cabang', 'value' => $this->stringValue($model->branch) ?: null],
            ['label' => 'Mulai', 'value' => $this->displayValue($model->period_start, 'date')],
            ['label' => 'Selesai', 'value' => $this->displayValue($model->period_end, 'date')],
            ['label' => 'Saldo awal', 'value' => $this->displayValue($model->opening_balance ?? 0, 'money')],
            ['label' => 'Saldo akhir', 'value' => $this->displayValue($model->closing_balance ?? 0, 'money')],
            ['label' => 'Total debit', 'value' => $this->displayValue($model->tot_debit ?? 0, 'money')],
            ['label' => 'Jumlah debit', 'value' => $model->no_of_debit !== null ? ((int) $model->no_of_debit).' transaksi' : null],
            ['label' => 'Total kredit', 'value' => $this->displayValue($model->tot_credit ?? 0, 'money')],
            ['label' => 'Jumlah kredit', 'value' => $model->no_of_credit !== null ? ((int) $model->no_of_credit).' transaksi' : null],
            ['label' => 'Total mutasi', 'value' => $model->total_records !== null ? ((int) $model->total_records).' baris' : null],
            [
                'label' => 'Sumber',
                'value' => $sourceLabels[(string) $model->source_type] ?? ($this->stringValue($model->source_type) ?: null),
            ],
            ['label' => 'File', 'value' => $this->stringValue($model->original_filename) ?: null],
            [
                'label' => 'Status',
                'value' => $statusLabels[(string) $model->status] ?? ($this->stringValue($model->status) ?: null),
            ],
            [
                'label' => 'Status rekonsiliasi',
                'value' => $reconLabels[(string) $model->reconciliation_status] ?? ($this->stringValue($model->reconciliation_status) ?: null),
            ],
            ['label' => 'Catatan', 'value' => $this->plainText($model->description)],
        ];

        $payload['fields'] = array_values(array_filter(
            $fields,
            fn (array $row) => filled($row['value'] ?? null)
        ));
        $payload['children_title'] = 'Mutasi rekening';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productFacilityChildren(Product $product): array
    {
        $product->loadMissing(['items.vendor']);

        return $product->items->map(function ($item) {
            $qty = max(1, (int) ($item->quantity ?? 1));
            $hargaPublish = (int) ($item->harga_publish ?? 0);
            $hargaVendor = (int) ($item->harga_vendor ?? 0);
            $linePublic = (int) ($item->price_public ?: $hargaPublish * $qty);
            $lineVendor = (int) ($item->total_price ?: $hargaVendor * $qty);
            if ($hargaVendor > 0 && $hargaPublish !== $hargaVendor && $lineVendor === $linePublic) {
                $lineVendor = $hargaVendor * $qty;
            }

            $fields = [];
            if ($description = $this->plainText($item->description)) {
                $fields[] = ['label' => 'Fasilitas', 'value' => $description];
            }
            if ($qty > 1) {
                $fields[] = ['label' => 'Qty', 'value' => (string) $qty];
            }
            if ($lineVendor > 0) {
                $fields[] = ['label' => 'Harga vendor', 'value' => $this->displayValue($lineVendor, 'money')];
            }

            return [
                'id' => (int) $item->id,
                'title' => $item->vendor?->name ?: 'Fasilitas',
                'subtitle' => $qty > 1 ? $qty.'×' : null,
                'amount' => $linePublic,
                'vendor_id' => $item->vendor_id ? (int) $item->vendor_id : null,
                'fields' => $fields,
            ];
        })->values()->all();
    }

    /**
     * @param  array<int|string, string>|string  $source
     * @return list<array{value: string, label: string}>
     */
    private function options(array|string $source): array
    {
        if (is_array($source) && $this->isAssoc($source)) {
            $rows = [];
            foreach ($source as $value => $label) {
                $rows[] = ['value' => (string) $value, 'label' => (string) $label];
            }

            return $rows;
        }

        if (! is_string($source)) {
            return [];
        }

        $companyId = UserVisibility::companyId();

        $map = function ($query, string $labelAttr = 'name') {
            return $query->orderBy($labelAttr)->get(['id', $labelAttr])->map(fn ($row) => [
                'value' => (string) $row->id,
                'label' => (string) $row->{$labelAttr},
            ])->values()->all();
        };

        return match ($source) {
            'payment_methods' => $map(PaymentMethod::query(), 'name'),
            'vendors' => $map(Vendor::query(), 'name'),
            'orders' => Order::query()->with('prospect:id,name_event')->latest('id')->limit(100)->get()->map(fn (Order $order) => [
                'value' => (string) $order->id,
                'label' => trim(($order->no_kontrak ? $order->no_kontrak.' · ' : '').($order->prospect?->name_event ?: 'Proyek #'.$order->id)),
            ])->values()->all(),
            'prospects' => $map(Prospect::query(), 'name_event'),
            'open_prospects' => Prospect::query()
                ->where(function ($query) {
                    $query->whereDoesntHave('orders', function ($orderQuery) {
                        $orderQuery->whereNotNull('status');
                    });
                })
                ->orderBy('name_event')
                ->get(['id', 'name_event'])
                ->map(fn (Prospect $row) => [
                    'value' => (string) $row->id,
                    'label' => (string) $row->name_event,
                ])
                ->values()
                ->all(),
            'products' => $map(Product::query(), 'name'),
            'account_managers' => $this->accountManagerOptions(),
            'employees' => $map(Employee::query(), 'name'),
            'users' => User::query()
                ->when(
                    $companyId && Schema::hasColumn('users', 'company_id'),
                    fn ($q) => $q->where('company_id', $companyId)
                )
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $row) => ['value' => (string) $row->id, 'label' => $row->name])
                ->values()
                ->all(),
            'categories' => $map(Category::query(), 'name'),
            'nota_dinas' => NotaDinas::query()->latest('id')->limit(100)->get(['id', 'no_nd', 'hal'])->map(fn (NotaDinas $row) => [
                'value' => (string) $row->id,
                'label' => trim($row->no_nd.' · '.$row->hal),
            ])->values()->all(),
            'piutangs' => Piutang::query()->latest('id')->limit(100)->get(['id', 'nomor_piutang', 'nama_debitur'])->map(fn (Piutang $row) => [
                'value' => (string) $row->id,
                'label' => trim($row->nomor_piutang.' · '.$row->nama_debitur),
            ])->values()->all(),
            'document_categories' => DocumentCategory::query()
                ->when(
                    $companyId && Schema::hasColumn((new DocumentCategory)->getTable(), 'company_id'),
                    fn ($q) => $q->where('company_id', $companyId)
                )
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($row) => ['value' => (string) $row->id, 'label' => (string) $row->name])
                ->values()
                ->all(),
            'sop_categories' => $map(SopCategory::query(), 'name'),
            'documentation_categories' => $map(DocumentationCategory::query(), 'name'),
            default => [],
        };
    }

    private function isAssoc(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function value(Model $model, string $attr): mixed
    {
        if (! str_contains($attr, '.')) {
            return $model->{$attr} ?? null;
        }

        $current = $model;
        foreach (explode('.', $attr) as $segment) {
            if ($current === null) {
                return null;
            }
            $current = $current->{$segment} ?? null;
        }

        return $current;
    }

    private function scalar(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }
        if (is_bool($value)) {
            return $value ? 'aktif' : 'nonaktif';
        }
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function stringValue(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return method_exists($value, 'getLabel') ? (string) $value->getLabel() : (string) $value->value;
        }
        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        return trim((string) ($value ?? ''));
    }

    private function dateValue(Model $model, ?string $attr): ?string
    {
        if (! $attr) {
            return optional($model->created_at)?->toDateString();
        }

        $value = $this->value($model, $attr);
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $value ? (string) $value : null;
    }

    private function displayValue(mixed $value, ?string $format): ?string
    {
        if ($format === 'money') {
            return 'Rp '.number_format((int) $value, 0, ',', '.');
        }
        if ($format === 'date' && $value instanceof \DateTimeInterface) {
            return $value->format('d M Y');
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d M Y');
        }
        $text = $this->stringValue($value);
        if ($format === 'html' || $this->looksLikeHtml($text)) {
            $text = $this->plainText($text) ?? '';
        }

        return $text !== '' ? $text : null;
    }

    private function looksLikeHtml(string $text): bool
    {
        return (bool) preg_match('/<\/?[a-z][\s\S]*>/i', $text);
    }

    private function plainText(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\/(p|div|li|h[1-6]|tr)>/i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = trim($text);

        return $text === '' ? null : $text;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            'products' => [
                'model' => Product::class,
                'feature' => PricingPlans::FEATURE_PROJECTS,
                'quota' => CompanySubscription::RESOURCE_PRODUCTS,
                'title' => 'Paket',
                'subtitle' => 'Katalog paket wedding',
                'icon' => 'shippingbox.fill',
                'group' => 'penjualan',
                'group_label' => 'Penjualan',
                'title_attr' => 'name',
                'subtitle_attr' => 'category.name',
                'amount_attr' => 'price',
                'status_attr' => 'is_active',
                'search' => ['name', 'slug'],
                'with' => [
                    'category:id,name',
                    'items',
                    'pengurangans',
                    'penambahanHarga',
                ],
                'detail_with' => [
                    'items.vendor.category:id,name',
                    'pengurangans',
                    'penambahanHarga.vendor.category:id,name',
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama paket', 'type' => 'text', 'required' => true],
                    ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'options' => 'categories', 'cast' => 'int'],
                    ['name' => 'price', 'label' => 'Harga jual', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'pax', 'label' => 'Pax', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'Kategori', 'attr' => 'category.name'],
                    ['label' => 'Harga', 'attr' => 'price', 'format' => 'money'],
                    ['label' => 'Harga vendor', 'attr' => 'product_price', 'format' => 'money'],
                    ['label' => 'Pax', 'attr' => 'pax'],
                    ['label' => 'Akad (pax)', 'attr' => 'pax_akad'],
                    ['label' => 'Deskripsi', 'attr' => 'description'],
                ],
            ],
            'vendors' => [
                'model' => Vendor::class,
                'feature' => PricingPlans::FEATURE_PROJECTS,
                'quota' => CompanySubscription::RESOURCE_VENDORS,
                'title' => 'Vendor',
                'subtitle' => 'Mitra dan supplier',
                'icon' => 'storefront.fill',
                'group' => 'penjualan',
                'group_label' => 'Penjualan',
                'title_attr' => 'name',
                'subtitle_attr' => 'pic_name',
                'amount_attr' => 'harga_publish',
                'status_attr' => 'status',
                'search' => ['name', 'pic_name', 'phone'],
                'with' => ['category:id,name'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama vendor', 'type' => 'text', 'required' => true],
                    ['name' => 'pic_name', 'label' => 'Nama PIC', 'type' => 'text'],
                    ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text'],
                    ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
                    ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'options' => 'categories', 'cast' => 'int'],
                    ['name' => 'harga_publish', 'label' => 'Harga publish', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'harga_vendor', 'label' => 'Harga vendor', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'bank_name', 'label' => 'Bank', 'type' => 'text'],
                    ['name' => 'account_holder', 'label' => 'Nama rekening', 'type' => 'text'],
                    ['name' => 'bank_account', 'label' => 'Nomor rekening', 'type' => 'text'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'PIC', 'attr' => 'pic_name'],
                    ['label' => 'Telepon', 'attr' => 'phone'],
                    ['label' => 'Alamat', 'attr' => 'address'],
                    ['label' => 'Kategori', 'attr' => 'category.name'],
                    ['label' => 'Harga publish', 'attr' => 'harga_publish', 'format' => 'money'],
                    ['label' => 'Harga vendor', 'attr' => 'harga_vendor', 'format' => 'money'],
                    ['label' => 'Bank', 'attr' => 'bank_name'],
                    ['label' => 'Rekening', 'attr' => 'bank_account'],
                ],
            ],
            'categories' => [
                'model' => Category::class,
                'feature' => PricingPlans::FEATURE_PROJECTS,
                'quota' => CompanySubscription::RESOURCE_CATEGORIES,
                'title' => 'Kategori',
                'subtitle' => 'Kategori paket dan vendor',
                'icon' => 'square.grid.2x2.fill',
                'group' => 'penjualan',
                'group_label' => 'Penjualan',
                'company_scope' => true,
                'title_attr' => 'name',
                'subtitle_attr' => 'description',
                'status_attr' => 'is_active',
                'search' => ['name'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama kategori', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'Deskripsi', 'attr' => 'description'],
                ],
            ],
            'nota_dinas' => [
                'model' => NotaDinas::class,
                'feature' => PricingPlans::FEATURE_NOTA_DINAS,
                'title' => 'Nota Dinas',
                'subtitle' => 'Tracking pengeluaran',
                'icon' => 'doc.text.fill',
                'group' => 'penjualan',
                'group_label' => 'Penjualan',
                'title_attr' => 'no_nd',
                'subtitle_attr' => 'hal',
                'date_attr' => 'tanggal',
                'status_attr' => 'status',
                'search' => ['no_nd', 'hal'],
                'with' => ['pengirim:id,name'],
                'detail_with' => ['details.vendor:id,name'],
                'fields' => [
                    ['name' => 'kategori_nd', 'label' => 'Kategori', 'type' => 'select', 'required' => true, 'options' => ['BIS' => 'Bisnis', 'OPS' => 'Operasional', 'LAIN' => 'Lain-lain'], 'in' => ['BIS', 'OPS', 'LAIN']],
                    ['name' => 'tanggal', 'label' => 'Tanggal', 'type' => 'date', 'required' => true],
                    ['name' => 'hal', 'label' => 'Hal / perihal', 'type' => 'text', 'required' => true],
                    ['name' => 'sifat', 'label' => 'Sifat', 'type' => 'select', 'options' => ['biasa' => 'Biasa', 'segera' => 'Segera', 'rahasia' => 'Rahasia'], 'in' => ['biasa', 'segera', 'rahasia']],
                    ['name' => 'catatan', 'label' => 'Catatan', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Nomor', 'attr' => 'no_nd'],
                    ['label' => 'Perihal', 'attr' => 'hal'],
                    ['label' => 'Kategori', 'attr' => 'kategori_nd'],
                    ['label' => 'Tanggal', 'attr' => 'tanggal', 'format' => 'date'],
                    ['label' => 'Status', 'attr' => 'status'],
                    ['label' => 'Pengirim', 'attr' => 'pengirim.name'],
                    ['label' => 'Catatan', 'attr' => 'catatan'],
                ],
            ],
            'nota_dinas_details' => [
                'model' => NotaDinasDetail::class,
                'feature' => PricingPlans::FEATURE_NOTA_DINAS,
                'title' => 'Detail Nota Dinas',
                'subtitle' => 'Item transfer nota dinas',
                'icon' => 'list.bullet.rectangle.fill',
                'group' => 'penjualan',
                'group_label' => 'Penjualan',
                'title_attr' => 'keperluan',
                'subtitle_attr' => 'vendor.name',
                'amount_attr' => 'jumlah_transfer',
                'status_attr' => 'status_invoice',
                'search' => ['keperluan', 'invoice_number'],
                'with' => ['vendor:id,name', 'notaDinas:id,no_nd,hal'],
                'fields' => [
                    ['name' => 'nota_dinas_id', 'label' => 'Nota dinas', 'type' => 'select', 'required' => true, 'options' => 'nota_dinas', 'cast' => 'int'],
                    ['name' => 'vendor_id', 'label' => 'Vendor', 'type' => 'select', 'options' => 'vendors', 'cast' => 'int'],
                    ['name' => 'keperluan', 'label' => 'Keperluan', 'type' => 'text', 'required' => true],
                    ['name' => 'jumlah_transfer', 'label' => 'Jumlah transfer', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'jenis_pengeluaran', 'label' => 'Jenis', 'type' => 'select', 'options' => ['lain' => 'Lain-lain', 'operasional' => 'Operasional', 'wedding' => 'Wedding'], 'in' => ['lain', 'operasional', 'wedding']],
                    ['name' => 'bank_name', 'label' => 'Bank', 'type' => 'text'],
                    ['name' => 'bank_account', 'label' => 'Nomor rekening', 'type' => 'text'],
                    ['name' => 'account_holder', 'label' => 'Nama rekening', 'type' => 'text'],
                ],
                'detail' => [
                    ['label' => 'Nota dinas', 'attr' => 'notaDinas.no_nd'],
                    ['label' => 'Vendor', 'attr' => 'vendor.name'],
                    ['label' => 'Keperluan', 'attr' => 'keperluan'],
                    ['label' => 'Jumlah', 'attr' => 'jumlah_transfer', 'format' => 'money'],
                    ['label' => 'Jenis', 'attr' => 'jenis_pengeluaran'],
                    ['label' => 'Status invoice', 'attr' => 'status_invoice'],
                ],
            ],
            'simulasi' => [
                'model' => SimulasiProduk::class,
                'feature' => PricingPlans::FEATURE_SIMULASI,
                'quota' => CompanySubscription::RESOURCE_SIMULASI,
                'plan_badge' => 'Pro',
                'title' => 'Simulasi',
                'subtitle' => 'Paket penawaran untuk calon klien',
                'icon' => 'doc.badge.plus',
                'group' => 'penjualan',
                'group_label' => 'Penjualan',
                'title_attr' => 'prospect.name_event',
                'subtitle_attr' => 'product.name',
                'amount_attr' => 'grand_total',
                'search' => ['customer_name', 'notes', 'contract_number'],
                'with' => ['prospect:id,name_event', 'product:id,name', 'user:id,name'],
                'fields' => [
                    ['name' => 'product_id', 'label' => 'Paket dasar', 'type' => 'select', 'required' => true, 'options' => 'products', 'cast' => 'int', 'section' => 'Paket & harga', 'helper' => 'Harga otomatis mengikuti paket yang dipilih.'],
                    ['name' => 'user_id', 'label' => 'Account Manager', 'type' => 'select', 'required' => true, 'options' => 'account_managers', 'cast' => 'int', 'section' => 'Paket & harga'],
                    ['name' => 'prospect_id', 'label' => 'Prospek', 'type' => 'select', 'required' => true, 'options' => 'open_prospects', 'cast' => 'int', 'section' => 'Detail simulasi', 'helper' => 'Hanya prospek yang belum punya proyek.'],
                    ['name' => 'contract_number', 'label' => 'Nomor kontrak / surat', 'type' => 'text', 'section' => 'Detail simulasi', 'helper' => 'Kosongkan untuk penomoran otomatis.'],
                    ['name' => 'name_ttd', 'label' => 'Nama TTD', 'type' => 'text', 'section' => 'Detail simulasi'],
                    ['name' => 'title_ttd', 'label' => 'Jabatan TTD', 'type' => 'text', 'section' => 'Detail simulasi'],
                    ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea', 'section' => 'Detail simulasi'],
                    ['name' => 'payment_dp_amount', 'label' => 'Down Payment (DP)', 'type' => 'number', 'cast' => 'int', 'section' => 'Pola pembayaran'],
                ],
                'detail' => [
                    ['label' => 'Prospek', 'attr' => 'prospect.name_event'],
                    ['label' => 'Paket', 'attr' => 'product.name'],
                    ['label' => 'Account Manager', 'attr' => 'user.name'],
                    ['label' => 'Harga paket', 'attr' => 'total_price', 'format' => 'money'],
                    ['label' => 'Penambahan', 'attr' => 'penambahan', 'format' => 'money'],
                    ['label' => 'Pengurangan', 'attr' => 'pengurangan', 'format' => 'money'],
                    ['label' => 'Grand total', 'attr' => 'grand_total', 'format' => 'money'],
                    ['label' => 'Down Payment', 'attr' => 'payment_dp_amount', 'format' => 'money'],
                    ['label' => 'Nomor kontrak', 'attr' => 'contract_number'],
                    ['label' => 'Nama TTD', 'attr' => 'name_ttd'],
                    ['label' => 'Jabatan TTD', 'attr' => 'title_ttd'],
                    ['label' => 'Catatan', 'attr' => 'notes', 'format' => 'html'],
                ],
            ],
            'payment_methods' => [
                'model' => PaymentMethod::class,
                'feature' => PricingPlans::FEATURE_BASIC_FINANCE,
                'quota' => CompanySubscription::RESOURCE_PAYMENT_METHODS,
                'title' => 'Rekening',
                'subtitle' => 'Kas dan bank',
                'icon' => 'creditcard.fill',
                'group' => 'keuangan',
                'group_label' => 'Keuangan',
                'company_scope' => true,
                'title_attr' => 'name',
                'subtitle_attr' => 'bank_name',
                'amount_attr' => 'opening_balance',
                'search' => ['name', 'bank_name', 'no_rekening'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama rekening', 'type' => 'text', 'required' => true],
                    ['name' => 'bank_name', 'label' => 'Bank', 'type' => 'text'],
                    ['name' => 'no_rekening', 'label' => 'Nomor rekening', 'type' => 'text'],
                    ['name' => 'cabang', 'label' => 'Cabang', 'type' => 'text'],
                    ['name' => 'is_cash', 'label' => 'Kas tunai', 'type' => 'toggle', 'cast' => 'bool'],
                    ['name' => 'opening_balance', 'label' => 'Saldo awal', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'opening_balance_date', 'label' => 'Tanggal saldo awal', 'type' => 'date'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'Bank', 'attr' => 'bank_name'],
                    ['label' => 'Nomor rekening', 'attr' => 'no_rekening'],
                    ['label' => 'Cabang', 'attr' => 'cabang'],
                    ['label' => 'Kas tunai', 'attr' => 'is_cash'],
                    ['label' => 'Saldo awal', 'attr' => 'opening_balance', 'format' => 'money'],
                ],
            ],
            'piutangs' => [
                'model' => Piutang::class,
                'feature' => PricingPlans::FEATURE_BASIC_FINANCE,
                'quota' => CompanySubscription::RESOURCE_PIUTANGS,
                'title' => 'Piutang',
                'subtitle' => 'Tagihan di luar proyek',
                'icon' => 'clock.arrow.circlepath',
                'group' => 'keuangan',
                'group_label' => 'Keuangan',
                'title_attr' => 'nama_debitur',
                'subtitle_attr' => 'nomor_piutang',
                'amount_attr' => 'sisa_piutang',
                'date_attr' => 'tanggal_jatuh_tempo',
                'status_attr' => 'status',
                'search' => ['nama_debitur', 'nomor_piutang'],
                'detail_with' => ['pembayaranPiutangs'],
                'fields' => [
                    ['name' => 'nama_debitur', 'label' => 'Nama debitur', 'type' => 'text', 'required' => true],
                    ['name' => 'kontak_debitur', 'label' => 'Kontak', 'type' => 'text'],
                    ['name' => 'jenis_piutang', 'label' => 'Jenis', 'type' => 'select', 'options' => ['bisnis' => 'Bisnis', 'operasional' => 'Operasional', 'pribadi' => 'Pribadi'], 'in' => ['bisnis', 'operasional', 'pribadi']],
                    ['name' => 'jumlah_pokok', 'label' => 'Jumlah pokok', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'persentase_bunga', 'label' => 'Bunga (%)', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'tanggal_piutang', 'label' => 'Tanggal piutang', 'type' => 'date', 'required' => true],
                    ['name' => 'tanggal_jatuh_tempo', 'label' => 'Jatuh tempo', 'type' => 'date'],
                    ['name' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Nomor', 'attr' => 'nomor_piutang'],
                    ['label' => 'Debitur', 'attr' => 'nama_debitur'],
                    ['label' => 'Kontak', 'attr' => 'kontak_debitur'],
                    ['label' => 'Pokok', 'attr' => 'jumlah_pokok', 'format' => 'money'],
                    ['label' => 'Total', 'attr' => 'total_piutang', 'format' => 'money'],
                    ['label' => 'Sudah dibayar', 'attr' => 'sudah_dibayar', 'format' => 'money'],
                    ['label' => 'Sisa', 'attr' => 'sisa_piutang', 'format' => 'money'],
                    ['label' => 'Status', 'attr' => 'status'],
                ],
            ],
            'pembayaran_piutangs' => [
                'model' => PembayaranPiutang::class,
                'feature' => PricingPlans::FEATURE_BASIC_FINANCE,
                'quota' => CompanySubscription::RESOURCE_PEMBAYARAN_PIUTANGS,
                'title' => 'Bayar Piutang',
                'subtitle' => 'Penerimaan cicilan piutang',
                'icon' => 'checkmark.circle.fill',
                'group' => 'keuangan',
                'group_label' => 'Keuangan',
                'title_attr' => 'nomor_pembayaran',
                'subtitle_attr' => 'piutang.nama_debitur',
                'amount_attr' => 'total_pembayaran',
                'date_attr' => 'tanggal_pembayaran',
                'search' => ['nomor_pembayaran', 'nomor_referensi'],
                'with' => ['piutang:id,nama_debitur,nomor_piutang'],
                'fields' => [
                    ['name' => 'piutang_id', 'label' => 'Piutang', 'type' => 'select', 'required' => true, 'options' => 'piutangs', 'cast' => 'int'],
                    ['name' => 'jumlah_pembayaran', 'label' => 'Jumlah', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'payment_method_id', 'label' => 'Rekening', 'type' => 'select', 'required' => true, 'options' => 'payment_methods', 'cast' => 'int'],
                    ['name' => 'tanggal_pembayaran', 'label' => 'Tanggal bayar', 'type' => 'date', 'required' => true],
                    ['name' => 'catatan', 'label' => 'Catatan', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Nomor', 'attr' => 'nomor_pembayaran'],
                    ['label' => 'Piutang', 'attr' => 'piutang.nomor_piutang'],
                    ['label' => 'Jumlah', 'attr' => 'jumlah_pembayaran', 'format' => 'money'],
                    ['label' => 'Tanggal', 'attr' => 'tanggal_pembayaran', 'format' => 'date'],
                ],
            ],
            'expenses' => [
                'model' => Expense::class,
                'feature' => PricingPlans::FEATURE_BASIC_FINANCE,
                'quota' => CompanySubscription::RESOURCE_EXPENSES,
                'title' => 'Pengeluaran Wedding',
                'subtitle' => 'Biaya vendor per proyek',
                'icon' => 'arrow.up.right.circle.fill',
                'group' => 'keuangan',
                'group_label' => 'Keuangan',
                'title_attr' => 'note',
                'subtitle_attr' => 'vendor.name',
                'amount_attr' => 'amount',
                'date_attr' => 'date_expense',
                'search' => ['note', 'no_nd'],
                'with' => ['vendor:id,name', 'order:id,no_kontrak'],
                'fields' => [
                    ['name' => 'order_id', 'label' => 'Proyek', 'type' => 'select', 'required' => true, 'options' => 'orders', 'cast' => 'int'],
                    ['name' => 'vendor_id', 'label' => 'Vendor', 'type' => 'select', 'options' => 'vendors', 'cast' => 'int'],
                    ['name' => 'payment_method_id', 'label' => 'Rekening', 'type' => 'select', 'required' => true, 'options' => 'payment_methods', 'cast' => 'int'],
                    ['name' => 'amount', 'label' => 'Nominal', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'date_expense', 'label' => 'Tanggal', 'type' => 'date', 'required' => true],
                    ['name' => 'note', 'label' => 'Catatan', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Proyek', 'attr' => 'order.no_kontrak'],
                    ['label' => 'Vendor', 'attr' => 'vendor.name'],
                    ['label' => 'Nominal', 'attr' => 'amount', 'format' => 'money'],
                    ['label' => 'Tanggal', 'attr' => 'date_expense', 'format' => 'date'],
                    ['label' => 'Catatan', 'attr' => 'note'],
                ],
            ],
            'expense_ops' => [
                'model' => ExpenseOps::class,
                'feature' => PricingPlans::FEATURE_BASIC_FINANCE,
                'quota' => CompanySubscription::RESOURCE_EXPENSE_OPS,
                'title' => 'Pengeluaran Operasional',
                'subtitle' => 'Biaya operasional WO',
                'icon' => 'briefcase.fill',
                'group' => 'keuangan',
                'group_label' => 'Keuangan',
                'title_attr' => 'name',
                'amount_attr' => 'amount',
                'date_attr' => 'date_expense',
                'search' => ['name', 'note'],
                'with' => ['paymentMethod:id,name'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama pengeluaran', 'type' => 'text', 'required' => true],
                    ['name' => 'payment_method_id', 'label' => 'Rekening', 'type' => 'select', 'required' => true, 'options' => 'payment_methods', 'cast' => 'int'],
                    ['name' => 'amount', 'label' => 'Nominal', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'date_expense', 'label' => 'Tanggal', 'type' => 'date', 'required' => true],
                    ['name' => 'note', 'label' => 'Catatan', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'Nominal', 'attr' => 'amount', 'format' => 'money'],
                    ['label' => 'Tanggal', 'attr' => 'date_expense', 'format' => 'date'],
                    ['label' => 'Catatan', 'attr' => 'note'],
                ],
            ],
            'pendapatan_lains' => [
                'model' => PendapatanLain::class,
                'feature' => PricingPlans::FEATURE_BASIC_FINANCE,
                'quota' => CompanySubscription::RESOURCE_PENDAPATAN_LAINS,
                'title' => 'Pendapatan Lain',
                'subtitle' => 'Pemasukan di luar proyek',
                'icon' => 'arrow.down.left.circle.fill',
                'group' => 'keuangan',
                'group_label' => 'Keuangan',
                'title_attr' => 'name',
                'amount_attr' => 'nominal',
                'date_attr' => 'tgl_bayar',
                'search' => ['name', 'keterangan'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama pendapatan', 'type' => 'text', 'required' => true],
                    ['name' => 'payment_method_id', 'label' => 'Rekening', 'type' => 'select', 'required' => true, 'options' => 'payment_methods', 'cast' => 'int'],
                    ['name' => 'nominal', 'label' => 'Nominal', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'tgl_bayar', 'label' => 'Tanggal', 'type' => 'date', 'required' => true],
                    ['name' => 'keterangan', 'label' => 'Keterangan', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'Nominal', 'attr' => 'nominal', 'format' => 'money'],
                    ['label' => 'Tanggal', 'attr' => 'tgl_bayar', 'format' => 'date'],
                    ['label' => 'Keterangan', 'attr' => 'keterangan'],
                ],
            ],
            'pengeluaran_lains' => [
                'model' => PengeluaranLain::class,
                'feature' => PricingPlans::FEATURE_BASIC_FINANCE,
                'quota' => CompanySubscription::RESOURCE_PENGELUARAN_LAINS,
                'title' => 'Pengeluaran Lain',
                'subtitle' => 'Biaya di luar wedding dan operasional',
                'icon' => 'minus.circle.fill',
                'group' => 'keuangan',
                'group_label' => 'Keuangan',
                'title_attr' => 'name',
                'amount_attr' => 'amount',
                'date_attr' => 'date_expense',
                'search' => ['name', 'note'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama pengeluaran', 'type' => 'text', 'required' => true],
                    ['name' => 'payment_method_id', 'label' => 'Rekening', 'type' => 'select', 'required' => true, 'options' => 'payment_methods', 'cast' => 'int'],
                    ['name' => 'amount', 'label' => 'Nominal', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'date_expense', 'label' => 'Tanggal', 'type' => 'date', 'required' => true],
                    ['name' => 'note', 'label' => 'Catatan', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'Nominal', 'attr' => 'amount', 'format' => 'money'],
                    ['label' => 'Tanggal', 'attr' => 'date_expense', 'format' => 'date'],
                    ['label' => 'Catatan', 'attr' => 'note'],
                ],
            ],
            'fixed_assets' => [
                'model' => FixedAsset::class,
                'feature' => PricingPlans::FEATURE_FIXED_ASSETS,
                'quota' => CompanySubscription::RESOURCE_FIXED_ASSETS,
                'plan_badge' => 'Pro',
                'title' => 'Aset Tetap',
                'subtitle' => 'Peralatan dan aset WO',
                'icon' => 'building.2.fill',
                'group' => 'profesional',
                'group_label' => 'Professional',
                'title_attr' => 'asset_name',
                'subtitle_attr' => 'asset_code',
                'amount_attr' => 'current_book_value',
                'status_attr' => 'is_active',
                'search' => ['asset_name', 'asset_code'],
                'detail_with' => ['depreciations'],
                'fields' => [
                    ['name' => 'asset_name', 'label' => 'Nama aset', 'type' => 'text', 'required' => true],
                    ['name' => 'category', 'label' => 'Kategori', 'type' => 'select', 'options' => FixedAsset::CATEGORIES, 'in' => array_keys(FixedAsset::CATEGORIES)],
                    ['name' => 'purchase_date', 'label' => 'Tanggal beli', 'type' => 'date', 'required' => true],
                    ['name' => 'purchase_price', 'label' => 'Harga perolehan', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'location', 'label' => 'Lokasi', 'type' => 'text'],
                    ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Kode', 'attr' => 'asset_code'],
                    ['label' => 'Nama', 'attr' => 'asset_name'],
                    ['label' => 'Kategori', 'attr' => 'category'],
                    ['label' => 'Harga perolehan', 'attr' => 'purchase_price', 'format' => 'money'],
                    ['label' => 'Nilai buku', 'attr' => 'current_book_value', 'format' => 'money'],
                    ['label' => 'Lokasi', 'attr' => 'location'],
                ],
            ],
            'bank_statements' => [
                'model' => BankStatement::class,
                'feature' => PricingPlans::FEATURE_RECONCILIATION,
                'plan_badge' => 'Pro',
                'title' => 'Rekonsiliasi',
                'subtitle' => 'Rekening koran',
                'icon' => 'arrow.left.arrow.right',
                'group' => 'profesional',
                'group_label' => 'Professional',
                'title_attr' => 'title',
                'subtitle_attr' => 'paymentMethod.name',
                'amount_attr' => 'closing_balance',
                'status_attr' => 'status',
                'date_attr' => 'period_start',
                'search' => ['title', 'original_filename'],
                'with' => ['paymentMethod:id,name,bank_name,no_rekening'],
                'detail_with' => ['transactions'],
                'fields' => [
                    ['name' => 'payment_method_id', 'label' => 'Rekening', 'type' => 'select', 'required' => true, 'options' => 'payment_methods', 'cast' => 'int'],
                    ['name' => 'title', 'label' => 'Judul', 'type' => 'text', 'required' => true],
                    ['name' => 'period_start', 'label' => 'Periode mulai', 'type' => 'date', 'required' => true],
                    ['name' => 'period_end', 'label' => 'Periode selesai', 'type' => 'date', 'required' => true],
                    ['name' => 'opening_balance', 'label' => 'Saldo awal', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'closing_balance', 'label' => 'Saldo akhir', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'description', 'label' => 'Catatan', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Judul', 'attr' => 'title'],
                    ['label' => 'Rekening', 'attr' => 'paymentMethod.name'],
                    ['label' => 'Cabang', 'attr' => 'branch'],
                    ['label' => 'Mulai', 'attr' => 'period_start', 'format' => 'date'],
                    ['label' => 'Selesai', 'attr' => 'period_end', 'format' => 'date'],
                    ['label' => 'Saldo awal', 'attr' => 'opening_balance', 'format' => 'money'],
                    ['label' => 'Saldo akhir', 'attr' => 'closing_balance', 'format' => 'money'],
                    ['label' => 'Total debit', 'attr' => 'tot_debit', 'format' => 'money'],
                    ['label' => 'Total kredit', 'attr' => 'tot_credit', 'format' => 'money'],
                    ['label' => 'Status', 'attr' => 'status'],
                    ['label' => 'Catatan', 'attr' => 'description'],
                ],
            ],
            'employees' => [
                'model' => Employee::class,
                'feature' => PricingPlans::FEATURE_PAYROLL,
                'plan_badge' => 'Pro',
                'title' => 'Karyawan',
                'subtitle' => 'Master SDM',
                'icon' => 'person.2.fill',
                'group' => 'profesional',
                'group_label' => 'Professional',
                'title_attr' => 'name',
                'subtitle_attr' => 'position',
                'amount_attr' => 'salary',
                'search' => ['name', 'email', 'phone', 'position'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text'],
                    ['name' => 'position', 'label' => 'Jabatan', 'type' => 'text'],
                    ['name' => 'salary', 'label' => 'Gaji', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'date_of_join', 'label' => 'Tanggal gabung', 'type' => 'date'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'Email', 'attr' => 'email'],
                    ['label' => 'Telepon', 'attr' => 'phone'],
                    ['label' => 'Jabatan', 'attr' => 'position'],
                    ['label' => 'Gaji', 'attr' => 'salary', 'format' => 'money'],
                ],
            ],
            'payrolls' => [
                'model' => Payroll::class,
                'feature' => PricingPlans::FEATURE_PAYROLL,
                'plan_badge' => 'Pro',
                'title' => 'Payroll',
                'subtitle' => 'Periode gaji tim',
                'icon' => 'banknote.fill',
                'group' => 'profesional',
                'group_label' => 'Professional',
                // Internal Makna: payroll terikat User (bukan master Employee seperti WOFINS SaaS).
                'title_attr' => 'user.name',
                'subtitle_attr' => 'period_name',
                'amount_attr' => 'monthly_salary',
                'search' => ['notes'],
                'with' => ['user:id,name'],
                'fields' => [
                    ['name' => 'user_id', 'label' => 'Karyawan', 'type' => 'select', 'required' => true, 'options' => 'users', 'cast' => 'int'],
                    ['name' => 'period_month', 'label' => 'Bulan', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'period_year', 'label' => 'Tahun', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'gaji_pokok', 'label' => 'Gaji pokok', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'tunjangan', 'label' => 'Tunjangan', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'pengurangan', 'label' => 'Pengurangan', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'bonus', 'label' => 'Bonus', 'type' => 'number', 'cast' => 'int'],
                    ['name' => 'notes', 'label' => 'Catatan', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Karyawan', 'attr' => 'user.name'],
                    ['label' => 'Bulan', 'attr' => 'period_month'],
                    ['label' => 'Tahun', 'attr' => 'period_year'],
                    ['label' => 'Gaji bulanan', 'attr' => 'monthly_salary', 'format' => 'money'],
                    ['label' => 'Bonus', 'attr' => 'bonus', 'format' => 'money'],
                ],
            ],
            'documents' => [
                'model' => Document::class,
                'feature' => PricingPlans::FEATURE_DOCUMENTS,
                'plan_badge' => 'Business',
                'title' => 'Dokumen',
                'subtitle' => 'Dokumen resmi perusahaan',
                'icon' => 'folder.fill',
                'group' => 'bisnis',
                'group_label' => 'Business',
                'title_attr' => 'title',
                'subtitle_attr' => 'document_number',
                'status_attr' => 'status',
                'date_attr' => 'date_effective',
                'search' => ['title', 'document_number', 'summary'],
                'with' => ['category:id,name'],
                'fields' => [
                    ['name' => 'title', 'label' => 'Judul', 'type' => 'text', 'required' => true],
                    ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'options' => 'document_categories', 'cast' => 'int'],
                    ['name' => 'document_number', 'label' => 'Nomor dokumen', 'type' => 'text'],
                    ['name' => 'summary', 'label' => 'Ringkasan', 'type' => 'textarea'],
                    ['name' => 'date_effective', 'label' => 'Berlaku mulai', 'type' => 'date'],
                ],
                'detail' => [
                    ['label' => 'Judul', 'attr' => 'title'],
                    ['label' => 'Nomor', 'attr' => 'document_number'],
                    ['label' => 'Kategori', 'attr' => 'category.name'],
                    ['label' => 'Ringkasan', 'attr' => 'summary'],
                    ['label' => 'Status', 'attr' => 'status'],
                ],
            ],
            'document_categories' => [
                'model' => DocumentCategory::class,
                'feature' => PricingPlans::FEATURE_DOCUMENTS,
                'plan_badge' => 'Business',
                'title' => 'Kategori Dokumen',
                'subtitle' => 'Klasifikasi dokumen',
                'icon' => 'tag.fill',
                'group' => 'bisnis',
                'group_label' => 'Business',
                'company_scope' => true,
                'title_attr' => 'name',
                'subtitle_attr' => 'code',
                'search' => ['name', 'code'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'code', 'label' => 'Kode', 'type' => 'text'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'Kode', 'attr' => 'code'],
                ],
            ],
            'sops' => [
                'model' => Sop::class,
                'feature' => PricingPlans::FEATURE_DOCUMENTS,
                'plan_badge' => 'Business',
                'title' => 'SOP',
                'subtitle' => 'Prosedur operasional',
                'icon' => 'checklist',
                'group' => 'bisnis',
                'group_label' => 'Business',
                'title_attr' => 'title',
                'subtitle_attr' => 'category.name',
                'status_attr' => 'is_active',
                'search' => ['title', 'description'],
                'with' => ['category:id,name'],
                'fields' => [
                    ['name' => 'title', 'label' => 'Judul SOP', 'type' => 'text', 'required' => true],
                    ['name' => 'category_id', 'label' => 'Kategori', 'type' => 'select', 'options' => 'sop_categories', 'cast' => 'int'],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Judul', 'attr' => 'title'],
                    ['label' => 'Kategori', 'attr' => 'category.name'],
                    ['label' => 'Deskripsi', 'attr' => 'description'],
                    ['label' => 'Versi', 'attr' => 'version'],
                ],
            ],
            'sop_categories' => [
                'model' => SopCategory::class,
                'feature' => PricingPlans::FEATURE_DOCUMENTS,
                'plan_badge' => 'Business',
                'title' => 'Kategori SOP',
                'subtitle' => 'Kelompok prosedur',
                'icon' => 'square.stack.3d.up.fill',
                'group' => 'bisnis',
                'group_label' => 'Business',
                'title_attr' => 'name',
                'subtitle_attr' => 'description',
                'status_attr' => 'is_active',
                'search' => ['name'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'Deskripsi', 'attr' => 'description'],
                ],
            ],
            'documentations' => [
                'model' => Documentation::class,
                'feature' => PricingPlans::FEATURE_DOCUMENTS,
                'plan_badge' => 'Business',
                'title' => 'Knowledge Base',
                'subtitle' => 'Artikel internal tim',
                'icon' => 'book.fill',
                'group' => 'bisnis',
                'group_label' => 'Business',
                'title_attr' => 'title',
                'subtitle_attr' => 'category.name',
                'status_attr' => 'is_published',
                'search' => ['title', 'content', 'keywords'],
                'with' => ['category:id,name'],
                'fields' => [
                    ['name' => 'title', 'label' => 'Judul', 'type' => 'text', 'required' => true],
                    ['name' => 'documentation_category_id', 'label' => 'Kategori', 'type' => 'select', 'options' => 'documentation_categories', 'cast' => 'int'],
                    ['name' => 'content', 'label' => 'Isi', 'type' => 'textarea', 'required' => true],
                ],
                'detail' => [
                    ['label' => 'Judul', 'attr' => 'title'],
                    ['label' => 'Kategori', 'attr' => 'category.name'],
                    ['label' => 'Isi', 'attr' => 'content'],
                ],
            ],
            'documentation_categories' => [
                'model' => DocumentationCategory::class,
                'feature' => PricingPlans::FEATURE_DOCUMENTS,
                'plan_badge' => 'Business',
                'title' => 'Kategori Knowledge',
                'subtitle' => 'Kelompok artikel',
                'icon' => 'books.vertical.fill',
                'group' => 'bisnis',
                'group_label' => 'Business',
                'title_attr' => 'name',
                'status_attr' => 'is_active',
                'search' => ['name'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                ],
            ],
            'data_pribadis' => [
                'model' => DataPribadi::class,
                'feature' => PricingPlans::FEATURE_CREW_FREELANCE,
                'plan_badge' => 'Business',
                'title' => 'Crew Freelance',
                'subtitle' => 'Data crew undangan',
                'icon' => 'person.crop.rectangle.fill',
                'group' => 'bisnis',
                'group_label' => 'Business',
                'title_attr' => 'nama_lengkap',
                'subtitle_attr' => 'pekerjaan',
                'search' => ['nama_lengkap', 'email', 'pekerjaan'],
                'fields' => [
                    ['name' => 'nama_lengkap', 'label' => 'Nama lengkap', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                    ['name' => 'nomor_telepon', 'label' => 'Telepon', 'type' => 'text'],
                    ['name' => 'pekerjaan', 'label' => 'Pekerjaan', 'type' => 'text'],
                    ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'textarea'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'nama_lengkap'],
                    ['label' => 'Email', 'attr' => 'email'],
                    ['label' => 'Telepon', 'attr' => 'nomor_telepon'],
                    ['label' => 'Pekerjaan', 'attr' => 'pekerjaan'],
                    ['label' => 'Alamat', 'attr' => 'alamat'],
                ],
            ],
            'account_manager_targets' => [
                'model' => AccountManagerTarget::class,
                'feature' => PricingPlans::FEATURE_ADVANCED_REPORTS,
                'plan_badge' => 'Business',
                'title' => 'Target AM',
                'subtitle' => 'Target versus closing',
                'icon' => 'chart.bar.fill',
                'group' => 'bisnis',
                'group_label' => 'Business',
                'title_attr' => 'user.name',
                'amount_attr' => 'target_amount',
                'status_attr' => 'status',
                'search' => ['status'],
                'with' => ['user:id,name'],
                'fields' => [
                    ['name' => 'user_id', 'label' => 'Account Manager', 'type' => 'select', 'required' => true, 'options' => 'users', 'cast' => 'int'],
                    ['name' => 'year', 'label' => 'Tahun', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'month', 'label' => 'Bulan', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'target_amount', 'label' => 'Target', 'type' => 'number', 'required' => true, 'cast' => 'int'],
                    ['name' => 'achieved_amount', 'label' => 'Pencapaian', 'type' => 'number', 'cast' => 'int'],
                ],
                'detail' => [
                    ['label' => 'AM', 'attr' => 'user.name'],
                    ['label' => 'Tahun', 'attr' => 'year'],
                    ['label' => 'Bulan', 'attr' => 'month'],
                    ['label' => 'Target', 'attr' => 'target_amount', 'format' => 'money'],
                    ['label' => 'Pencapaian', 'attr' => 'achieved_amount', 'format' => 'money'],
                    ['label' => 'Status', 'attr' => 'status'],
                ],
            ],
            'team' => [
                'model' => User::class,
                'feature' => PricingPlans::FEATURE_ROLE_MANAGEMENT,
                'quota' => CompanySubscription::RESOURCE_USERS,
                'plan_badge' => 'Business',
                'allowed_team' => true,
                'title' => 'Tim',
                'subtitle' => 'Pengguna company ini',
                'icon' => 'person.3.fill',
                'group' => 'perusahaan',
                'group_label' => 'Perusahaan',
                'company_scope' => true,
                'title_attr' => 'name',
                'subtitle_attr' => 'email',
                'status_attr' => 'status',
                'search' => ['name', 'email'],
                'fields' => [
                    ['name' => 'name', 'label' => 'Nama', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'unique' => ['users', 'email']],
                    ['name' => 'password', 'label' => 'Password', 'type' => 'text', 'required' => true],
                    ['name' => 'phone_number', 'label' => 'Telepon', 'type' => 'text'],
                ],
                'detail' => [
                    ['label' => 'Nama', 'attr' => 'name'],
                    ['label' => 'Email', 'attr' => 'email'],
                    ['label' => 'Telepon', 'attr' => 'phone_number'],
                    ['label' => 'Status', 'attr' => 'status'],
                ],
            ],
        ];
    }
}
