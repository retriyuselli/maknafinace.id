<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Widgets\EventManager as EventManagerWidget;
use App\Models\DataPembayaran;
use App\Models\Employee;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;

class EventManagerDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'event-manager/{record}';

    protected static ?string $title = 'Event Manager';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.event-manager-detail';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    #[Locked]
    public Model|int|string|null $record = null;

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'event-manager.show';
    }

    public static function canAccess(): bool
    {
        return EventManagerWidget::canView();
    }

    public function mount(int|string $record): void
    {
        $this->record = Employee::query()
            ->where('position', 'Event Manager')
            ->withCount('orders')
            ->withSum('orders', 'grand_total')
            ->findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        return $this->getEmployee()?->name ?? static::$title ?? 'Event Manager';
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Event Manager';
    }

    public function getBreadcrumbs(): array
    {
        return [
            Dashboard::getUrl() => 'Dashboard',
            '#' => 'Event Manager',
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali ke Dashboard')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(Dashboard::getUrl()),
            Action::make('editEmployee')
                ->label('Edit Karyawan')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => EmployeeResource::getUrl('edit', ['record' => $this->getEmployee()]))
                ->visible(fn (): bool => $this->getEmployee() !== null
                    && (bool) Filament::auth()?->user()?->can('Update:Employee')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Daftar Event')
            ->query(
                Order::query()
                    ->where('employee_id', $this->getEmployee()?->id ?? 0)
                    ->with('prospect')
            )
            ->columns([
                TextColumn::make('number')
                    ->label('No. Proyek')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('prospect.name_event')
                    ->label('Nama Event')
                    ->searchable()
                    ->wrap()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('closing_date')
                    ->label('Tanggal Closing')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('grand_total')
                    ->label('Nilai Proyek')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('bayar')
                    ->label('Terbayar')
                    ->money('IDR'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class)
                    ->multiple(),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('closing_date', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Belum ada event')
            ->emptyStateDescription('Event Manager ini belum memiliki proyek wedding.');
    }

    public function getEmployee(): ?Employee
    {
        return $this->record instanceof Employee ? $this->record : null;
    }

    public function getPhotoUrl(): string
    {
        $employee = $this->getEmployee();

        if (filled($employee?->photo)) {
            return Storage::disk('public')->url($employee->photo);
        }

        $name = $employee?->name ?? 'Event Manager';

        return 'https://ui-avatars.com/api/?'.http_build_query([
            'name' => $name,
            'size' => 128,
            'background' => '059669',
            'color' => 'ffffff',
            'font-size' => 0.6,
            'rounded' => true,
            'bold' => true,
            'format' => 'svg',
        ]);
    }

    public function getTotalEvents(): int
    {
        return (int) ($this->getEmployee()?->orders_count ?? 0);
    }

    public function getTotalValue(): int
    {
        return (int) ($this->getEmployee()?->orders_sum_grand_total ?? 0);
    }

    public function getTotalPaid(): int
    {
        $employeeId = $this->getEmployee()?->id;

        if (! $employeeId) {
            return 0;
        }

        return (int) DataPembayaran::query()
            ->whereHas('order', fn ($query) => $query->where('employee_id', $employeeId))
            ->sum('nominal');
    }

    public function getOutstanding(): int
    {
        return max(0, $this->getTotalValue() - $this->getTotalPaid());
    }
}
