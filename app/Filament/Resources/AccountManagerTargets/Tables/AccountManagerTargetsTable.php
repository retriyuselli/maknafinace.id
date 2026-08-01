<?php

namespace App\Filament\Resources\AccountManagerTargets\Tables;

use App\Models\AccountManagerTarget;
use App\Models\Order;
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
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AccountManagerTargetsTable
{
    public static function configure(Table $table): Table
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
                    ->prefix('Rp. ')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('achieved_amount')
                    ->label('Pencapaian')
                    ->prefix('Rp. ')
                    ->numeric()
                    ->sortable()
                    ->url(fn ($record) => route('account-manager.report.show', [
                        'userId' => $record->user_id,
                        'year' => $record->year,
                        'month' => $record->month,
                    ]))
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->extraAttributes(['class' => 'cursor-pointer'])
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
                                ->prefix('Rp. ')
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
                            $achieved = Order::where('user_id', $record->user_id)
                                ->whereNotNull('closing_date')
                                ->whereYear('closing_date', $record->year)
                                ->whereMonth('closing_date', $record->month)
                                ->sum('total_price') ?? 0;

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
                                $achieved = Order::where('user_id', $record->user_id)
                                    ->whereNotNull('closing_date')
                                    ->whereYear('closing_date', $record->year)
                                    ->whereMonth('closing_date', $record->month)
                                    ->sum('total_price') ?? 0;

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
}
