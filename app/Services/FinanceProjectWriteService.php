<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\DataPembayaran;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Prospect;
use App\Models\User;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceProjectWriteService
{
    public function __construct(
        private readonly FinanceSummaryService $finance,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function options(User $user, ?int $orderId = null): array
    {
        $company = CompanySubscription::company();
        $contractPrefix = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) ($company?->inisial_kontak ?: 'KKP')));
        if ($contractPrefix === '') {
            $contractPrefix = 'KKP';
        }

        $currentProspect = null;
        $existingProductIds = [];
        if ($orderId) {
            /** @var Order|null $currentOrder */
            $currentOrder = $this->finance->scopedOrdersQuery($user)->with(['prospect', 'items'])->find($orderId);
            $currentProspect = $currentOrder?->prospect;
            $existingProductIds = $currentOrder?->items->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->all() ?? [];
        }

        $prospectQuery = $this->finance->scopedProspectsQuery()
            ->whereDoesntHave('orders', function ($orders) {
                $orders->whereNotNull('status');
            })
            ->orderBy('name_event');

        $productQuery = Product::query()->where(function ($query) use ($existingProductIds) {
            $query->where('stock', '>', 1);
            if ($existingProductIds !== []) {
                $query->orWhereIn('id', $existingProductIds);
            }
        });
        UserVisibility::constrainCompanyQuery($productQuery);

        $accountManagers = $this->teamUsers('Account Manager');
        $eventManagers = $this->teamUsers('Event Manager');

        $prospects = $prospectQuery->get(['id', 'name_event', 'name_cpp', 'name_cpw', 'venue']);
        if ($currentProspect && ! $prospects->contains('id', $currentProspect->id)) {
            $prospects->prepend($currentProspect);
        }

        return [
            'number' => OrderForm::defaultOrderNumber(),
            'number_prefix' => OrderForm::orderNumberPrefix(),
            'contract_prefix' => $contractPrefix,
            'default_no_kontrak' => $this->defaultContractNumber($contractPrefix),
            'default_pax' => 1000,
            'current_user_id' => $user->id,
            'single_seat' => UserVisibility::isSingleSeatPlan($user),
            'can_create' => CompanySubscription::canCreate(CompanySubscription::RESOURCE_ORDERS),
            'quota_message' => CompanySubscription::summary(CompanySubscription::RESOURCE_ORDERS),
            'statuses' => collect(OrderStatus::cases())->map(fn (OrderStatus $status) => [
                'value' => $status->value,
                'label' => $status->getLabel(),
            ])->values()->all(),
            'prospects' => $prospects
                ->map(fn (Prospect $prospect) => [
                    'id' => $prospect->id,
                    'name_event' => $prospect->name_event,
                    'name_cpp' => $prospect->name_cpp,
                    'name_cpw' => $prospect->name_cpw,
                    'venue' => $prospect->venue,
                ])->values()->all(),
            'products' => $productQuery->orderBy('name')->get([
                'id', 'name', 'product_price', 'price', 'pengurangan', 'penambahan_publish', 'stock', 'pax',
            ])->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'product_price' => (int) ($product->product_price ?: $product->price),
                'pengurangan' => (int) ($product->pengurangan ?? 0),
                'penambahan_publish' => (int) ($product->penambahan_publish ?? 0),
                'stock' => (int) ($product->stock ?? 0),
                'pax' => (int) ($product->pax ?? 0),
            ])->values()->all(),
            'payment_methods' => PaymentMethod::query()->orderBy('name')->get()
                ->map(fn (PaymentMethod $method) => [
                    'id' => $method->id,
                    'name' => $method->name,
                    'label' => $this->paymentMethodLabel($method),
                ])->values()->all(),
            'account_managers' => $accountManagers,
            'event_managers' => $eventManagers,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $files
     * @param  array<int, UploadedFile>  $paymentProofs
     * @return array<string, mixed>
     */
    public function store(User $user, array $data, array $files, array $paymentProofs = []): array
    {
        $prospect = $this->finance->scopedProspectsQuery()->find((int) $data['prospect_id']);
        if (! $prospect) {
            throw ValidationException::withMessages([
                'prospect_id' => 'Prospek tidak ditemukan.',
            ]);
        }

        $hasOrder = Order::query()
            ->where('prospect_id', $prospect->id)
            ->whereNotNull('status')
            ->exists();
        if ($hasOrder) {
            throw ValidationException::withMessages([
                'prospect_id' => 'Prospek ini sudah memiliki proyek.',
            ]);
        }

        $accountManagerIds = collect($this->teamUsers('Account Manager'))->pluck('id')->all();
        $eventManagerIds = collect($this->teamUsers('Event Manager'))->pluck('id')->all();

        if (! in_array((int) $data['user_id'], $accountManagerIds, true)) {
            throw ValidationException::withMessages([
                'user_id' => 'Account Manager tidak valid.',
            ]);
        }

        if (! in_array((int) $data['employee_id'], $eventManagerIds, true)) {
            throw ValidationException::withMessages([
                'employee_id' => 'Event Manager tidak valid.',
            ]);
        }

        $items = $this->normalizedItems($data['items'] ?? []);
        $payments = $this->normalizedPayments($data['payments'] ?? []);

        $totals = $this->totalsFromItems($items);
        $paid = collect($payments)
            ->filter(fn (array $payment) => ($payment['kategori_transaksi'] ?? 'uang_masuk') !== 'uang_keluar')
            ->sum(fn (array $payment) => (int) $payment['nominal']);
        $sisa = $totals['grand_total'] - $paid;
        $closingDate = collect($payments)
            ->pluck('tgl_bayar')
            ->filter()
            ->sort()
            ->first();

        $note = trim(strip_tags((string) ($data['note'] ?? '')));
        $number = trim((string) ($data['number'] ?? '')) ?: OrderForm::defaultOrderNumber();

        if (Order::query()->where('number', $number)->exists()) {
            $number = OrderForm::defaultOrderNumber();
        }

        $payload = UserVisibility::stampCompanyId([
            'prospect_id' => $prospect->id,
            'name' => $prospect->name_event,
            'slug' => Str::slug($prospect->name_event.'-'.$number),
            'number' => $number,
            'user_id' => (int) $data['user_id'],
            'employee_id' => (int) $data['employee_id'],
            'last_edited_by' => $user->id,
            'no_kontrak' => trim((string) $data['no_kontrak']),
            'pax' => (int) $data['pax'],
            'status' => $data['status'],
            'note' => $note === '' ? null : '<p>'.e($note).'</p>',
            'total_price' => $totals['total_price'],
            'promo' => 0,
            'penambahan' => $totals['penambahan'],
            'pengurangan' => $totals['pengurangan'],
            'grand_total' => $totals['grand_total'],
            'paid_amount' => $paid,
            'is_paid' => $sisa <= 0,
            'closing_date' => $closingDate,
        ], 'user_id');

        return DB::transaction(function () use ($payload, $items, $payments, $paymentProofs, $files) {
            $payload['doc_kontrak'] = $files['doc_kontrak']->store('doc_kontrak', 'public');
            $payload['agreement_product'] = $files['agreement_product']->store('agreement_product', 'public');

            $order = Order::query()->create($payload);

            foreach ($items as $item) {
                OrderProduct::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            foreach ($payments as $index => $payment) {
                $row = UserVisibility::stampCompanyId([
                    'company_id' => $order->company_id,
                    'order_id' => $order->id,
                    'keterangan' => $payment['keterangan'],
                    'payment_method_id' => $payment['payment_method_id'],
                    'nominal' => $payment['nominal'],
                    'kategori_transaksi' => $payment['kategori_transaksi'],
                    'tgl_bayar' => $payment['tgl_bayar'],
                    'image' => isset($paymentProofs[$index])
                        ? $paymentProofs[$index]->store('payment-proofs/'.date('Y/m'), 'public')
                        : null,
                ]);

                DataPembayaran::query()->create($row);
            }

            $order->load(['prospect', 'user:id,name']);

            return $this->finance->projectSummary($order);
        });
    }

    public function canEdit(User $user, Order $order): bool
    {
        $status = $order->status instanceof OrderStatus
            ? $order->status
            : OrderStatus::tryFrom((string) $order->status);

        if ($status === OrderStatus::Done) {
            return $user->hasRole('super_admin');
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $files
     * @param  array<int, UploadedFile>  $paymentProofs
     * @return array<string, mixed>
     */
    public function update(User $user, Order $order, array $data, array $files = [], array $paymentProofs = []): array
    {
        if (! $this->canEdit($user, $order)) {
            abort(403, 'Proyek sudah selesai. Hanya Super Admin yang dapat mengedit.');
        }

        $accountManagerIds = collect($this->teamUsers('Account Manager'))->pluck('id')->all();
        $eventManagerIds = collect($this->teamUsers('Event Manager'))->pluck('id')->all();

        if (! in_array((int) $data['user_id'], $accountManagerIds, true)) {
            throw ValidationException::withMessages([
                'user_id' => 'Account Manager tidak valid.',
            ]);
        }

        if (! in_array((int) $data['employee_id'], $eventManagerIds, true)) {
            throw ValidationException::withMessages([
                'employee_id' => 'Event Manager tidak valid.',
            ]);
        }

        $keepProductIds = $order->items()->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        $items = $this->normalizedItems($data['items'] ?? [], $keepProductIds);
        $payments = $this->normalizedPayments($data['payments'] ?? []);
        $totals = $this->totalsFromItems($items);
        $promo = (int) ($order->promo ?? 0);
        $paid = collect($payments)
            ->filter(fn (array $payment) => ($payment['kategori_transaksi'] ?? 'uang_masuk') !== 'uang_keluar')
            ->sum(fn (array $payment) => (int) $payment['nominal']);
        $grandTotal = Order::computeGrandTotalFromValues(
            $totals['total_price'],
            $totals['penambahan'],
            $promo,
            $totals['pengurangan']
        );
        $sisa = $grandTotal - $paid;
        $closingDate = collect($payments)
            ->pluck('tgl_bayar')
            ->filter()
            ->sort()
            ->first();

        $note = trim(strip_tags((string) ($data['note'] ?? '')));

        $payload = [
            'user_id' => (int) $data['user_id'],
            'employee_id' => (int) $data['employee_id'],
            'last_edited_by' => $user->id,
            'no_kontrak' => trim((string) $data['no_kontrak']),
            'pax' => (int) $data['pax'],
            'status' => $data['status'],
            'note' => $note === '' ? null : '<p>'.e($note).'</p>',
            'total_price' => $totals['total_price'],
            'penambahan' => $totals['penambahan'],
            'pengurangan' => $totals['pengurangan'],
            'grand_total' => $grandTotal,
            'paid_amount' => $paid,
            'is_paid' => $sisa <= 0,
            'closing_date' => $closingDate,
        ];

        if (isset($files['doc_kontrak'])) {
            $payload['doc_kontrak'] = $files['doc_kontrak']->store('doc_kontrak', 'public');
        }
        if (isset($files['agreement_product'])) {
            $payload['agreement_product'] = $files['agreement_product']->store('agreement_product', 'public');
        }

        return DB::transaction(function () use ($order, $payload, $items, $payments, $paymentProofs) {
            $order->update($payload);

            $keepProductIds = [];
            foreach ($items as $item) {
                OrderProduct::query()->updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                    ]
                );
                $keepProductIds[] = $item['product_id'];
            }
            OrderProduct::query()
                ->where('order_id', $order->id)
                ->whereNotIn('product_id', $keepProductIds)
                ->delete();

            $keepPaymentIds = [];
            foreach ($payments as $index => $payment) {
                $row = [
                    'company_id' => $order->company_id,
                    'order_id' => $order->id,
                    'keterangan' => $payment['keterangan'],
                    'payment_method_id' => $payment['payment_method_id'],
                    'nominal' => $payment['nominal'],
                    'kategori_transaksi' => $payment['kategori_transaksi'],
                    'tgl_bayar' => $payment['tgl_bayar'],
                ];
                if (isset($paymentProofs[$index])) {
                    $row['image'] = $paymentProofs[$index]->store('payment-proofs/'.date('Y/m'), 'public');
                }

                $existingId = (int) ($payment['id'] ?? 0);
                $existing = $existingId > 0
                    ? DataPembayaran::query()->where('order_id', $order->id)->find($existingId)
                    : null;

                if ($existing) {
                    $existing->update($row);
                    $keepPaymentIds[] = $existing->id;
                } else {
                    $created = DataPembayaran::query()->create(UserVisibility::stampCompanyId($row));
                    $keepPaymentIds[] = $created->id;
                }
            }

            DataPembayaran::query()
                ->where('order_id', $order->id)
                ->when(
                    $keepPaymentIds !== [],
                    fn ($query) => $query->whereNotIn('id', $keepPaymentIds),
                    fn ($query) => $query
                )
                ->delete();

            $order->load(['prospect', 'user:id,name']);

            return $this->finance->projectSummary($order->fresh(['prospect', 'user:id,name']));
        });
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function teamUsers(string $preferredRole): array
    {
        if ($preferredRole === 'Event Manager') {
            return Employee::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Employee $employee) => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                ])
                ->values()
                ->all();
        }

        $query = User::query()->orderBy('name');
        OrderForm::constrainTeamRoleQuery($query, $preferredRole);

        return $query->get(['id', 'name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->values()
            ->all();
    }

    private function defaultContractNumber(string $prefix): string
    {
        do {
            $number = $prefix.'-'.date('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Order::query()->where('no_kontrak', $number)->exists());

        return $number;
    }

    private function paymentMethodLabel(PaymentMethod $method): string
    {
        if ($method->is_cash) {
            return 'Kas/Tunai';
        }

        if ($method->bank_name) {
            return trim($method->bank_name.' - '.$method->no_rekening);
        }

        return (string) $method->name;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<int>  $keepProductIds
     * @return list<array{product_id: int, quantity: int, unit_price: int, pengurangan: int, penambahan: int}>
     */
    private function normalizedItems(array $items, array $keepProductIds = []): array
    {
        $normalized = [];
        $seen = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            if ($productId <= 0) {
                continue;
            }

            if (isset($seen[$productId])) {
                throw ValidationException::withMessages([
                    'items' => 'Setiap paket hanya boleh dipilih sekali.',
                ]);
            }

            $productQuery = Product::query()->whereKey($productId);
            if (! in_array($productId, $keepProductIds, true)) {
                $productQuery->where('stock', '>', 1);
            }
            UserVisibility::constrainCompanyQuery($productQuery);
            /** @var Product|null $product */
            $product = $productQuery->first();
            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => 'Paket tidak ditemukan atau stok tidak mencukupi.',
                ]);
            }

            $stock = (int) ($product->stock ?? 0);
            if (! in_array($productId, $keepProductIds, true) && $stock > 0 && $quantity > $stock) {
                throw ValidationException::withMessages([
                    'items' => 'Jumlah "'.$product->name.'" melebihi stok ('.$stock.').',
                ]);
            }

            $seen[$productId] = true;
            $normalized[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => (int) ($product->product_price ?: $product->price),
                'pengurangan' => (int) ($product->pengurangan ?? 0),
                'penambahan' => (int) ($product->penambahan_publish ?? 0),
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu paket harus dipilih.',
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $payments
     * @return list<array{keterangan: string, payment_method_id: int, nominal: int, kategori_transaksi: string, tgl_bayar: string}>
     */
    private function normalizedPayments(array $payments): array
    {
        $normalized = [];

        foreach ($payments as $index => $payment) {
            $methodId = (int) ($payment['payment_method_id'] ?? 0);
            $nominal = (int) preg_replace('/\D+/', '', (string) ($payment['nominal'] ?? 0));
            $keterangan = trim((string) ($payment['keterangan'] ?? ''));
            $kategori = (string) ($payment['kategori_transaksi'] ?? 'uang_masuk');
            $date = (string) ($payment['tgl_bayar'] ?? '');

            if ($methodId === 0 && $nominal === 0 && $keterangan === '') {
                continue;
            }

            if ($keterangan === '' || $methodId <= 0 || $nominal <= 0 || $date === '') {
                throw ValidationException::withMessages([
                    "payments.$index" => 'Lengkapi keterangan, metode, nominal, dan tanggal bayar.',
                ]);
            }

            if (! in_array($kategori, ['uang_masuk', 'uang_keluar'], true)) {
                $kategori = 'uang_masuk';
            }

            if (! PaymentMethod::query()->whereKey($methodId)->exists()) {
                throw ValidationException::withMessages([
                    "payments.$index.payment_method_id" => 'Metode pembayaran tidak valid.',
                ]);
            }

            $normalized[] = [
                'id' => (int) ($payment['id'] ?? 0),
                'keterangan' => $keterangan,
                'payment_method_id' => $methodId,
                'nominal' => $nominal,
                'kategori_transaksi' => $kategori,
                'tgl_bayar' => $date,
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array{quantity: int, unit_price: int, pengurangan: int, penambahan: int}>  $items
     * @return array{total_price: int, pengurangan: int, penambahan: int, grand_total: int}
     */
    private function totalsFromItems(array $items): array
    {
        $totalPrice = 0;
        $pengurangan = 0;
        $penambahan = 0;

        foreach ($items as $item) {
            $qty = (int) $item['quantity'];
            $totalPrice += $qty * (int) $item['unit_price'];
            $pengurangan += $qty * (int) $item['pengurangan'];
            $penambahan += $qty * (int) $item['penambahan'];
        }

        return [
            'total_price' => $totalPrice,
            'pengurangan' => $pengurangan,
            'penambahan' => $penambahan,
            'grand_total' => Order::computeGrandTotalFromValues($totalPrice, $penambahan, 0, $pengurangan),
        ];
    }
}
