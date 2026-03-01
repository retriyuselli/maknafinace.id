<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Models\LeaveRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user && ! $user->roles->contains('name', 'super_admin')) {
                    $query->where('user_id', $user->id);
                }
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Tanggal Selesai')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('total_days')
                    ->label('Hari')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('documents')
                    ->label('Dokumen')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return 'Tidak ada dokumen';
                        }
                        $count = is_array($state) ? count($state) : 0;

                        return $count.' file';
                    })
                    ->badge()
                    ->color(function ($state) {
                        return empty($state) ? 'gray' : 'success';
                    })
                    ->icon(function ($state) {
                        return empty($state) ? 'heroicon-o-document' : 'heroicon-o-document-text';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'pending' => 'Menunggu',
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default => $state,
                        };
                    })
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                    ]),

                TextColumn::make('replacementEmployee.name')
                    ->label('Pengganti')
                    ->placeholder('Tidak ada pengganti')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('emergency_contact')
                    ->label('Kontak Darurat')
                    ->placeholder('Tidak disediakan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),

                TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('Belum disetujui')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),

                SelectFilter::make('leave_type_id')
                    ->label('Jenis Cuti')
                    ->relationship('leaveType', 'name'),

                SelectFilter::make('user_id')
                    ->label('Karyawan')
                    ->relationship('user', 'name')
                    ->searchable(),

                SelectFilter::make('replacement_employee_id')
                    ->label('Karyawan Pengganti')
                    ->relationship('replacementEmployee', 'name')
                    ->searchable(),

                Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->label('Lihat'),
                \Filament\Actions\EditAction::make()
                    ->label('Edit'),
                \Filament\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(function () {
                        $user = Auth::user();

                        return $user ? $user->roles->contains('name', 'super_admin') : false;
                    }),

                \Filament\Actions\Action::make('view_documents')
                    ->label('Dokumen')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(function (LeaveRequest $record) {
                        return ! empty($record->documents);
                    })
                    ->modalHeading('Dokumen Pendukung')
                    ->modalContent(function (LeaveRequest $record) {
                        $documents = $record->documents ?? [];
                        $documentLinks = [];

                        foreach ($documents as $document) {
                            $documentLinks[] = '<div class="mb-2">
                                <a href="'.asset('storage/'.$document).'" 
                                   target="_blank" 
                                   class="text-blue-600 hover:text-blue-800 underline flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    '.basename($document).'
                                </a>
                            </div>';
                        }

                        return view('filament.components.document-list', [
                            'documents' => $documents,
                            'documentLinks' => $documentLinks,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                \Filament\Actions\Action::make('approve')
                    ->label('Setuju')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (LeaveRequest $record) {
                        $user = Auth::user();
                        $isSuperAdmin = $user ? $user->roles->contains('name', 'super_admin') : false;

                        return $record->status === 'pending' && $isSuperAdmin;
                    })
                    ->action(function (LeaveRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => Auth::id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Permohonan cuti disetujui')
                            ->success()
                            ->send();
                    }),

                \Filament\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(function (LeaveRequest $record) {
                        $user = Auth::user();
                        $isSuperAdmin = $user ? $user->roles->contains('name', 'super_admin') : false;

                        return $record->status === 'pending' && $isSuperAdmin;
                    })
                    ->action(function (LeaveRequest $record) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => Auth::id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Permohonan cuti ditolak')
                            ->success()
                            ->send();
                    }),

                \Filament\Actions\Action::make('view_approval')
                    ->label('Lihat Persetujuan')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->visible(function (LeaveRequest $record) {
                        return $record->status === 'approved';
                    })
                    ->url(fn (LeaveRequest $record) => route('leave-request.approval-detail', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->visible(function () {
                            $user = Auth::user();

                            return $user ? $user->roles->contains('name', 'super_admin') : false;
                        }),

                    \Filament\Actions\BulkAction::make('approve_bulk')
                        ->label('Setujui Terpilih')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(function () {
                            $user = Auth::user();

                            return $user ? $user->roles->contains('name', 'super_admin') : false;
                        })
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'approved',
                                        'approved_by' => Auth::id(),
                                    ]);
                                }
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Permohonan cuti terpilih telah disetujui')
                                ->success()
                                ->send();
                        }),

                    \Filament\Actions\BulkAction::make('reject_bulk')
                        ->label('Tolak Terpilih')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(function () {
                            $user = Auth::user();

                            return $user ? $user->roles->contains('name', 'super_admin') : false;
                        })
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'rejected',
                                        'approved_by' => Auth::id(),
                                    ]);
                                }
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Permohonan cuti terpilih telah ditolak')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
