<?php

namespace App\Filament\Resources\NotaDinasDetails\Pages;

use App\Filament\Resources\NotaDinasDetails\NotaDinasDetailResource;
use App\Models\NotaDinasDetail;
use Carbon\Carbon;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class CurrentMonthReport extends Page
{
    protected static string $resource = NotaDinasDetailResource::class;

    protected string $view = 'filament.resources.nota-dinas-detail-resource.pages.current-month-report';

    protected static ?string $title = 'Total Transaksi Bulan Ini';

    protected static ?string $slug = 'current-month';

    public int $year;
    public int $month;

    public function mount(): void
    {
        $this->year = (int) (request()->input('year') ?? now()->year);
        $this->month = (int) (request()->input('month') ?? now()->month);
    }

    protected function getViewData(): array
    {
        $details = NotaDinasDetail::with([
            'notaDinas:id,no_nd,status',
            'vendor:id,name',
            'order:id,name',
        ])
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $details->sum('jumlah_transfer');
        $monthName = Carbon::create()->month($this->month)->locale('id')->isoFormat('MMMM');

        return [
            'details' => $details,
            'total' => $total,
            'year' => $this->year,
            'month' => $this->month,
            'monthName' => $monthName,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Total Transaksi Bulan Ini';
    }
}
