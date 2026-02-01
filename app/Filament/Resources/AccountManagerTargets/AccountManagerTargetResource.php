<?php

namespace App\Filament\Resources\AccountManagerTargets;

use App\Filament\Resources\AccountManagerTargets\Pages\CreateAccountManagerTarget;
use App\Filament\Resources\AccountManagerTargets\Pages\ListAccountManagerTargets;
use App\Filament\Resources\AccountManagerTargets\Widgets\AmOverview;
use App\Filament\Resources\AccountManagerTargets\Widgets\AmPerformanceChart;
use App\Filament\Resources\AccountManagerTargets\Widgets\TopPerformersWidget;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\AccountManagerTarget;
use App\Models\LeaveRequest;
use App\Models\Order;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class AccountManagerTargetResource extends Resource
{
    protected static ?string $model = AccountManagerTarget::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Target Manajer Akun';

    protected static ?string $modelLabel = 'Target Account Manager';

    protected static ?string $pluralModelLabel = 'Target Account Manager';

    /**
     * Check if user can access this resource
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Check if user has super_admin or Account Manager role
        $roleNames = $user->roles->pluck('name');

        return $roleNames->contains('super_admin') || $roleNames->contains('Account Manager');
    }

    /**
     * Check if user can view any records
     */
    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    /**
     * Check if user can view specific record
     */
    public static function canView(Model $record): bool
    {
        return static::canAccess();
    }

    /**
     * Check if user can create records
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Only super_admin can create
        return $user->roles->where('name', 'super_admin')->count() > 0;
    }

    /**
     * Check if user can edit records
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Only super_admin can edit
        return $user->roles->where('name', 'super_admin')->count() > 0;
    }

    /**
     * Check if user can delete records
     */
    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Only super_admin can delete
        return $user->roles->where('name', 'super_admin')->count() > 0;
    }

    /**
     * Get the Eloquent query builder for the resource
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc');

        // Filter resigned users: only show targets up to their resignation month
        $query->whereHas('user', function ($q) {
            $q->whereNull('last_working_date')
                ->orWhereRaw("(account_manager_targets.year * 100 + account_manager_targets.month) <= (YEAR(last_working_date) * 100 + MONTH(last_working_date))");
        });

        $user = Auth::user();

        // If user is Account Manager, only show their own targets
        if ($user) {
            $isAccountManager = $user->roles->where('name', 'Account Manager')->count() > 0;
            $isSuperAdmin = $user->roles->where('name', 'super_admin')->count() > 0;

            if ($isAccountManager && ! $isSuperAdmin) {
                $query->where('user_id', $user->id);
            }
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name', function (Builder $query) {
                        return $query->whereHas('roles', function ($q) {
                            $q->where('name', 'Account Manager');
                        });
                    })
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('year')
                    ->options(function () {
                        $currentYear = Carbon::now()->year;
                        $years = [];
                        for ($i = -2; $i <= 3; $i++) {
                            $year = $currentYear + $i;
                            $years[$year] = $year;
                        }

                        return $years;
                    })
                    ->default(Carbon::now()->year)
                    ->required(),
                Select::make('month')
                    ->options(function () {
                        $months = [];
                        for ($m = 1; $m <= 12; $m++) {
                            $months[$m] = Carbon::createFromDate(null, $m, 1)->format('F');
                        }

                        return $months;
                    })
                    ->required(),
                TextInput::make('target_amount')
                    ->required()
                    ->numeric()
                    ->prefix('IDR')
                    ->default(1000000000.00)
                    ->placeholder('1.000.000.000'),
                TextInput::make('achieved_amount')
                    ->numeric()
                    ->prefix('IDR')
                    ->default(0.00)
                    ->readOnly()
                    ->helperText('Otomatis dihitung dari orders'),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'achieved' => 'Achieved',
                        'on_track' => 'On Track',
                        'behind' => 'Behind',
                        'failed' => 'Failed',
                        'overachieved' => 'Overachieved',
                    ])
                    ->default('pending')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Account Manager')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),
                TextColumn::make('month')
                    ->label('Bulan (Angka)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('month_name')
                    ->label('Nama Bulan')
                    ->getStateUsing(function ($record) {
                        return Carbon::createFromDate(null, $record->month, 1)->format('F');
                    }),
                TextColumn::make('target_amount')
                    ->label('Target')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('achieved_amount')
                    ->label('Pencapaian')
                    ->money('IDR')
                    ->sortable()
                    ->action(function ($record, $livewire) {
                        $livewire->mountTableAction('preview_orders', $record, [
                            'user_id' => $record->user_id,
                            'year' => $record->year,
                            'month' => $record->month,
                        ]);
                    })
                    ->color('primary')
                    ->tooltip('Klik untuk melihat detail order yang berkontribusi pada pencapaian ini'),

                TextColumn::make('order_count')
                    ->label('Jumlah Order')
                    ->getStateUsing(function ($record) {
                        return Order::where('user_id', $record->user_id)
                            ->whereNotNull('closing_date')
                            ->whereYear('closing_date', $record->year)
                            ->whereMonth('closing_date', $record->month)
                            ->where('total_price', '>', 0)
                            ->count();
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->sortable(false)
                    ->tooltip('Jumlah order yang berkontribusi pada pencapaian ini'),
                TextColumn::make('achievement_percentage')
                    ->label('Persentase (%)')
                    ->getStateUsing(function ($record) {
                        if ($record->target_amount > 0) {
                            return round(($record->achieved_amount / $record->target_amount) * 100, 2);
                        }

                        return 0;
                    })
                    ->suffix('%'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->target_amount > 0) {
                            $percentage = ($record->achieved_amount / $record->target_amount) * 100;

                            if ($percentage >= 100) {
                                return 'Achieved';
                            }
                            if ($percentage >= 75) {
                                return 'On Track';
                            }
                            if ($percentage >= 50) {
                                return 'Behind';
                            }

                            return 'Failed';
                        }

                        return 'Failed';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Achieved' => 'success',
                        'On Track' => 'warning',
                        'Behind' => 'danger',
                        'Failed' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('user_id')
                    ->relationship('user', 'name', function (Builder $query) {
                        $query->whereHas('roles', function ($q) {
                            $q->where('name', 'Account Manager');
                        });

                        $user = Auth::user();
                        // If user is Account Manager (not super_admin), only show themselves
                        if ($user) {
                            $isAccountManager = $user->roles->where('name', 'Account Manager')->count() > 0;
                            $isSuperAdmin = $user->roles->where('name', 'super_admin')->count() > 0;

                            if ($isAccountManager && ! $isSuperAdmin) {
                                $query->where('id', $user->id);
                            }
                        }

                        return $query;
                    })
                    ->label('Account Manager')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('year')
                    ->options(function () {
                        $currentYear = Carbon::now()->year;
                        $years = [];

                        // Mulai dari 2024 sampai tahun sekarang + 1 tahun ke depan
                        for ($year = 2024; $year <= ($currentYear + 1); $year++) {
                            $years[$year] = $year;
                        }

                        return $years;
                    })
                    ->label('Tahun'),

                SelectFilter::make('month')
                    ->options(function () {
                        $months = [];
                        for ($m = 1; $m <= 12; $m++) {
                            $months[$m] = Carbon::createFromDate(null, $m, 1)->format('F');
                        }

                        return $months;
                    })
                    ->label('Bulan'),
            ])
            ->actions([
                Action::make('preview_orders')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Kontribusi Order')
                    ->modalContent(function (Action $action, $record) {
                        $arguments = $action->getArguments();
                        $userId = $arguments['user_id'] ?? $record?->user_id;
                        $year = $arguments['year'] ?? $record?->year;
                        $month = $arguments['month'] ?? $record?->month;

                        if (! $userId) {
                            return view('filament.modals.achievement-details', [
                                'orders' => [],
                            ]);
                        }

                        $orders = Order::where('user_id', $userId)
                            ->whereYear('closing_date', $year)
                            ->whereMonth('closing_date', $month)
                            ->with('prospect')
                            ->get();
                        
                        return view('filament.modals.achievement-details', [
                            'orders' => $orders,
                        ]);
                    }),
                    // ->modalSubmitAction(false)
                    // ->modalCancelActionLabel('Tutup'),
                
                ActionGroup::make([
                    Action::make('edit_target')
                        ->label('Edit Target')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->visible(function (): bool {
                            $user = Auth::user();

                            return $user && $user->roles->where('name', 'super_admin')->count() > 0;
                        })
                        ->schema([
                            TextInput::make('target_amount')
                                ->label('Target Amount')
                                ->numeric()
                                ->prefix('IDR')
                                ->required()
                                ->placeholder('1.000.000.000'),
                        ])
                        ->fillForm(fn (AccountManagerTarget $record): array => [
                            'target_amount' => $record->target_amount,
                        ])
                        ->action(function (array $data, AccountManagerTarget $record): void {
                            $record->update([
                                'target_amount' => $data['target_amount'],
                            ]);

                            Notification::make()
                                ->title('Target updated successfully')
                                ->success()
                                ->send();
                        }),

                    RestoreAction::make(),
                    ForceDeleteAction::make(),

                    Action::make('refresh_data')
                        ->label('Sync dari Order')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Sync Data dari Order')
                        ->modalDescription('Sinkronkan achieved_amount dan status berdasarkan data Order terbaru.')
                        ->action(function (AccountManagerTarget $record) {
                            // Hitung achieved amount berdasarkan Order menggunakan total_price
                            $achieved = Order::where('user_id', $record->user_id)
                                ->whereNotNull('closing_date')
                                ->whereYear('closing_date', $record->year)
                                ->whereMonth('closing_date', $record->month)
                                ->sum('total_price') ?? 0;

                            // Hitung status berdasarkan pencapaian
                            $targetAmount = $record->target_amount;
                            $status = 'pending';

                            if ($achieved >= $targetAmount) {
                                $status = 'achieved';
                            } elseif ($achieved >= ($targetAmount * 0.75)) {
                                $status = 'on_track';
                            } elseif ($achieved >= ($targetAmount * 0.50)) {
                                $status = 'behind';
                            } else {
                                $status = 'failed';
                            }

                            $record->update([
                                'achieved_amount' => $achieved,
                                'status' => $status,
                            ]);

                            Notification::make()
                                ->title('Data berhasil disinkronkan')
                                ->body('Achieved amount: '.number_format($achieved, 0, ',', '.').' | Status: '.$status)
                                ->success()
                                ->send();
                        }),




                ])
                    ->label('Actions')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),

                    BulkAction::make('refresh_all')
                        ->label('Sync All Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->modalHeading('Sync Selected Records')
                        ->modalDescription('Sinkronkan achieved_amount dan status untuk semua record yang dipilih berdasarkan data Order terbaru.')
                        ->action(function ($records) {
                            $syncedCount = 0;

                            foreach ($records as $record) {
                                // Hitung achieved amount berdasarkan Order menggunakan total_price
                                $achieved = Order::where('user_id', $record->user_id)
                                    ->whereNotNull('closing_date')
                                    ->whereYear('closing_date', $record->year)
                                    ->whereMonth('closing_date', $record->month)
                                    ->sum('total_price') ?? 0;

                                // Hitung status berdasarkan pencapaian
                                $targetAmount = $record->target_amount;
                                $status = 'pending';

                                if ($achieved >= $targetAmount) {
                                    $status = 'achieved';
                                } elseif ($achieved >= ($targetAmount * 0.8)) {
                                    $status = 'on_track';
                                } elseif ($achieved > 0) {
                                    $status = 'behind';
                                }

                                $record->update([
                                    'achieved_amount' => $achieved,
                                    'status' => $status,
                                ]);

                                $syncedCount++;
                            }

                            Notification::make()
                                ->title('Semua record berhasil disinkronkan')
                                ->body("{$syncedCount} record telah diperbarui dengan data Order terbaru.")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccountManagerTargets::route('/'),
            'create' => CreateAccountManagerTarget::route('/create'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            AmOverview::class,
            AmPerformanceChart::class,
            TopPerformersWidget::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}