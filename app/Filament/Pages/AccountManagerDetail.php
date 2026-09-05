<?php

namespace App\Filament\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\AccountManagerWidget;
use App\Models\DataPembayaran;
use App\Models\Employee;
use App\Models\Order;
use App\Models\User;
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

class AccountManagerDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'account-manager/{record}';

    protected static ?string $title = 'Account Manager';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.account-manager-detail';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    #[Locked]
    public Model|int|string|null $record = null;

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'account-manager.show';
    }

    public static function canAccess(): bool
    {
        return AccountManagerWidget::canView();
    }

    public function mount(int|string $record): void
    {
        $user = User::query()
            ->with(['activeEmployee', 'latestEmployee'])
            ->withCount('orders')
            ->withSum('orders', 'grand_total')
            ->findOrFail($record);

        abort_unless($user->hasRole('Account Manager'), 404);

        $this->record = $user;
    }

    public function getTitle(): string|Htmlable
    {
        return $this->getAccountManager()?->name ?? static::$title ?? 'Account Manager';
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Account Manager';
    }

    public function getBreadcrumbs(): array
    {
        return [
            Dashboard::getUrl() => 'Dashboard',
            '#' => 'Account Manager',
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
            Action::make('editUser')
                ->label('Edit Pengguna')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => UserResource::getUrl('edit', ['record' => $this->getAccountManager()]))
                ->visible(fn (): bool => $this->getAccountManager() !== null
                    && (bool) Filament::auth()?->user()?->can('Update:User')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Daftar Proyek')
            ->query(
                Order::query()
                    ->where('user_id', $this->getAccountManager()?->id ?? 0)
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
            ->emptyStateHeading('Belum ada proyek')
            ->emptyStateDescription('Account Manager ini belum memiliki proyek wedding.');
    }

    public function getAccountManager(): ?User
    {
        return $this->record instanceof User ? $this->record : null;
    }

    public function getRelatedEmployee(): ?Employee
    {
        $user = $this->getAccountManager();

        return $user?->activeEmployee ?? $user?->latestEmployee;
    }

    public function getPhotoUrl(): string
    {
        $user = $this->getAccountManager();

        if (filled($user?->avatar_url)) {
            return Storage::url($user->avatar_url);
        }

        $name = $user?->name ?? 'Account Manager';

        return 'https://ui-avatars.com/api/?'.http_build_query([
            'name' => $name,
            'size' => 128,
            'background' => '3b82f6',
            'color' => 'ffffff',
            'font-size' => 0.6,
            'rounded' => true,
            'bold' => true,
            'format' => 'svg',
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string, helper: string, valueClass: string}>
     */
    public function getStatCards(): array
    {
        $totalClients = $this->getTotalClients();
        $totalValue = $this->getTotalValue();
        $totalPaid = $this->getTotalPaid();
        $outstanding = $this->getOutstanding();

        return [
            [
                'label' => 'Total Clients',
                'value' => number_format($totalClients, 0, ',', '.'),
                'helper' => 'Jumlah proyek wedding yang di-closing',
                'valueClass' => 'text-gray-950 dark:text-white',
            ],
            [
                'label' => 'Nilai Proyek',
                'value' => 'Rp '.number_format($totalValue, 0, ',', '.'),
                'helper' => 'Total grand total seluruh proyek',
                'valueClass' => 'text-gray-950 dark:text-white',
            ],
            [
                'label' => 'Terbayar',
                'value' => 'Rp '.number_format($totalPaid, 0, ',', '.'),
                'helper' => 'Akumulasi pembayaran klien yang sudah diterima',
                'valueClass' => 'text-emerald-600 dark:text-emerald-400',
            ],
            [
                'label' => 'Sisa Tagihan',
                'value' => 'Rp '.number_format($outstanding, 0, ',', '.'),
                'helper' => $outstanding > 0
                    ? 'Nilai proyek dikurangi pembayaran yang diterima'
                    : ($totalPaid > $totalValue
                        ? 'Pembayaran sudah menutup nilai proyek'
                        : 'Tidak ada sisa tagihan'),
                'valueClass' => 'text-amber-600 dark:text-amber-400',
            ],
        ];
    }

    public function getTotalClients(): int
    {
        return (int) ($this->getAccountManager()?->orders_count ?? 0);
    }

    public function getTotalValue(): int
    {
        return (int) ($this->getAccountManager()?->orders_sum_grand_total ?? 0);
    }

    public function getTotalPaid(): int
    {
        $userId = $this->getAccountManager()?->id;

        if (! $userId) {
            return 0;
        }

        return (int) DataPembayaran::query()
            ->whereHas('order', fn ($query) => $query->where('user_id', $userId))
            ->sum('nominal');
    }

    public function getOutstanding(): int
    {
        return max(0, $this->getTotalValue() - $this->getTotalPaid());
    }
}
