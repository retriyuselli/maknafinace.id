<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use App\Models\Prospect;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class FinanceSummaryService
{
    /**
     * @return array{from: string, to: string}
     */
    public function resolvePeriod(?string $from, ?string $to): array
    {
        $end = $to ? Carbon::parse($to)->startOfDay() : now()->startOfDay();
        $start = $from ? Carbon::parse($from)->startOfDay() : $end->copy()->startOfMonth();

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        return [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(string $from, string $to): array
    {
        $inflow = $this->cashInflow($from, $to);
        $outflow = $this->cashOutflow($from, $to);

        $prevEnd = Carbon::parse($from)->subDay();
        $prevStart = $prevEnd->copy()->subDays(
            Carbon::parse($from)->diffInDays(Carbon::parse($to))
        );
        $prevFrom = $prevStart->toDateString();
        $prevTo = $prevEnd->toDateString();

        $prevIn = $this->cashInflow($prevFrom, $prevTo);
        $prevOut = $this->cashOutflow($prevFrom, $prevTo);

        return [
            'period' => ['from' => $from, 'to' => $to],
            'inflow' => $inflow,
            'outflow' => $outflow,
            'net_cash' => $inflow['total'] - $outflow['total'],
            'comparison' => [
                'period' => ['from' => $prevFrom, 'to' => $prevTo],
                'previous_inflow' => $prevIn['total'],
                'previous_outflow' => $prevOut['total'],
                'previous_net_cash' => $prevIn['total'] - $prevOut['total'],
            ],
        ];
    }

    /**
     * @return array{wedding_payments: int, other_income: int, total: int}
     */
    public function cashInflow(string $from, string $to): array
    {
        $wedding = (int) DataPembayaran::query()
            ->whereBetween('tgl_bayar', [$from, $to])
            ->sum('nominal');

        $other = (int) PendapatanLain::query()
            ->whereBetween('tgl_bayar', [$from, $to])
            ->sum('nominal');

        return [
            'wedding_payments' => $wedding,
            'other_income' => $other,
            'total' => $wedding + $other,
        ];
    }

    /**
     * @return array{wedding_expenses: int, operational: int, other_expenses: int, total: int}
     */
    public function cashOutflow(string $from, string $to): array
    {
        $wedding = (int) Expense::query()
            ->whereBetween('date_expense', [$from, $to])
            ->sum('amount');

        $ops = (int) ExpenseOps::query()
            ->whereBetween('date_expense', [$from, $to])
            ->sum('amount');

        $other = (int) PengeluaranLain::query()
            ->whereBetween('date_expense', [$from, $to])
            ->sum('amount');

        return [
            'wedding_expenses' => $wedding,
            'operational' => $ops,
            'other_expenses' => $other,
            'total' => $wedding + $ops + $other,
        ];
    }

    public function scopedOrdersQuery(): Builder
    {
        return Order::query()->with([
            'prospect:id,name_event,name_cpp,name_cpw,venue,phone,address,date_lamaran,date_akad,date_resepsi',
            'user:id,name',
            'dataPembayaran:id,order_id,nominal,tgl_bayar,keterangan,payment_method_id',
            'dataPengeluaran:id,order_id,amount,date_expense,note,vendor_id,payment_stage',
            'expenses:id,order_id,amount,date_expense,note,vendor_id,payment_stage',
        ]);
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|float>}
     */
    public function projects(?string $status = null, int $perPage = 20): array
    {
        $query = $this->scopedOrdersQuery()->latest('id');

        if ($status) {
            $query->where('status', $status);
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(min(max($perPage, 1), 50));

        $data = collect($paginator->items())->map(fn (Order $order) => $this->projectSummary($order))->values()->all();

        $metaTotals = [
            'total_grand_total' => 0,
            'total_payments' => 0,
            'total_expenses' => 0,
            'total_net_cash_flow' => 0,
        ];

        foreach ($data as $row) {
            $metaTotals['total_grand_total'] += $row['grand_total'];
            $metaTotals['total_payments'] += $row['paid_amount'];
            $metaTotals['total_expenses'] += $row['expenses_total'];
            $metaTotals['total_net_cash_flow'] += $row['net_cash_flow'];
        }

        return [
            'data' => $data,
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ], $metaTotals),
        ];
    }

    public function scopedProspectsQuery(): Builder
    {
        return Prospect::query()->with([
            'user:id,name',
            'latestOrder',
        ]);
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function prospects(?string $status = null, int $perPage = 20): array
    {
        $query = $this->scopedProspectsQuery()->latest('id');
        $this->applyProspectStatusFilter($query, $status);

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate(min(max($perPage, 1), 50));

        $base = $this->scopedProspectsQuery();
        $allCount = (clone $base)->count();
        $warmCount = (clone $base)->doesntHave('orders')->count();

        return [
            'data' => collect($paginator->items())
                ->map(fn (Prospect $prospect) => $this->prospectSummary($prospect))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'all_count' => $allCount,
                'warm_count' => $warmCount,
                'with_order_count' => max(0, $allCount - $warmCount),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function projectSummary(Order $order): array
    {
        $finance = OrderFinance::for($order);
        $paid = $finance->paymentsTotal();
        $expenses = $finance->expensesTotal();
        $grand = $finance->grandTotal();
        $prospect = $order->prospect;

        return [
            'id' => $order->id,
            'slug' => $order->slug,
            'name' => $order->name ?: ($prospect?->name_event),
            'number' => $order->number,
            'status' => $this->enumValue($order->status),
            'closing_date' => optional($order->closing_date)?->toDateString(),
            'account_manager' => $order->user?->name,
            'prospect' => $prospect ? [
                'id' => $prospect->id,
                'name_event' => $prospect->name_event,
                'date_lamaran' => optional($prospect->date_lamaran)?->toDateString(),
                'date_akad' => optional($prospect->date_akad)?->toDateString(),
                'date_resepsi' => optional($prospect->date_resepsi)?->toDateString(),
            ] : null,
            'grand_total' => $grand,
            'paid_amount' => $paid,
            'remaining' => $grand - $paid,
            'expenses_total' => $expenses,
            'net_cash_flow' => $paid - $expenses,
            'gross_profit' => $grand - $expenses,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function prospectSummary(Prospect $prospect): array
    {
        $order = $prospect->latestOrder;
        $orderStatus = $order
            ? $this->enumValue($order->status)
            : 'no_order';

        return [
            'id' => $prospect->id,
            'name_event' => $prospect->name_event,
            'name_cpp' => $prospect->name_cpp,
            'name_cpw' => $prospect->name_cpw,
            'venue' => $prospect->venue,
            'phone' => $prospect->phone,
            'address' => $prospect->address,
            'date_lamaran' => optional($prospect->date_lamaran)?->toDateString(),
            'time_lamaran' => $this->formatClock($prospect->time_lamaran),
            'date_akad' => optional($prospect->date_akad)?->toDateString(),
            'time_akad' => $this->formatClock($prospect->time_akad),
            'date_resepsi' => optional($prospect->date_resepsi)?->toDateString(),
            'time_resepsi' => $this->formatClock($prospect->time_resepsi),
            'total_penawaran' => (int) ($prospect->total_penawaran ?? 0),
            'notes' => $prospect->notes,
            'account_manager' => $prospect->user?->name,
            'order_status' => $orderStatus ?: 'no_order',
            'order' => $order ? [
                'id' => $order->id,
                'name' => $order->name ?: $prospect->name_event,
                'number' => $order->number,
                'status' => $this->enumValue($order->status),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function projectDetail(User $user, int $id): ?array
    {
        /** @var Order|null $order */
        $order = $this->scopedOrdersQuery()
            ->with([
                'prospect',
                'employee:id,name',
                'items.product:id,name,slug,pax,price',
                'dataPembayaran.paymentMethod:id,name,no_rekening',
                'expenses.vendor:id,name',
            ])
            ->find($id);

        if (! $order) {
            return null;
        }

        $summary = $this->projectSummary($order);
        $finance = OrderFinance::for($order);
        $prospect = $order->prospect;

        $contractUrl = $this->publicFileUrl($order->doc_kontrak)
            ? url('/api/v1/finance/projects/'.$order->id.'/contract')
            : null;
        $invoiceName = 'Invoice-'.($prospect?->name_event ?: $order->number ?: 'proyek').'.pdf';

        return array_merge($summary, [
            'pax' => $order->pax,
            'no_kontrak' => $order->no_kontrak,
            'user_id' => $order->user_id,
            'employee_id' => $order->employee_id,
            'prospect_id' => $order->prospect_id,
            'note' => $this->plainText($order->note),
            'has_doc_kontrak' => $this->publicFileUrl($order->doc_kontrak) !== null,
            'has_agreement_product' => $this->publicFileUrl($order->agreement_product) !== null,
            'can_edit' => $this->actorCanEditOrder($user, $order),
            'can_edit_reason' => $this->actorCanEditOrder($user, $order)
                ? null
                : 'Proyek sudah selesai. Hanya Super Admin yang dapat mengedit.',
            'doc_kontrak_url' => $contractUrl,
            'doc_kontrak_name' => $contractUrl
                ? $this->publicFileName($order->doc_kontrak, 'Dokumen kontrak.pdf')
                : null,
            'invoice_url' => url('/api/v1/finance/projects/'.$order->id.'/invoice'),
            'invoice_name' => $invoiceName,
            'event_manager' => $order->employee?->name,
            'prospect' => $prospect ? [
                'id' => $prospect->id,
                'name_event' => $prospect->name_event,
                'name_cpp' => $prospect->name_cpp,
                'name_cpw' => $prospect->name_cpw,
                'venue' => $prospect->venue,
                'phone' => $prospect->phone,
                'address' => $prospect->address,
                'date_lamaran' => optional($prospect->date_lamaran)?->toDateString(),
                'date_akad' => optional($prospect->date_akad)?->toDateString(),
                'date_resepsi' => optional($prospect->date_resepsi)?->toDateString(),
            ] : $summary['prospect'],
            'products' => $order->items->map(function (OrderProduct $item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (int) $item->unit_price,
                    'pax' => $item->product?->pax,
                ];
            })->values()->all(),
            'totals' => [
                'grand_total' => $finance->grandTotal(),
                'paid' => $finance->paymentsTotal(),
                'remaining' => $finance->sisa(),
                'expenses' => $finance->expensesTotal(),
                'net_cash' => $finance->uangDiterima(),
                'gross_profit' => $finance->labaKotor(),
            ],
            'payments' => $order->dataPembayaran->map(function (DataPembayaran $p) {
                return [
                    'id' => $p->id,
                    'date' => optional($p->tgl_bayar)?->toDateString() ?? (string) $p->tgl_bayar,
                    'amount' => (int) $p->nominal,
                    'keterangan' => $p->keterangan,
                    'payment_method' => $this->formatPaymentMethod($p->paymentMethod),
                    'payment_method_id' => $p->payment_method_id,
                    'kategori_transaksi' => $p->kategori_transaksi ?: 'uang_masuk',
                    'has_proof' => $this->latestStoredPath($p->image) !== null,
                ];
            })->values()->all(),
            'expenses' => $order->expenses->map(function (Expense $e) {
                return [
                    'id' => $e->id,
                    'date' => optional($e->date_expense)?->toDateString() ?? (string) $e->date_expense,
                    'amount' => (int) $e->amount,
                    'note' => $e->note,
                    'vendor' => $e->vendor?->name,
                    'payment_stage' => $e->payment_stage,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function transactions(string $from, string $to, ?string $type = null, int $limit = 100, ?string $direction = null): array
    {
        $rows = $this->cashLedgerRows($from, $to);

        if ($type) {
            $rows = $rows->filter(fn (array $r) => $r['type'] === $type)->values();
        }

        if ($direction) {
            $rows = $rows->filter(fn (array $r) => $r['direction'] === $direction)->values();
        }

        $totalIn = (int) $rows->where('direction', 'in')->sum('amount');
        $totalOut = (int) $rows->where('direction', 'out')->sum('amount');

        $sorted = $rows->sortBy('date')->values();
        $running = 0;
        $withBalance = $sorted->map(function (array $row) use (&$running) {
            $running += $row['direction'] === 'in' ? $row['amount'] : -$row['amount'];
            $row['running_balance'] = $running;

            return $row;
        });

        $data = $withBalance->sortByDesc('date')->take($limit)->values()->all();

        return [
            'data' => $data,
            'meta' => [
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'net' => $totalIn - $totalOut,
                'count' => count($data),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reportSummary(string $from, string $to, string $mode = 'cash'): array
    {
        if ($mode === 'profit_loss') {
            return $this->profitLossSummary($from, $to);
        }

        $in = $this->cashInflow($from, $to);
        $out = $this->cashOutflow($from, $to);

        return [
            'mode' => 'cash',
            'period' => ['from' => $from, 'to' => $to],
            'by_type' => [
                'Masuk (Wedding)' => $in['wedding_payments'],
                'Masuk (Lain-lain)' => $in['other_income'],
                'Keluar (Wedding)' => $out['wedding_expenses'],
                'Keluar (Operasional)' => $out['operational'],
                'Keluar (Lain-lain)' => $out['other_expenses'],
            ],
            'total_in' => $in['total'],
            'total_out' => $out['total'],
            'net' => $in['total'] - $out['total'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profitLossSummary(string $from, string $to): array
    {
        $orders = Order::query()
            ->with(['dataPembayaran', 'expenses', 'prospect'])
            ->whereHas('prospect', function (Builder $q) use ($from, $to) {
                $q->where(function (Builder $inner) use ($from, $to) {
                    $inner->whereBetween('date_lamaran', [$from, $to])
                        ->orWhereBetween('date_akad', [$from, $to])
                        ->orWhereBetween('date_resepsi', [$from, $to]);
                });
            })
            ->get();

        $totalOrderValue = 0;
        $totalPayments = 0;
        $totalExpenses = 0;

        foreach ($orders as $order) {
            $finance = OrderFinance::for($order);
            $totalOrderValue += $finance->grandTotal();
            $totalPayments += $finance->paymentsTotal();
            $totalExpenses += $finance->expensesTotal();
        }

        $ops = (int) ExpenseOps::query()->whereBetween('date_expense', [$from, $to])->sum('amount');
        $otherExp = (int) PengeluaranLain::query()->whereBetween('date_expense', [$from, $to])->sum('amount');
        $otherInc = (int) PendapatanLain::query()->whereBetween('tgl_bayar', [$from, $to])->sum('nominal');

        return [
            'mode' => 'profit_loss',
            'period' => ['from' => $from, 'to' => $to],
            'orders_count' => $orders->count(),
            'total_order_value' => $totalOrderValue,
            'total_payments_on_orders' => $totalPayments,
            'total_wedding_expenses' => $totalExpenses,
            'net_profit' => $totalOrderValue - $totalExpenses,
            'operational_expenses' => $ops,
            'other_expenses' => $otherExp,
            'other_income' => $otherInc,
        ];
    }

    /**
     * @return array{absolute: string, name: string, mime: string, mtime: int}|null
     */
    public function paymentProofFile(int $id): ?array
    {
        /** @var DataPembayaran|null $payment */
        $payment = DataPembayaran::query()->find($id);
        $path = $this->latestStoredPath($payment?->image);
        $absolute = $this->absolutePublicPath($path);

        if ($absolute === null || $path === null) {
            return null;
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => 'image/jpeg',
        };

        return [
            'absolute' => $absolute,
            'name' => $this->publicFileName($path, 'Payment proof.jpg') ?? 'Payment proof.jpg',
            'mime' => $mime,
            'mtime' => (int) filemtime($absolute),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function cashLedgerRows(string $from, string $to): Collection
    {
        $weddingIn = DataPembayaran::query()
            ->with(['order:id,name,prospect_id', 'order.prospect:id,name_event', 'paymentMethod:id,name,no_rekening'])
            ->whereBetween('tgl_bayar', [$from, $to])
            ->get()
            ->map(function (DataPembayaran $p) {
                return [
                    'date' => optional($p->tgl_bayar)?->toDateString() ?? (string) $p->tgl_bayar,
                    'type' => 'wedding_payment',
                    'direction' => 'in',
                    'amount' => (int) $p->nominal,
                    'description' => $p->keterangan,
                    'order_id' => $p->order_id,
                    'prospect_name' => $p->order?->prospect?->name_event ?? $p->order?->name,
                    'vendor_name' => null,
                    'payment_method' => $this->formatPaymentMethod($p->paymentMethod),
                    'source_table' => 'data_pembayarans',
                    'source_id' => $p->id,
                    'proof_url' => $this->paymentProofUrl($p),
                ];
            });

        $otherIn = PendapatanLain::query()
            ->with(['paymentMethod:id,name,no_rekening'])
            ->whereBetween('tgl_bayar', [$from, $to])
            ->get()
            ->map(function (PendapatanLain $p) {
                return [
                    'date' => optional($p->tgl_bayar)?->toDateString() ?? (string) $p->tgl_bayar,
                    'type' => 'other_income',
                    'direction' => 'in',
                    'amount' => (int) $p->nominal,
                    'description' => $p->keterangan ?? $p->name,
                    'order_id' => null,
                    'prospect_name' => null,
                    'vendor_name' => null,
                    'payment_method' => $this->formatPaymentMethod($p->paymentMethod),
                    'source_table' => 'pendapatan_lains',
                    'source_id' => $p->id,
                ];
            });

        $weddingOut = Expense::query()
            ->with(['order:id,name,prospect_id', 'order.prospect:id,name_event', 'vendor:id,name', 'paymentMethod:id,name,no_rekening'])
            ->whereBetween('date_expense', [$from, $to])
            ->get()
            ->map(function (Expense $e) {
                return [
                    'date' => optional($e->date_expense)?->toDateString() ?? (string) $e->date_expense,
                    'type' => 'wedding_expense',
                    'direction' => 'out',
                    'amount' => (int) $e->amount,
                    'description' => $e->note,
                    'order_id' => $e->order_id,
                    'prospect_name' => $e->order?->prospect?->name_event ?? $e->order?->name,
                    'vendor_name' => $e->vendor?->name,
                    'payment_method' => $this->formatPaymentMethod($e->paymentMethod),
                    'source_table' => 'expenses',
                    'source_id' => $e->id,
                ];
            });

        $opsOut = ExpenseOps::query()
            ->with(['paymentMethod:id,name,no_rekening'])
            ->whereBetween('date_expense', [$from, $to])
            ->get()
            ->map(function (ExpenseOps $e) {
                return [
                    'date' => optional($e->date_expense)?->toDateString() ?? (string) $e->date_expense,
                    'type' => 'operational_expense',
                    'direction' => 'out',
                    'amount' => (int) $e->amount,
                    'description' => $e->note ?? $e->name,
                    'order_id' => null,
                    'prospect_name' => null,
                    'vendor_name' => null,
                    'payment_method' => $this->formatPaymentMethod($e->paymentMethod),
                    'source_table' => 'expense_ops',
                    'source_id' => $e->id,
                ];
            });

        $otherOut = PengeluaranLain::query()
            ->with(['paymentMethod:id,name,no_rekening'])
            ->whereBetween('date_expense', [$from, $to])
            ->get()
            ->map(function (PengeluaranLain $e) {
                return [
                    'date' => optional($e->date_expense)?->toDateString() ?? (string) $e->date_expense,
                    'type' => 'other_expense',
                    'direction' => 'out',
                    'amount' => (int) $e->amount,
                    'description' => $e->note ?? $e->name,
                    'order_id' => null,
                    'prospect_name' => null,
                    'vendor_name' => null,
                    'payment_method' => $this->formatPaymentMethod($e->paymentMethod),
                    'source_table' => 'pengeluaran_lains',
                    'source_id' => $e->id,
                ];
            });

        return $weddingIn
            ->concat($otherIn)
            ->concat($weddingOut)
            ->concat($opsOut)
            ->concat($otherOut)
            ->values();
    }

    private function applyProspectStatusFilter(Builder $query, ?string $status): void
    {
        if (! $status) {
            return;
        }

        match ($status) {
            'no_order' => $query->doesntHave('orders'),
            'has_order' => $query->has('orders'),
            'pending', 'processing', 'done', 'cancelled' => $query->whereHas(
                'orders',
                fn (Builder $orders) => $orders->where('status', $status)
            ),
            default => null,
        };
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function formatClock(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    protected function formatPaymentMethod(?\App\Models\PaymentMethod $method): ?string
    {
        if (! $method) {
            return null;
        }

        $label = trim((string) ($method->name ?? ''));
        $account = trim((string) ($method->no_rekening ?? ''));

        if ($account !== '') {
            $label .= ($label !== '' ? ' (' : '(').$account.')';
        }

        return $label !== '' ? $label : null;
    }

    private function plainText(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $text === '' ? null : $text;
    }

    private function actorCanEditOrder(User $user, Order $order): bool
    {
        $status = $order->status instanceof OrderStatus
            ? $order->status
            : OrderStatus::tryFrom((string) $order->status);

        if ($status === OrderStatus::Done) {
            return $user->hasRole('super_admin');
        }

        return true;
    }

    private function paymentProofUrl(DataPembayaran $payment): ?string
    {
        $path = $this->latestStoredPath($payment->image);
        $absolute = $this->absolutePublicPath($path);
        if ($absolute === null) {
            return null;
        }

        $version = (string) ((int) filemtime($absolute) ?: optional($payment->updated_at)?->timestamp ?: time());

        return url('/api/v1/finance/payments/'.$payment->id.'/proof').'?v='.$version;
    }

    private function absolutePublicPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);
        if (is_file($absolute)) {
            return $absolute;
        }

        $publicPath = public_path('storage/'.$path);

        return is_file($publicPath) ? $publicPath : null;
    }

    private function publicFileUrl(mixed $value): ?string
    {
        $path = $this->firstStoredPath($value);
        if ($path === null) {
            return null;
        }

        if (! Storage::disk('public')->exists($path) && ! is_file(public_path('storage/'.$path))) {
            return null;
        }

        return url(Storage::url($path));
    }

    private function publicFileName(mixed $value, string $fallback): ?string
    {
        $path = $this->firstStoredPath($value);
        if ($path === null) {
            return null;
        }

        $name = basename($path);
        if ($name === '' || $name === '.' || $name === '..') {
            return $fallback;
        }

        if (preg_match('/^[0-9A-HJKMNP-TV-Z]{20,}\.[a-z0-9]+$/i', $name) === 1) {
            return $fallback;
        }

        return $name;
    }

    private function firstStoredPath(mixed $value): ?string
    {
        return $this->storedPath($value, false);
    }

    private function latestStoredPath(mixed $value): ?string
    {
        return $this->storedPath($value, true);
    }

    private function storedPath(mixed $value, bool $latest): ?string
    {
        if (is_array($value)) {
            if ($value === []) {
                return null;
            }

            $pick = $latest ? end($value) : reset($value);

            return (is_string($pick) || is_array($pick)) ? $this->storedPath($pick, $latest) : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || $trimmed === '0') {
            return null;
        }

        if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->storedPath($decoded, $latest);
            }
        }

        $path = ltrim(str_replace('\\', '/', $trimmed), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path === '' ? null : $path;
    }
}
