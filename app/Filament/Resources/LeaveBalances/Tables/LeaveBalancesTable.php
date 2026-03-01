<?php

namespace App\Filament\Resources\LeaveBalances\Tables;

use App\Models\LeaveBalance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeaveBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user && ! $user->roles->contains('name', 'super_admin')) {
                    $query->where('user_id', $user->id);
                } else {
                    $query->whereHas('user', function ($q) {
                        $q->whereHas('statuses', function ($q2) {
                            $q2->where('status_name', 'Karyawan');
                        });
                    });
                }
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.employee_id')
                    ->label('ID Karyawan')
                    ->sortable(false),
                TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('allocated_days')
                    ->label('Hak Cuti')
                    ->formatStateUsing(function (LeaveBalance $record) {
                        $allocated = $record->allocated_days;
                        $carryOver = $record->carried_over_days ?? 0;

                        $text = $allocated;
                        if ($carryOver > 0) {
                            $text .= " + {$carryOver}";
                        }

                        return $text.' hari';
                    })
                    ->description(function (LeaveBalance $record) {
                        $parts = [];
                        if ($record->carried_over_days > 0) {
                            $parts[] = 'Termasuk sisa thn lalu';
                        }

                        $pending = $record->histories()->where('status', 'pending')->sum('amount');
                        if ($pending > 0) {
                            $parts[] = "+ {$pending} hari (Pending)";
                        }

                        return implode(', ', $parts);
                    })
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('used_days')
                    ->label('Terpakai')
                    ->numeric()
                    ->suffix(' hari')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('remaining_days')
                    ->label('Sisa')
                    ->numeric()
                    ->suffix(' hari')
                    ->alignCenter()
                    ->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 2 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('usage_percentage')
                    ->label('Penggunaan')
                    ->formatStateUsing(fn (LeaveBalance $record): string => $record->usage_percentage.'%')
                    ->alignCenter()
                    ->color(fn (LeaveBalance $record): string => match (true) {
                        $record->usage_percentage >= 100 => 'danger',
                        $record->usage_percentage >= 80 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                IconColumn::make('is_critical')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user')
                    ->label('Karyawan')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('leave_type')
                    ->label('Jenis Cuti')
                    ->relationship('leaveType', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('critical_balance')
                    ->label('Saldo Kritis')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('allocated_days > 0 AND (used_days / allocated_days * 100) > 80')),
                Filter::make('exhausted_balance')
                    ->label('Saldo Habis')
                    ->query(fn (Builder $query): Builder => $query->where('remaining_days', '<=', 0)),
            ])
            ->headerActions([
                Action::make('auto_generate')
                    ->label('Auto Generate Saldo Cuti')
                    ->icon('heroicon-m-sparkles')
                    ->color('success')
                    ->action(function () {
                        $result = LeaveBalance::generateForAllUsers();

                        Notification::make()
                            ->title('Auto Generate Berhasil!')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Auto Generate Saldo Cuti')
                    ->modalDescription('Sistem akan otomatis membuat saldo cuti untuk semua karyawan berdasarkan jenis cuti yang tersedia. Data yang sudah ada akan diperbarui sesuai quota terbaru.')
                    ->modalSubmitActionLabel('Ya, Generate Otomatis')
                    ->visible(fn () => Auth::user()->roles->contains('name', 'super_admin')),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('add_quota')
                        ->label('Top Up Saldo')
                        ->icon('heroicon-m-plus-circle')
                        ->color('success')
                        ->form([
                            TextInput::make('days_to_add')
                                ->label('Jumlah Hari')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->helperText('Masukkan jumlah hari (misal: 1 untuk pengganti 1 hari kerja di hari libur)'),
                            DatePicker::make('date')
                                ->label('Tanggal')
                                ->default(now())
                                ->required(),
                            TextInput::make('reason')
                                ->label('Alasan / Keterangan')
                                ->required()
                                ->placeholder('Contoh: Masuk kerja di hari Minggu'),
                        ])
                        ->action(function (LeaveBalance $record, array $data) {
                            $isSuperAdmin = Auth::user()->roles->contains('name', 'super_admin');
                            $status = $isSuperAdmin ? 'approved' : 'pending';

                            if ($status === 'approved') {
                                $record->allocated_days += $data['days_to_add'];
                                $record->save();
                            }

                            $record->histories()->create([
                                'amount' => $data['days_to_add'],
                                'transaction_date' => $data['date'],
                                'reason' => $data['reason'],
                                'created_by' => Auth::id(),
                                'status' => $status,
                            ]);

                            $date = \Carbon\Carbon::parse($data['date'])->translatedFormat('d F Y');

                            if ($status === 'approved') {
                                Notification::make()
                                    ->title('Saldo Berhasil Ditambahkan')
                                    ->body("Menambahkan {$data['days_to_add']} hari untuk tanggal {$date}. Total Hak Cuti: {$record->allocated_days} hari.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Permintaan Top Up Berhasil')
                                    ->body("Permintaan top up {$data['days_to_add']} hari untuk tanggal {$date} telah diajukan dan menunggu persetujuan.")
                                    ->success()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Top Up Saldo Cuti')
                        ->modalDescription('Fitur ini digunakan untuk menambah saldo cuti secara manual, misalnya untuk Cuti Pengganti.')
                        ->visible(fn (LeaveBalance $record) => (Auth::user()->roles->contains('name', 'super_admin') || Auth::id() === $record->user_id) &&
                            ($record->leaveType->name === 'Cuti Pengganti' || stripos($record->leaveType->name, 'replacement') !== false)
                        ),

                    Action::make('history')
                        ->label('Riwayat Top Up')
                        ->icon('heroicon-m-clock')
                        ->color('info')
                        ->modalHeading('Riwayat Top Up Saldo')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(fn (LeaveBalance $record) => view('filament.resources.leave-balances.history-modal', ['record' => $record]))
                        ->visible(fn (LeaveBalance $record) => (Auth::user()->roles->contains('name', 'super_admin') || Auth::id() === $record->user_id) &&
                            ($record->leaveType->name === 'Cuti Pengganti' || stripos($record->leaveType->name, 'replacement') !== false)
                        ),
                    Action::make('recalculate')
                        ->label('Hitung Ulang')
                        ->icon('heroicon-m-calculator')
                        ->color('info')
                        ->action(function (LeaveBalance $record) {
                            $record->calculateUsedDays();

                            Notification::make()
                                ->title('Berhasil!')
                                ->body('Saldo cuti telah dihitung ulang.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Hitung Ulang Saldo Cuti')
                        ->modalDescription('Menghitung ulang cuti terpakai berdasarkan pengajuan cuti yang disetujui tahun ini.')
                        ->modalSubmitActionLabel('Ya, Hitung Ulang')
                        ->visible(fn () => Auth::user()->roles->contains('name', 'super_admin')),
                    EditAction::make()
                        ->label('Edit')
                        ->icon('heroicon-m-pencil-square')
                        ->visible(fn () => Auth::user()->roles->contains('name', 'super_admin')),
                    DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-m-trash')
                        ->visible(fn () => Auth::user()->roles->contains('name', 'super_admin')),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_recalculate')
                        ->label('Hitung Ulang Semua')
                        ->icon('heroicon-m-calculator')
                        ->color('info')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->calculateUsedDays();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => Auth::user()->roles->contains('name', 'super_admin')),
                    BulkAction::make('bulk_auto_generate')
                        ->label('Auto Generate untuk User Terpilih')
                        ->icon('heroicon-m-sparkles')
                        ->color('success')
                        ->action(function ($records) {
                            $userIds = $records->pluck('user_id')->unique();
                            $totalCreated = 0;
                            $totalUpdated = 0;

                            foreach ($userIds as $userId) {
                                $user = User::find($userId);
                                if ($user) {
                                    $result = LeaveBalance::generateForUser($user);
                                    $totalCreated += $result['created'];
                                    $totalUpdated += $result['updated'];
                                }
                            }

                            Notification::make()
                                ->title('Auto Generate Berhasil!')
                                ->body("Dibuat: {$totalCreated} record baru, Diperbarui: {$totalUpdated} record")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => Auth::user()->roles->contains('name', 'super_admin')),
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->roles->contains('name', 'super_admin')),
                ]),
            ])
            ->defaultSort('user.name', 'asc');
    }
}
