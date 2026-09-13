<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\StatusPiutang;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PendapatanLain;
use App\Models\PengeluaranLain;
use App\Models\Piutang;
use App\Models\Product;
use App\Models\Prospect;
use App\Models\User;
use App\Models\Vendor;
use App\Support\UserVisibility;
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

    public function scopedOrdersQuery(?User $user = null): Builder
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
     * @return array<string, mixed>|null
     */
    public function prospectDetail(int $id): ?array
    {
        /** @var Prospect|null $prospect */
        $prospect = $this->scopedProspectsQuery()->find($id);

        return $prospect ? $this->prospectSummary($prospect) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function storeProspect(User $user, array $data): array
    {
        $data['user_id'] = $user->id;
        $data = UserVisibility::stampCompanyId($data, 'user_id');

        $prospect = Prospect::query()->create($data);
        $prospect->load(['user:id,name', 'latestOrder']);

        return $this->prospectSummary($prospect);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    public function updateProspect(int $id, array $data): ?array
    {
        /** @var Prospect|null $prospect */
        $prospect = $this->scopedProspectsQuery()->find($id);

        if (! $prospect) {
            return null;
        }

        unset($data['user_id'], $data['company_id']);
        $prospect->update($data);
        $prospect->load(['user:id,name', 'latestOrder']);

        return $this->prospectSummary($prospect);
    }

    public static function normalizeProspectPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        }

        return ltrim($digits, '0');
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
     * @return array{absolute: string, name: string}|null
     */
    public function projectContractFile(?User $user, int $id): ?array
    {
        /** @var Order|null $order */
        $order = $this->scopedOrdersQuery($user)->find($id);
        $path = $this->firstStoredPath($order?->doc_kontrak);

        if ($path === null) {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);
        if (! is_file($absolute)) {
            $publicPath = public_path('storage/'.$path);
            $absolute = is_file($publicPath) ? $publicPath : null;
        }

        if ($absolute === null) {
            return null;
        }

        return [
            'absolute' => $absolute,
            'name' => $this->publicFileName($order->doc_kontrak, 'Dokumen kontrak.pdf') ?? 'Dokumen kontrak.pdf',
        ];
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
     * @return array{view: string, data: array<string, mixed>, filename: string}
     */
    public function reportPdfPayload(User $user, string $from, string $to, string $mode = 'cash'): array
    {
        \App\Support\CompanyBrand::remember($user);

        if ($mode === 'profit_loss') {
            return [
                'view' => 'pdf.profit_loss_report',
                'data' => $this->profitLossPdfData($from, $to),
                'filename' => 'laporan-laba-rugi-'.$from.'-'.$to.'.pdf',
            ];
        }

        $summary = $this->reportSummary($from, $to, 'cash');

        return [
            'view' => 'pdf.cash_flow_report',
            'data' => [
                'filterStartDate' => $from,
                'filterEndDate' => $to,
                'generatedDate' => now()->format('d M Y H:i'),
                'companyLabel' => \App\Support\CompanyBrand::name(),
                'rows' => $summary['by_type'] ?? [],
                'totalIn' => $summary['total_in'] ?? 0,
                'totalOut' => $summary['total_out'] ?? 0,
                'net' => $summary['net'] ?? 0,
            ],
            'filename' => 'laporan-arus-kas-'.$from.'-'.$to.'.pdf',
        ];
    }

    /**
     * @return array{export: \App\Exports\FinanceReportExport, filename: string}
     */
    public function reportExcelPayload(User $user, string $from, string $to, string $mode = 'cash'): array
    {
        $pdf = $this->reportPdfPayload($user, $from, $to, $mode);
        $kind = $mode === 'profit_loss' ? 'laba-rugi' : 'arus-kas';

        return [
            'export' => new \App\Exports\FinanceReportExport($mode, $pdf['data']),
            'filename' => 'laporan-'.$kind.'-'.$from.'-'.$to.'.xlsx',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function profitLossPdfData(string $from, string $to): array
    {
        $orders = Order::query()
            ->with(['prospect', 'dataPembayaran', 'expenses'])
            ->whereHas('prospect', function (Builder $q) use ($from, $to) {
                $q->where(function (Builder $inner) use ($from, $to) {
                    $inner->whereBetween('date_lamaran', [$from, $to])
                        ->orWhereBetween('date_akad', [$from, $to])
                        ->orWhereBetween('date_resepsi', [$from, $to]);
                });
            })
            ->get();

        foreach ($orders as $order) {
            if ($order->prospect && ! mb_check_encoding($order->prospect->name_event ?? '', 'UTF-8')) {
                $order->prospect->name_event = iconv('UTF-8', 'UTF-8//IGNORE', $order->prospect->name_event ?? '') ?: '';
            }
        }

        $totalPaymentsReceived = $orders->sum(function (Order $order) {
            return $order->dataPembayaran->sum('nominal');
        });
        $totalOrderValue = $orders->sum('grand_total');
        $totalActualExpenses = $orders->sum(function (Order $order) {
            return $order->expenses->sum('amount');
        });

        $expenseOps = ExpenseOps::query()->with('vendor')->whereBetween('date_expense', [$from, $to])
            ->orderByDesc('date_expense')->get();

        $pengeluaranLain = PengeluaranLain::query()->with('vendor')->whereBetween('date_expense', [$from, $to])
            ->orderByDesc('date_expense')->get();

        $pendapatanLain = PendapatanLain::query()->with('vendor')->whereBetween('tgl_bayar', [$from, $to])
            ->orderByDesc('tgl_bayar')->get();

        return [
            'orders' => $orders,
            'totalIncome' => $totalPaymentsReceived,
            'totalExpenses' => $totalOrderValue,
            'sumAllOrdersPengeluaran' => $totalActualExpenses,
            'netProfit' => $totalOrderValue - $totalActualExpenses,
            'expenseOps' => $expenseOps,
            'pengeluaranLain' => $pengeluaranLain,
            'pendapatanLain' => $pendapatanLain,
            'totalExpenseOps' => $expenseOps->sum('amount'),
            'totalPengeluaranLain' => $pengeluaranLain->sum('amount'),
            'totalPendapatanLain' => $pendapatanLain->sum('nominal'),
            'filterStartDate' => $from,
            'filterEndDate' => $to,
            'generatedDate' => now()->format('d M Y H:i'),
            'companyLabel' => \App\Support\CompanyBrand::name(),
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
     * @return array<string, mixed>|null
     */
    public function productDetail(int $id): ?array
    {
        /** @var Product|null $product */
        $product = Product::query()
            ->with($this->productDetailRelations())
            ->find($id);

        return $product ? $this->serializeProductDetail($product) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeProductDetail(Product $product): array
    {
        $product->unsetRelation('items');
        $product->unsetRelation('penambahanHarga');
        $product->unsetRelation('pengurangans');
        $product->load($this->productDetailRelations());

        $pricing = ProductPricingCalculator::calculateForProduct($product);
        $vendorLines = collect($product->items)->map(fn ($item) => $this->productVendorLine($item))->values()->all();
        $additionLines = collect($product->penambahanHarga)->map(fn ($item) => $this->productAdditionLine($item))->values()->all();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'pax' => $product->pax,
            'pax_akad' => $product->pax_akad ? (int) $product->pax_akad : null,
            'category' => $product->category?->name,
            'description' => $this->plainText($product->description),
            'image_url' => $this->publicFileUrl($product->image),
            'preview_url' => url('/products/'.$product->slug.'/details/preview'),
            'free_pengurangan' => $this->plainText($product->free_pengurangan),
            'product_price' => (int) ($pricing['total_public_price'] ?? $product->product_price ?? 0),
            'vendor_price' => (int) ($pricing['total_vendor_price'] ?? 0),
            'pengurangan' => (int) ($pricing['total_discount_amount'] ?? $product->pengurangan ?? 0),
            'penambahan_publish' => (int) ($pricing['total_addition_publish'] ?? 0),
            'penambahan_vendor' => (int) ($pricing['total_addition_vendor'] ?? 0),
            'price' => (int) ($pricing['final_publish'] ?? $product->price ?? 0),
            'profit' => (int) ($pricing['profit_and_loss'] ?? 0),
            'is_active' => (bool) $product->is_active,
            'is_approved' => (bool) $product->is_approved,
            'vendors' => $vendorLines,
            'additions' => $additionLines,
            'discounts' => collect($product->pengurangans)->map(function ($row) {
                return [
                    'id' => $row->id,
                    'description' => $row->description,
                    'amount' => (int) $row->amount,
                    'notes' => $this->plainText($row->notes),
                ];
            })->values()->all(),
            'pricing' => [
                'harga_awal_publish' => (int) ($pricing['total_public_price'] ?? 0),
                'harga_awal_vendor' => (int) ($pricing['total_vendor_price'] ?? 0),
                'penambahan_publish' => (int) ($pricing['total_addition_publish'] ?? 0),
                'penambahan_vendor' => (int) ($pricing['total_addition_vendor'] ?? 0),
                'subtotal_publish' => (int) ($pricing['subtotal_publish'] ?? 0),
                'subtotal_vendor' => (int) ($pricing['subtotal_vendor'] ?? 0),
                'pengurangan' => (int) ($pricing['total_discount_amount'] ?? 0),
                'total_publish' => (int) ($pricing['final_publish'] ?? 0),
                'total_vendor' => (int) ($pricing['final_vendor'] ?? 0),
                'profit' => (int) ($pricing['profit_and_loss'] ?? 0),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function productDetailRelations(): array
    {
        return [
            'category:id,name',
            'items.vendor.category:id,name',
            'pengurangans',
            'penambahanHarga.vendor.category:id,name',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function vendorDetail(int $id): ?array
    {
        /** @var Vendor|null $vendor */
        $vendor = Vendor::query()
            ->with('category:id,name')
            ->find($id);

        if (! $vendor) {
            return null;
        }

        $publish = (int) ($vendor->harga_publish ?? 0);
        $modal = (int) ($vendor->harga_vendor ?? 0);

        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'pic_name' => $vendor->pic_name,
            'phone' => $vendor->phone,
            'address' => $vendor->address,
            'category' => $vendor->category?->name,
            'description' => $this->plainText($vendor->description),
            'harga_publish' => $publish,
            'harga_vendor' => $modal,
            'profit_amount' => (int) ($vendor->profit_amount ?? max(0, $publish - $modal)),
        ];
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>}
     */
    public function piutangs(?string $status = null, int $perPage = 20, bool $openOnly = false): array
    {
        $query = Piutang::query()->latest('tanggal_piutang')->latest('id');

        $openStatuses = [
            StatusPiutang::AKTIF->value,
            StatusPiutang::DIBAYAR_SEBAGIAN->value,
            StatusPiutang::JATUH_TEMPO->value,
        ];

        if ($status) {
            $query->where('status', $status);
        } elseif ($openOnly) {
            $query->whereIn('status', $openStatuses);
        }

        $paginator = $query->paginate(min(max($perPage, 1), 50));

        $data = collect($paginator->items())->map(fn (Piutang $p) => $this->piutangSummary($p))->values()->all();

        $metaOpen = Piutang::query()->whereIn('status', $openStatuses);

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'open_count' => (int) (clone $metaOpen)->count(),
                'open_sisa' => (int) (clone $metaOpen)->sum('sisa_piutang'),
                'open_total' => (int) (clone $metaOpen)->sum('total_piutang'),
                'open_paid' => (int) (clone $metaOpen)->sum('sudah_dibayar'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function piutangDetail(int $id): ?array
    {
        /** @var Piutang|null $piutang */
        $piutang = Piutang::query()
            ->with([
                'pembayaranPiutangs' => fn ($q) => $q->latest('tanggal_pembayaran')->latest('id'),
                'pembayaranPiutangs.paymentMethod:id,name,no_rekening',
                'dibuatOleh:id,name',
            ])
            ->find($id);

        if (! $piutang) {
            return null;
        }

        $summary = $this->piutangSummary($piutang);

        return array_merge($summary, [
            'catatan' => $piutang->catatan,
            'keterangan' => $piutang->keterangan,
            'kontak_debitur' => $piutang->kontak_debitur,
            'dibuat_oleh' => $piutang->dibuatOleh?->name,
            'payments' => $piutang->pembayaranPiutangs->map(function ($bayar) {
                return [
                    'id' => $bayar->id,
                    'nomor' => $bayar->nomor_pembayaran,
                    'date' => optional($bayar->tanggal_pembayaran)?->toDateString(),
                    'amount' => (int) $bayar->jumlah_pembayaran,
                    'bunga' => (int) ($bayar->jumlah_bunga ?? 0),
                    'denda' => (int) ($bayar->denda ?? 0),
                    'total' => (int) $bayar->total_pembayaran,
                    'payment_method' => $this->formatPaymentMethod($bayar->paymentMethod),
                    'catatan' => $bayar->catatan,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function piutangSummary(Piutang $piutang): array
    {
        $status = $piutang->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : (string) $status;
        $jenis = $piutang->jenis_piutang;
        $jenisValue = $jenis instanceof \BackedEnum ? $jenis->value : (string) $jenis;

        return [
            'id' => $piutang->id,
            'nomor' => $piutang->nomor_piutang,
            'nama_debitur' => $piutang->nama_debitur,
            'jenis' => $jenisValue,
            'status' => $statusValue,
            'status_label' => $status instanceof StatusPiutang ? $status->getLabel() : $statusValue,
            'prioritas' => $piutang->prioritas,
            'jumlah_pokok' => (int) $piutang->jumlah_pokok,
            'total_piutang' => (int) $piutang->total_piutang,
            'sudah_dibayar' => (int) $piutang->sudah_dibayar,
            'sisa_piutang' => (int) $piutang->sisa_piutang,
            'tanggal_piutang' => optional($piutang->tanggal_piutang)?->toDateString(),
            'tanggal_jatuh_tempo' => optional($piutang->tanggal_jatuh_tempo)?->toDateString(),
            'tanggal_lunas' => optional($piutang->tanggal_lunas)?->toDateString(),
            'is_overdue' => $piutang->tanggal_jatuh_tempo
                && $piutang->tanggal_jatuh_tempo->isPast()
                && ! in_array($statusValue, [StatusPiutang::LUNAS->value, StatusPiutang::DIBATALKAN->value], true),
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
        if ($html === null || trim($html) === '') {
            return null;
        }

        $text = (string) preg_replace_callback(
            '/<(ol|ul)\b[^>]*>(.*?)<\/\1>/is',
            function (array $match): string {
                $ordered = strtolower($match[1]) === 'ol';
                $index = 0;
                $inner = preg_replace_callback(
                    '/<li\b[^>]*>(.*?)<\/li>/is',
                    function (array $item) use ($ordered, &$index): string {
                        $index++;
                        $line = $this->plainLine($item[1]);
                        if ($line === null) {
                            return '';
                        }

                        return ($ordered ? $index.'. ' : '- ').$line."\n";
                    },
                    $match[2]
                ) ?? $match[2];

                return "\n".$inner;
            },
            $html
        ) ?? $html;

        $text = (string) preg_replace_callback(
            '/<li\b[^>]*>(.*?)<\/li>/is',
            function (array $item): string {
                $line = $this->plainLine($item[1]);

                return $line === null ? '' : '- '.$line."\n";
            },
            $text
        );

        $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/(p|div|h[1-6]|tr)>/i', "\n", $text) ?? $text;
        $text = $this->plainLine($text, collapseNewlines: false);

        return $text;
    }

    private function plainLine(?string $html, bool $collapseNewlines = true): ?string
    {
        if ($html === null) {
            return null;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace("/[ \t]+/u", ' ', $text);
        if ($collapseNewlines) {
            $text = (string) preg_replace('/\s+/u', ' ', $text);
        } else {
            $text = (string) preg_replace("/\n{3,}/", "\n\n", $text);
        }
        $text = trim($text);

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

    /**
     * @param  \App\Models\ProductVendor  $item
     * @return array<string, mixed>
     */
    private function productVendorLine($item): array
    {
        $qty = max(1, (int) ($item->quantity ?? 1));
        $hargaPublish = (int) ($item->harga_publish ?? 0);
        $hargaVendor = (int) ($item->harga_vendor ?? 0);
        $linePublic = (int) ($item->price_public ?: $hargaPublish * $qty);
        $lineVendor = (int) ($item->total_price ?: $hargaVendor * $qty);

        if ($hargaVendor > 0 && $hargaPublish !== $hargaVendor && $lineVendor === $linePublic) {
            $lineVendor = $hargaVendor * $qty;
        }

        $vendor = $item->vendor;

        return [
            'id' => $item->id,
            'vendor_id' => $item->vendor_id,
            'name' => $vendor?->name,
            'pic_name' => $vendor?->pic_name,
            'phone' => $vendor?->phone !== null && $vendor->phone !== '' ? (string) $vendor->phone : null,
            'address' => $vendor?->address,
            'category' => $vendor?->category?->name,
            'quantity' => $qty,
            'harga_publish' => $hargaPublish,
            'harga_vendor' => $hargaVendor,
            'line_public' => $linePublic,
            'line_vendor' => $lineVendor,
            'line_total' => $linePublic,
            'description' => $this->plainText($item->description),
        ];
    }

    /**
     * @param  \App\Models\ProductPenambahan  $item
     * @return array<string, mixed>
     */
    private function productAdditionLine($item): array
    {
        $publish = (int) ($item->harga_publish ?? 0);
        $vendorPrice = (int) ($item->harga_vendor ?? 0);
        $vendor = $item->vendor;

        return [
            'id' => $item->id,
            'vendor_id' => $item->vendor_id,
            'name' => $vendor?->name,
            'pic_name' => $vendor?->pic_name,
            'phone' => $vendor?->phone !== null && $vendor->phone !== '' ? (string) $vendor->phone : null,
            'address' => $vendor?->address,
            'category' => $vendor?->category?->name,
            'quantity' => 1,
            'harga_publish' => $publish,
            'harga_vendor' => $vendorPrice,
            'line_public' => $publish,
            'line_vendor' => $vendorPrice,
            'line_total' => $publish,
            'description' => $this->plainText($item->description),
        ];
    }
}
