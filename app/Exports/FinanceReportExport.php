<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class FinanceReportExport implements \Maatwebsite\Excel\Concerns\WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly string $mode,
        private readonly array $data,
    ) {}

    public function sheets(): array
    {
        if ($this->mode === 'profit_loss') {
            $sheets = [
                new FinanceReportArraySheet('Ringkasan', $this->profitLossSummaryRows()),
                new FinanceReportArraySheet('Proyek', $this->projectRows()),
            ];

            if (($this->data['pendapatanLain'] ?? collect())->isNotEmpty()) {
                $sheets[] = new FinanceReportArraySheet('Pendapatan Lain', $this->otherIncomeRows());
            }
            if (($this->data['expenseOps'] ?? collect())->isNotEmpty()) {
                $sheets[] = new FinanceReportArraySheet('Operasional', $this->operationalRows());
            }
            if (($this->data['pengeluaranLain'] ?? collect())->isNotEmpty()) {
                $sheets[] = new FinanceReportArraySheet('Pengeluaran Lain', $this->otherExpenseRows());
            }

            return $sheets;
        }

        return [
            new FinanceReportArraySheet('Arus Kas', $this->cashRows()),
        ];
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    private function cashRows(): array
    {
        $rows = [
            $this->metaRow('Laporan Arus Kas'),
            ['Perusahaan', $this->data['companyLabel'] ?? ''],
            ['Periode', $this->periodLabel()],
            ['Dicetak', $this->data['generatedDate'] ?? ''],
            [],
            ['Kategori', 'Nominal'],
        ];

        foreach ($this->data['rows'] ?? [] as $label => $amount) {
            $rows[] = [(string) $label, (int) $amount];
        }

        $rows[] = [];
        $rows[] = ['Total Masuk', (int) ($this->data['totalIn'] ?? 0)];
        $rows[] = ['Total Keluar', (int) ($this->data['totalOut'] ?? 0)];
        $rows[] = ['Kas Bersih', (int) ($this->data['net'] ?? 0)];

        return $rows;
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    private function profitLossSummaryRows(): array
    {
        return [
            $this->metaRow('Laporan Laba Rugi'),
            ['Perusahaan', $this->data['companyLabel'] ?? ''],
            ['Periode', $this->periodLabel()],
            ['Dicetak', $this->data['generatedDate'] ?? ''],
            [],
            ['Kategori', 'Nominal'],
            ['Nilai Order', (int) ($this->data['totalExpenses'] ?? 0)],
            ['Pembayaran', (int) ($this->data['totalIncome'] ?? 0)],
            ['Pengeluaran Wedding', (int) ($this->data['sumAllOrdersPengeluaran'] ?? 0)],
            ['Biaya Operasional', (int) ($this->data['totalExpenseOps'] ?? 0)],
            ['Pendapatan Lainnya', (int) ($this->data['totalPendapatanLain'] ?? 0)],
            ['Pengeluaran Lain', (int) ($this->data['totalPengeluaranLain'] ?? 0)],
            [],
            ['Laba / Rugi', (int) ($this->data['netProfit'] ?? 0)],
        ];
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    private function projectRows(): array
    {
        $rows = [[
            'No',
            'Nama Event',
            'Tgl Closing',
            'Total Pemasukan',
            'Nilai Order',
            'Total Pengeluaran',
            'Laba / Rugi',
        ]];

        /** @var Collection|array $orders */
        $orders = $this->data['orders'] ?? collect();
        $index = 1;
        foreach ($orders as $order) {
            $payments = (int) $order->dataPembayaran->sum('nominal');
            $grand = (int) ($order->grand_total ?? 0);
            $expenses = (int) $order->expenses->sum('amount');
            $rows[] = [
                $index++,
                $order->prospect?->name_event ?? $order->number ?? '-',
                $this->formatDate($order->closing_date),
                $payments,
                $grand,
                $expenses,
                $grand - $expenses,
            ];
        }

        if ($index === 1) {
            $rows[] = ['-', 'Tidak ada proyek pada periode ini', '', 0, 0, 0, 0];
        }

        return $rows;
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    private function otherIncomeRows(): array
    {
        $rows = [['Vendor', 'Nama Pendapatan', 'Tanggal', 'Keterangan', 'Nominal']];
        foreach ($this->data['pendapatanLain'] ?? [] as $item) {
            $rows[] = [
                $item->vendor?->name ?? '-',
                $item->name ?? '-',
                $this->formatDate($item->tgl_bayar),
                $item->keterangan ?? '-',
                (int) ($item->nominal ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    private function operationalRows(): array
    {
        $rows = [['Nama', 'Vendor', 'Tanggal', 'Keterangan', 'Nominal']];
        foreach ($this->data['expenseOps'] ?? [] as $item) {
            $rows[] = [
                $item->name ?? '-',
                $item->vendor?->name ?? '-',
                $this->formatDate($item->date_expense),
                $item->note ?? '-',
                (int) ($item->amount ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @return list<list<string|int|float|null>>
     */
    private function otherExpenseRows(): array
    {
        $rows = [['Vendor', 'Nama', 'Tanggal', 'Keterangan', 'Nominal']];
        foreach ($this->data['pengeluaranLain'] ?? [] as $item) {
            $rows[] = [
                $item->vendor?->name ?? '-',
                $item->name ?? '-',
                $this->formatDate($item->date_expense),
                $item->note ?? $item->keterangan ?? '-',
                (int) ($item->amount ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function metaRow(string $title): array
    {
        return [$title];
    }

    private function periodLabel(): string
    {
        $from = $this->data['filterStartDate'] ?? null;
        $to = $this->data['filterEndDate'] ?? null;
        if (! $from || ! $to) {
            return '-';
        }

        return Carbon::parse($from)->format('d M Y').' – '.Carbon::parse($to)->format('d M Y');
    }

    private function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('d M Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}

class FinanceReportArraySheet implements FromArray, ShouldAutoSize, WithTitle
{
    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function __construct(
        private readonly string $title,
        private readonly array $rows,
    ) {}

    public function title(): string
    {
        return mb_substr($this->title, 0, 31);
    }

    public function array(): array
    {
        return $this->rows;
    }
}
