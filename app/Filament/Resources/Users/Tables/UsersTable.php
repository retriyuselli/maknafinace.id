<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as DBSchema;

class UsersTable
{
    private static ?bool $cachedIsSuperAdmin = null;

    private static function isSuperAdmin(): bool
    {
        if (static::$cachedIsSuperAdmin === null) {
            /** @var User|null $user */
            $user = Auth::user();
            static::$cachedIsSuperAdmin = $user ? $user->hasRole('super_admin') : false;
        }

        return static::$cachedIsSuperAdmin;
    }

    private static function isTargetUserSuperAdmin(?User $record): bool
    {
        if (! $record) {
            return false;
        }

        return $record->roles->contains('name', 'super_admin');
    }

    private static function avatarUrl(User $record): string
    {
        $initials = collect(explode(' ', $record->name ?? 'U'))
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->implode('');

        // Selalu pakai ui-avatars di list — hindari /storage/* lewat php artisan serve (~0.5–1s/file)
        return 'https://ui-avatars.com/api/?name='.urlencode($initials ?: 'U').'&background=3b82f6&color=ffffff&size=64&font-size=0.33';
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Foto')
                    ->getStateUsing(fn (User $record): string => static::avatarUrl($record))
                    ->circular()
                    ->size(36)
                    ->extraImgAttributes(['loading' => 'lazy', 'decoding' => 'async']),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): ?string => $record->email),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(',')
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'Account Manager' => 'info',
                        'employee' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('statuses.status_name')
                    ->label('Jabatan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin' => 'danger',
                        'Finance' => 'warning',
                        'HRD' => 'info',
                        'Account Manager' => 'primary',
                        'Staff' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'terminated' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'terminated' => 'Terminated',
                        default => $state,
                    }),

                TextColumn::make('work_type')
                    ->label('Office')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'office' => 'Office',
                        'remote' => 'Remote',
                        default => $state ?: '-',
                    }),

                TextColumn::make('department')
                    ->label('Departemen')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'bisnis' => 'Bisnis',
                        'operasional' => 'Operasional',
                        default => $state ?: '-',
                    }),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->multiple(),

                SelectFilter::make('account_status')
                    ->label('Status Akun')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'terminated' => 'Terminated',
                    ])
                    ->attribute('status'),

                SelectFilter::make('work_type')
                    ->label('Office')
                    ->options([
                        'office' => 'Office',
                        'remote' => 'Remote',
                    ]),

                SelectFilter::make('department')
                    ->label('Departemen')
                    ->options([
                        'bisnis' => 'Bisnis',
                        'operasional' => 'Operasional',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->color('info')
                    ->visible(fn () => ! static::isSuperAdmin()),

                ActionGroup::make([
                    ViewAction::make()->label('Lihat')->color('info'),

                    EditAction::make()
                        ->label('Edit')
                        ->color('warning')
                        ->visible(function ($record) {
                            if (static::isSuperAdmin()) {
                                return true;
                            }
                            $user = Auth::user();

                            return $user && $user->id === $record->id;
                        }),

                    Action::make('reset_password')
                        ->label('Reset Password')
                        ->icon('heroicon-o-key')
                        ->color('secondary')
                        ->schema([
                            TextInput::make('new_password')
                                ->label('Password Baru')
                                ->password()
                                ->required()
                                ->minLength(8),
                            TextInput::make('confirm_password')
                                ->label('Konfirmasi Password')
                                ->password()
                                ->required()
                                ->same('new_password'),
                        ])
                        ->action(function (array $data, $record): void {
                            $record->update(['password' => $data['new_password']]);

                            Notification::make()
                                ->title('Password berhasil direset')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Reset Password User')
                        ->visible(fn () => static::isSuperAdmin()),

                    Action::make('deactivate_user')
                        ->label('Nonaktifkan Permanen')
                        ->icon('heroicon-o-archive-box-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan User Permanen')
                        ->modalDescription(fn ($record) => "Nonaktifkan {$record->name} secara permanen?")
                        ->action(function ($record): void {
                            $record->update([
                                'status' => 'terminated',
                                'expire_date' => now(),
                                'last_working_date' => now()->toDateString(),
                            ]);

                            Notification::make()
                                ->title("User {$record->name} dinonaktifkan")
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => static::isSuperAdmin() && $record->status !== 'terminated'),

                    Action::make('delete_user')
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus User')
                        ->modalDescription('User dengan data terkait (cuti/payroll/nota dinas) tidak akan dihapus.')
                        ->action(function ($record): void {
                            if (static::userHasRelatedData((int) $record->id)) {
                                Notification::make()
                                    ->title('Tidak dapat dihapus')
                                    ->body('User masih memiliki data terkait.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $record->delete();

                            Notification::make()
                                ->title('User berhasil dihapus')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => static::isSuperAdmin()),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->visible(fn () => static::isSuperAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->action(function ($records, $livewire) {
                            if (! static::isSuperAdmin()) {
                                $records = $records->reject(fn ($record) => static::isTargetUserSuperAdmin($record));
                            }

                            $deleted = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    if (static::userHasRelatedData((int) $record->id)) {
                                        $failed++;

                                        continue;
                                    }
                                    $record->delete();
                                    $deleted++;
                                } catch (Exception) {
                                    $failed++;
                                }
                            }

                            if ($deleted > 0) {
                                Notification::make()->title("{$deleted} user dihapus")->success()->send();
                            }
                            if ($failed > 0) {
                                Notification::make()->title("{$failed} user tidak dapat dihapus")->warning()->send();
                            }

                            $livewire->dispatch('$refresh');
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_deactivate_permanent')
                        ->label('Nonaktifkan Permanen')
                        ->icon('heroicon-o-archive-box-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records, $livewire): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! static::isSuperAdmin() && static::isTargetUserSuperAdmin($record)) {
                                    continue;
                                }
                                if ($record->status === 'terminated') {
                                    continue;
                                }
                                $record->update([
                                    'status' => 'terminated',
                                    'expire_date' => now(),
                                    'last_working_date' => now()->toDateString(),
                                ]);
                                $count++;
                            }

                            Notification::make()
                                ->title("{$count} user dinonaktifkan")
                                ->success()
                                ->send();

                            $livewire->dispatch('$refresh');
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->selectCurrentPageOnly()
            ->recordTitleAttribute('name')
            ->searchOnBlur();
    }

    /**
     * Cek constraint hanya saat aksi dijalankan — bukan saat render tiap baris.
     */
    private static function userHasRelatedData(int $userId): bool
    {
        $tablesToCheck = [
            'nota_dinas' => ['approved_by', 'pengirim_id'],
            'leave_requests' => ['user_id', 'replacement_employee_id'],
            'payrolls' => ['user_id'],
            'leave_balances' => ['user_id'],
            'annual_summaries' => ['user_id'],
        ];

        foreach ($tablesToCheck as $table => $columns) {
            if (! DBSchema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (DB::table($table)->where($column, $userId)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }
}
