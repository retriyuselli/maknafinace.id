<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Models\Expense;
use App\Models\JournalBatch;
use App\Services\OrderJournalService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.prospect.name_event')
                    ->numeric()
                    ->searchable()
                    ->label('Project')
                    ->sortable()
                    ->copyable()
                    ->weight(FontWeight::Bold)
                    ->copyMessage('Project copied')
                    ->description(fn (Expense $record): ?string => Str::limit($record->note, 50))
                    ->formatStateUsing(fn ($state) => $state ?? 'No Project'),

                TextColumn::make('vendor.name')
                    ->searchable()
                    ->label('Vendor')
                    ->copyable()
                    ->badge()
                    ->color('primary')
                    ->copyMessage('Vendor copied')
                    ->formatStateUsing(fn ($state) => $state ?? 'No Vendor'),

                TextColumn::make('no_nd')
                    ->searchable()
                    ->label('Nomor ND'),

                TextColumn::make('paymentMethod.bank_name')
                    ->searchable()
                    ->label('Sumber Pembayaran')
                    ->description(fn ($record) => $record->paymentMethod?->no_rekening ?? 'N/A')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ?? 'No Bank'),

                TextColumn::make('date_expense')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal')
                    ->color('primary'),

                TextColumn::make('amount')
                    ->numeric()
                    ->formatStateUsing(fn (string $state): string => 'Rp. '.number_format($state, 0, ',', '.'))
                    ->label('Nominal')
                    ->summarize([
                        Sum::make()
                            ->formatStateUsing(fn (string $state): string => 'Rp. '.number_format($state, 0, ',', '.')),
                    ])
                    ->sortable()
                    ->alignment('right')
                    ->color(fn ($state) => $state > 5000000 ? 'danger' : 'success'),

                ImageColumn::make('image')
                    ->square()
                    ->label('Proof'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('void_expense')
                        ->label('Batalkan (Void)')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Batalkan Expense')
                        ->modalDescription('Tindakan ini akan membuat jurnal reversal dan menghapus (soft delete) expense agar angka operasional kembali benar.')
                        ->modalSubmitActionLabel('Ya, Batalkan')
                        ->form([
                            Textarea::make('reason')
                                ->label('Alasan')
                                ->required()
                                ->maxLength(500),
                        ])
                        ->action(function (Expense $record, array $data): void {
                            Gate::authorize('update', $record);

                            $ok = app(OrderJournalService::class)->reverseJournal('expense', $record->id, $data['reason']);

                            if (! $ok) {
                                Notification::make()
                                    ->danger()
                                    ->title('Pembatalan gagal')
                                    ->body('Jurnal expense tidak ditemukan atau tidak bisa dibatalkan.')
                                    ->send();

                                return;
                            }

                            $record->delete();

                            Notification::make()
                                ->success()
                                ->title('Expense dibatalkan')
                                ->body('Jurnal reversal dibuat dan expense dihapus (soft delete).')
                                ->send();
                        })
                        ->visible(function (Expense $record): bool {
                            if ($record->trashed()) {
                                return false;
                            }

                            return JournalBatch::where('reference_type', 'expense')
                                ->where('reference_id', $record->id)
                                ->where('status', 'posted')
                                ->exists();
                        }),
                    DeleteAction::make()
                        ->visible(function (Expense $record): bool {
                            if ($record->trashed()) {
                                return false;
                            }

                            return ! JournalBatch::where('reference_type', 'expense')
                                ->where('reference_id', $record->id)
                                ->where('status', 'posted')
                                ->exists();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('Apakah Anda yakin ingin menghapus expense yang dipilih? Data tidak bisa dikembalikan.')
                        ->modalSubmitActionLabel('Ya, Hapus')
                        ->modalCancelActionLabel('Batal'),

                    RestoreBulkAction::make()
                        ->label('Pulihkan Terpilih')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success'),

                    ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription('PERHATIAN: Data akan dihapus secara permanen dan tidak dapat dikembalikan!')
                        ->modalSubmitActionLabel('Ya, Hapus Permanen')
                        ->modalCancelActionLabel('Batal'),

                    BulkAction::make('export_excel')
                        ->label('Export ke Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function ($records) {
                            return response()->streamDownload(function () use ($records) {
                                $csv = "Vendor,Keterangan,No ND,Tanggal,Nominal,Sumber Pembayaran\n";
                                foreach ($records as $record) {
                                    $csv .= sprintf(
                                        '%s,%s,%s,%s,%s,%s',
                                        $record->vendor?->name ?? 'N/A',
                                        $record->note,
                                        $record->no_nd,
                                        $record->date_expense?->format('d/m/Y') ?? 'N/A',
                                        number_format($record->amount, 0, ',', '.'),
                                        $record->paymentMethod?->bank_name ?? 'N/A'
                                    )."\n";
                                }
                                echo $csv;
                            }, 'expense_export_'.now()->format('Y-m-d_H-i-s').'.csv');
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('calculate_total')
                        ->label('Hitung Total')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->action(function ($records) {
                            $total = $records->sum('amount');
                            $count = $records->count();

                            Notification::make()
                                ->title('Kalkulasi Selesai')
                                ->body("Total dari {$count} expense terpilih: Rp ".number_format($total, 0, ',', '.'))
                                ->success()
                                ->icon('heroicon-o-banknotes')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('mark_verified')
                        ->label('Tandai Terverifikasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $updated = 0;
                            foreach ($records as $record) {
                                $record->update(['verified_at' => now()]);
                                $updated++;
                            }

                            Notification::make()
                                ->title('Verifikasi Berhasil')
                                ->body("{$updated} expense telah ditandai sebagai terverifikasi")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalDescription('Tandai expense terpilih sebagai terverifikasi?')
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('update_period')
                        ->label('Update Periode')
                        ->icon('heroicon-o-calendar')
                        ->color('warning')
                        ->form([
                            \Filament\Forms\Components\DatePicker::make('new_date')
                                ->label('Tanggal Baru')
                                ->required()
                                ->default(now()),
                        ])
                        ->action(function ($records, array $data) {
                            $updated = 0;
                            foreach ($records as $record) {
                                $record->update(['date_expense' => $data['new_date']]);
                                $updated++;
                            }

                            Notification::make()
                                ->title('Update Berhasil')
                                ->body("{$updated} expense telah diupdate tanggalnya")
                                ->success()
                                ->send();
                        })
                        ->modalSubmitActionLabel('Update')
                        ->modalCancelActionLabel('Batal')
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('duplicate')
                        ->label('Duplikasi')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function ($records) {
                            $duplicated = 0;
                            foreach ($records as $record) {
                                $newRecord = $record->replicate();
                                $newRecord->note = $record->note.' (Copy)';
                                $newRecord->no_nd = $record->no_nd.'-COPY';
                                $newRecord->date_expense = now();
                                $newRecord->save();
                                $duplicated++;
                            }

                            Notification::make()
                                ->title('Duplikasi Berhasil')
                                ->body("{$duplicated} expense telah diduplikasi")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalDescription('Duplikasi expense terpilih dengan tanggal hari ini?')
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('generate_report')
                        ->label('Buat Laporan')
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->form([
                            \Filament\Forms\Components\TextInput::make('report_title')
                                ->label('Judul Laporan')
                                ->default('Laporan Pengeluaran')
                                ->required(),
                            \Filament\Forms\Components\Textarea::make('report_notes')
                                ->label('Catatan Laporan')
                                ->placeholder('Tambahkan catatan untuk laporan ini...')
                                ->rows(3),
                        ])
                        ->action(function ($records, array $data) {
                            $total = $records->sum('amount');
                            $vendors = $records->pluck('vendor.name')->filter()->unique();

                            $reportContent = [
                                'title' => $data['report_title'],
                                'generated_at' => now()->format('d/m/Y H:i:s'),
                                'total_records' => $records->count(),
                                'total_amount' => number_format($total, 0, ',', '.'),
                                'vendors_involved' => $vendors->count(),
                                'notes' => $data['report_notes'] ?? '',
                                'records' => $records->map(function ($record) {
                                    return [
                                        'vendor' => $record->vendor?->name ?? 'N/A',
                                        'note' => $record->note,
                                        'amount' => number_format($record->amount, 0, ',', '.'),
                                        'date' => $record->date_expense?->format('d/m/Y') ?? 'N/A',
                                    ];
                                })->toArray(),
                            ];

                            return response()->streamDownload(function () use ($reportContent) {
                                echo "=== {$reportContent['title']} ===\n\n";
                                echo "Dibuat pada: {$reportContent['generated_at']}\n";
                                echo "Total Records: {$reportContent['total_records']}\n";
                                echo "Total Amount: Rp {$reportContent['total_amount']}\n";
                                echo "Vendor Terlibat: {$reportContent['vendors_involved']}\n\n";

                                if (! empty($reportContent['notes'])) {
                                    echo "Catatan: {$reportContent['notes']}\n\n";
                                }

                                echo "=== DETAIL EXPENSES ===\n";
                                foreach ($reportContent['records'] as $record) {
                                    echo "- {$record['vendor']}: {$record['note']} (Rp {$record['amount']}) - {$record['date']}\n";
                                }
                            }, 'expense_report_'.now()->format('Y-m-d_H-i-s').'.txt');
                        })
                        ->modalSubmitActionLabel('Generate')
                        ->modalCancelActionLabel('Batal')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }
}
