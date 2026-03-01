<?php

namespace App\Filament\Resources\NotaDinas\Tables;

use App\Filament\Resources\NotaDinas\Pages\ViewNd;
use App\Models\NotaDinas;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotaDinasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_nd')
                    ->label('Nomor ND')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kategori_nd')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'BIS' => 'success',
                        'OPS' => 'info',
                        'ADM' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => NotaDinas::getKategoriOptions()[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('pengirim.name')
                    ->label('Pengirim')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('penerima.name')
                    ->label('Penerima')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sifat')
                    ->label('Sifat')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Segera' => 'danger',
                        'Biasa' => 'success',
                        'Rahasia' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('hal')
                    ->label('Hal')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('details_count')
                    ->label('Jumlah Detail')
                    ->getStateUsing(function ($record) {
                        return $record->details_count ?? 0;
                    })
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state <= 3 => 'warning',
                        $state <= 6 => 'success',
                        default => 'primary',
                    })
                    ->icon(fn (int $state): string => match (true) {
                        $state === 0 => 'heroicon-o-minus-circle',
                        $state <= 3 => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-check-circle',
                    })
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('nd_upload')
                    ->label('File Upload')
                    ->getStateUsing(function ($record) {
                        return $record->nd_upload ? 'Ada' : 'Tidak';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ada' => 'success',
                        'Tidak' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Ada' => 'heroicon-o-document-check',
                        'Tidak' => 'heroicon-o-document-minus',
                        default => 'heroicon-o-document',
                    })
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'diajukan' => 'warning',
                        'disetujui' => 'success',
                        'dibayar' => 'primary',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'draft' => 'heroicon-o-pencil',
                        'diajukan' => 'heroicon-o-paper-airplane',
                        'disetujui' => 'heroicon-o-check-circle',
                        'dibayar' => 'heroicon-o-banknotes',
                        'ditolak' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-document',
                    }),
                TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label('Waktu Persetujuan')
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kategori_nd')
                    ->label('Kategori')
                    ->options(NotaDinas::getKategoriOptions()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'diajukan' => 'Diajukan',
                        'disetujui' => 'Disetujui',
                        'dibayar' => 'Dibayar',
                        'ditolak' => 'Ditolak',
                    ]),
                SelectFilter::make('pengirim')
                    ->label('Pengirim')
                    ->relationship('pengirim', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('tanggal')
                    ->schema([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(function (NotaDinas $record): bool {
                        $uid = Auth::id();
                        if (! $uid) {
                            return false;
                        }
                        $isSuperAdmin = DB::table('model_has_roles')
                            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                            ->where('model_has_roles.model_type', User::class)
                            ->where('model_has_roles.model_id', $uid)
                            ->where('roles.name', 'super_admin')
                            ->exists();

                        return $record->status === 'diajukan' && $isSuperAdmin;
                    })
                    ->requiresConfirmation()
                    ->action(function (NotaDinas $record): void {
                        $record->update([
                            'status' => 'disetujui',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(function (NotaDinas $record): bool {
                        $uid = Auth::id();
                        if (! $uid) {
                            return false;
                        }
                        $isSuperAdmin = DB::table('model_has_roles')
                            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                            ->where('model_has_roles.model_type', User::class)
                            ->where('model_has_roles.model_id', $uid)
                            ->where('roles.name', 'super_admin')
                            ->exists();

                        return $record->status === 'diajukan' && $isSuperAdmin;
                    })
                    ->requiresConfirmation()
                    ->action(function (NotaDinas $record): void {
                        $record->update([
                            'status' => 'ditolak',
                        ]);
                    }),
                Action::make('view_approval')
                    ->label('Lihat Approval')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (NotaDinas $record): string => ViewNd::getUrl(['record' => $record]))
                    ->openUrlInNewTab(),
                Action::make('download_file')
                    ->label('Download File')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (NotaDinas $record): bool => ! empty($record->nd_upload))
                    ->url(fn (NotaDinas $record): string => asset('storage/'.$record->nd_upload))
                    ->openUrlInNewTab(),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->icon('heroicon-m-trash')
                    ->tooltip('Hapus Nota Dinas')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Nota Dinas')
                    ->modalDescription('Apakah Anda yakin ingin menghapus Nota Dinas ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('danger')
                    ->visible(function (NotaDinas $record): bool {
                        $detailCount = $record->details()->count();

                        return $detailCount === 0;
                    })
                    ->before(function (?NotaDinas $record) {
                        if (! $record) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Data Nota Dinas tidak ditemukan. Silakan refresh halaman dan coba lagi.')
                                ->persistent()
                                ->send();

                            return false;
                        }

                        Notification::make()
                            ->info()
                            ->title('Memproses')
                            ->body('Memvalidasi Nota Dinas untuk penghapusan...')
                            ->send();
                    })
                    ->action(function (?NotaDinas $record) {
                        if (! $record) {
                            Notification::make()
                                ->danger()
                                ->title('Penghapusan Gagal')
                                ->body('Data Nota Dinas tidak ditemukan. Mungkin sudah dihapus atau dipindahkan.')
                                ->persistent()
                                ->send();

                            return false;
                        }

                        $detailCount = $record->details()->count();
                        if ($detailCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak Dapat Menghapus Nota Dinas')
                                ->body("Nota Dinas ini tidak dapat dihapus karena memiliki {$detailCount} detail record. Silakan hapus semua detail terlebih dahulu.")
                                ->persistent()
                                ->send();

                            return false;
                        }

                        try {
                            $record->delete();

                            Notification::make()
                                ->success()
                                ->title('Nota Dinas Dihapus')
                                ->body('Nota Dinas telah berhasil dihapus.')
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Penghapusan Gagal')
                                ->body('Terjadi kesalahan saat menghapus Nota Dinas: '.$e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),
                RestoreAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Pulihkan Nota Dinas')
                    ->modalDescription('Apakah Anda yakin ingin memulihkan Nota Dinas yang dihapus ini?')
                    ->modalSubmitActionLabel('Ya, pulihkan')
                    ->modalIcon('heroicon-o-arrow-path')
                    ->modalIconColor('success')
                    ->successNotificationTitle('Nota Dinas Dipulihkan'),
                ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen Nota Dinas')
                    ->modalDescription('Apakah Anda yakin ingin MENGHAPUS PERMANEN Nota Dinas ini? Tindakan ini tidak dapat dibatalkan dan akan juga menghapus semua detail terkait.')
                    ->modalSubmitActionLabel('Ya, hapus permanen')
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('danger')
                    ->before(function (NotaDinas $record) {
                        $detailCount = $record->details()->withTrashed()->count();
                        if ($detailCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title('Peringatan Penghapusan Berantai')
                                ->body("⚠️ Ini akan menghapus permanen Nota Dinas dan {$detailCount} detail terkait. Tindakan ini TIDAK DAPAT DIBATALKAN!")
                                ->persistent()
                                ->send();
                        }
                    })
                    ->action(function (NotaDinas $record) {
                        try {
                            $detailCount = $record->details()->withTrashed()->count();
                            $record->forceDelete();

                            Notification::make()
                                ->success()
                                ->title('Dihapus Permanen')
                                ->body("Nota Dinas dan {$detailCount} detail terkait telah dihapus permanen.")
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Penghapusan Paksa Gagal')
                                ->body('Terjadi kesalahan: '.$e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Nota Dinas Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus record Nota Dinas yang dipilih? Hanya record tanpa detail yang akan dihapus.')
                        ->modalSubmitActionLabel('Ya, hapus yang dipilih')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->modalIconColor('danger')
                        ->before(function ($livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            $protectedRecords = [];
                            $deletableCount = 0;

                            foreach ($selectedRecords as $record) {
                                $detailCount = $record->details()->count();
                                if ($detailCount > 0) {
                                    $protectedRecords[] = $record->no_nd." ({$detailCount} detail)";
                                } else {
                                    $deletableCount++;
                                }
                            }

                            if (! empty($protectedRecords)) {
                                $message = "Nota Dinas berikut tidak dapat dihapus karena memiliki detail terkait:\n\n";
                                $message .= '• '.implode("\n• ", $protectedRecords);

                                if ($deletableCount > 0) {
                                    $message .= "\n\n{$deletableCount} record tanpa detail akan dihapus.";
                                }

                                Notification::make()
                                    ->warning()
                                    ->title('Beberapa Record Dilindungi')
                                    ->body($message)
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->action(function ($livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            $deletedCount = 0;
                            $protectedCount = 0;

                            foreach ($selectedRecords as $record) {
                                $detailCount = $record->details()->count();
                                if ($detailCount === 0) {
                                    try {
                                        $record->delete();
                                        $deletedCount++;
                                    } catch (Exception $e) {
                                    }
                                } else {
                                    $protectedCount++;
                                }
                            }

                            if ($deletedCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Penghapusan Massal Selesai')
                                    ->body("{$deletedCount} record Nota Dinas berhasil dihapus.".
                                           ($protectedCount > 0 ? " {$protectedCount} record dilindungi dari penghapusan." : ''))
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Tidak Ada Record Dihapus')
                                    ->body('Semua record yang dipilih memiliki detail terkait dan tidak dapat dihapus.')
                                    ->send();
                            }
                        }),
                    RestoreBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Pulihkan Nota Dinas Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin memulihkan record Nota Dinas yang dihapus dan dipilih?')
                        ->modalSubmitActionLabel('Ya, pulihkan yang dipilih')
                        ->modalIcon('heroicon-o-arrow-path')
                        ->modalIconColor('success')
                        ->successNotificationTitle('Record Dipulihkan')
                        ->action(function ($livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            $restoredCount = 0;

                            foreach ($selectedRecords as $record) {
                                try {
                                    $record->restore();
                                    $restoredCount++;
                                } catch (Exception $e) {
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Pemulihan Massal Selesai')
                                ->body("{$restoredCount} record Nota Dinas berhasil dipulihkan.")
                                ->send();
                        }),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Permanen Nota Dinas Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin MENGHAPUS PERMANEN record Nota Dinas yang dipilih? Tindakan ini tidak dapat dibatalkan dan akan juga menghapus semua detail terkait.')
                        ->modalSubmitActionLabel('Ya, hapus permanen')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->modalIconColor('danger')
                        ->before(function ($livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            $recordsWithDetails = [];
                            $totalDetails = 0;

                            foreach ($selectedRecords as $record) {
                                $detailCount = $record->details()->withTrashed()->count();
                                if ($detailCount > 0) {
                                    $recordsWithDetails[] = $record->no_nd." ({$detailCount} detail)";
                                    $totalDetails += $detailCount;
                                }
                            }

                            if (! empty($recordsWithDetails)) {
                                $message = "⚠️ PERINGATAN: Nota Dinas berikut memiliki detail terkait yang juga akan dihapus permanen:\n\n";
                                $message .= '• '.implode("\n• ", $recordsWithDetails);
                                $message .= "\n\nTotal detail yang akan dihapus: {$totalDetails}";
                                $message .= "\n\nTindakan ini TIDAK DAPAT DIBATALKAN!";

                                Notification::make()
                                    ->danger()
                                    ->title('Peringatan Penghapusan Berantai')
                                    ->body($message)
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->action(function ($livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            $deletedCount = 0;
                            $totalDetailsDeleted = 0;
                            $errorCount = 0;

                            foreach ($selectedRecords as $record) {
                                try {
                                    $detailCount = $record->details()->withTrashed()->count();
                                    $record->forceDelete();
                                    $deletedCount++;
                                    $totalDetailsDeleted += $detailCount;
                                } catch (Exception $e) {
                                    $errorCount++;
                                }
                            }

                            if ($deletedCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Penghapusan Paksa Selesai')
                                    ->body("{$deletedCount} Nota Dinas dan {$totalDetailsDeleted} detail terkait telah dihapus permanen.".
                                           ($errorCount > 0 ? " {$errorCount} record gagal dihapus." : ''))
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Penghapusan Paksa Gagal')
                                    ->body('Tidak ada record yang dihapus. Silakan periksa error.')
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
