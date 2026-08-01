<?php

namespace App\Filament\Resources\PaymentMethods\Tables;

use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Imports\BankStatementImport;
use App\Models\BankStatement;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class PaymentMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                IconColumn::make('is_cash')
                    ->label('Tunai')
                    ->boolean()
                    ->trueIcon('heroicon-o-banknotes')
                    ->falseIcon('heroicon-o-credit-card')
                    ->trueColor('warning')
                    ->falseColor('info'),
                TextColumn::make('name')
                    ->label('Nama Rekening')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('no_rekening')
                    ->label('Nomor Rekening')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nomor rekening disalin')
                    ->fontFamily('mono'),
                TextColumn::make('opening_balance')
                    ->label('Saldo Awal')
                    ->numeric()
                    ->prefix('Rp. ')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('opening_balance_date')
                    ->label('Tgl Pembukuan')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('saldo')
                    ->label('Saldo Saat Ini')
                    ->numeric()
                    ->prefix('Rp. ')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state == 0 ? 'warning' : 'success'))
                    ->description(fn ($record) => 'Perubahan: '.
                        ($record->perubahan_saldo >= 0 ? '+' : '').
                        'Rp '.number_format(abs($record->perubahan_saldo), 0, ',', '.')),
                TextColumn::make('status_perubahan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'naik' => 'success',
                        'turun' => 'danger',
                        'tetap' => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'naik' => 'heroicon-o-arrow-trending-up',
                        'turun' => 'heroicon-o-arrow-trending-down',
                        'tetap' => 'heroicon-o-minus',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'naik' => 'Naik',
                        'turun' => 'Turun',
                        'tetap' => 'Tetap',
                    }),
                TextColumn::make('cabang')
                    ->label('Cabang')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('is_cash')
                    ->label('Tampilkan Hanya Uang Tunai')
                    ->query(fn (Builder $query): Builder => $query->where('is_cash', true))
                    ->toggle(),
                Filter::make('saldo_positif')
                    ->label('Saldo Positif')
                    ->query(function (Builder $query): Builder {
                        return $query->whereRaw('
                            (opening_balance + 
                            COALESCE((SELECT SUM(nominal) FROM data_pembayarans WHERE payment_method_id = payment_methods.id AND tgl_bayar >= opening_balance_date AND deleted_at IS NULL), 0) +
                            COALESCE((SELECT SUM(nominal) FROM pendapatan_lains WHERE payment_method_id = payment_methods.id AND tgl_bayar >= opening_balance_date AND deleted_at IS NULL), 0) -
                            COALESCE((SELECT SUM(amount) FROM expenses WHERE payment_method_id = payment_methods.id AND date_expense >= opening_balance_date AND deleted_at IS NULL), 0) -
                            COALESCE((SELECT SUM(amount) FROM expense_ops WHERE payment_method_id = payment_methods.id AND date_expense >= opening_balance_date AND deleted_at IS NULL), 0) -
                            COALESCE((SELECT SUM(amount) FROM pengeluaran_lains WHERE payment_method_id = payment_methods.id AND date_expense >= opening_balance_date AND deleted_at IS NULL), 0)
                            ) > 0
                        ');
                    }),
                Filter::make('saldo_negatif')
                    ->label('Saldo Negatif')
                    ->query(function (Builder $query): Builder {
                        return $query->whereRaw('
                            (opening_balance + 
                            COALESCE((SELECT SUM(nominal) FROM data_pembayarans WHERE payment_method_id = payment_methods.id AND tgl_bayar >= opening_balance_date AND deleted_at IS NULL), 0) +
                            COALESCE((SELECT SUM(nominal) FROM pendapatan_lains WHERE payment_method_id = payment_methods.id AND tgl_bayar >= opening_balance_date AND deleted_at IS NULL), 0) -
                            COALESCE((SELECT SUM(amount) FROM expenses WHERE payment_method_id = payment_methods.id AND date_expense >= opening_balance_date AND deleted_at IS NULL), 0) -
                            COALESCE((SELECT SUM(amount) FROM expense_ops WHERE payment_method_id = payment_methods.id AND date_expense >= opening_balance_date AND deleted_at IS NULL), 0) -
                            COALESCE((SELECT SUM(amount) FROM pengeluaran_lains WHERE payment_method_id = payment_methods.id AND date_expense >= opening_balance_date AND deleted_at IS NULL), 0)
                            ) < 0
                        ');
                    }),
            ])
            ->recordActions([
                Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => PaymentMethodResource::getUrl('view', ['record' => $record]))
                    ->tooltip('Lihat detail lengkap rekening dengan tab Uang Masuk, Uang Keluar, dan Laporan'),
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('rekonsiliasi_bank')
                        ->label('Rekonsiliasi Bank')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn ($record) => ! $record->is_cash)
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Upload Mutasi Bank')
                                ->description('Upload file Excel (.xlsx) atau CSV dari mutasi bank untuk melakukan rekonsiliasi otomatis')
                                ->schema([
                                    \Filament\Forms\Components\FileUpload::make('mutasi_file')
                                        ->label('File Mutasi Bank')
                                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/vnd.ms-excel'])
                                        ->helperText('Upload file Excel (.xlsx/.xls) atau CSV dari bank. 
                                                     ✅ Format yang didukung:
                                                     • Bank Mandiri: Balance History & Transaction History
                                                     • BCA, BNI, BRI: Format standar
                                                     • Format generic dengan kolom: Tanggal, Keterangan, Debit/Kredit, Saldo')
                                        ->required()
                                        ->disk('public')
                                        ->directory('bank-statements')
                                        ->preserveFilenames()
                                        ->maxSize(10240),
                                    \Filament\Schemas\Components\Grid::make(2)->schema([
                                        \Filament\Forms\Components\DatePicker::make('periode_dari')
                                            ->label('Periode Dari')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->default(now()->startOfMonth()),
                                        \Filament\Forms\Components\DatePicker::make('periode_sampai')
                                            ->label('Periode Sampai')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->default(now()->endOfMonth()),
                                    ]),
                                    \Filament\Forms\Components\Textarea::make('catatan')
                                        ->label('Catatan Rekonsiliasi')
                                        ->placeholder('Tambahkan catatan atau informasi khusus untuk rekonsiliasi ini...')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->action(function ($record, $data) {
                            try {
                                $bankStatement = BankStatement::create([
                                    'payment_method_id' => $record->id,
                                    'period_start' => $data['periode_dari'],
                                    'period_end' => $data['periode_sampai'],
                                    'source_type' => 'excel',
                                    'file_path' => $data['mutasi_file'],
                                    'status' => 'pending',
                                    'uploaded_at' => now(),
                                    'opening_balance' => 0,
                                    'closing_balance' => 0,
                                    'no_of_debit' => 0,
                                    'tot_debit' => 0,
                                    'no_of_credit' => 0,
                                    'tot_credit' => 0,
                                    'branch' => null,
                                ]);

                                $import = new BankStatementImport($bankStatement);
                                Excel::import($import, storage_path('app/public/'.$data['mutasi_file']));

                                $stats = $import->getImportStats();
                                $hasErrors = ! empty($stats['errors']);
                                $finalStatus = $hasErrors ? 'failed' : 'parsed';

                                if ($stats['imported'] > 0 && ! $hasErrors) {
                                    $import->calculateTransactionAmounts();
                                    $import->updateBankStatementStatistics();
                                }

                                $bankStatement->update([
                                    'status' => $finalStatus,
                                ]);

                                if ($hasErrors) {
                                    Notification::make()
                                        ->title('Import Selesai dengan Error')
                                        ->body("Berhasil import {$stats['imported']} transaksi. ".
                                              "Namun ada {$stats['skipped']} baris yang error: ".
                                              implode(', ', array_slice($stats['errors'], 0, 3)).
                                              (count($stats['errors']) > 3 ? '...' : ''))
                                        ->warning()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Import Bank Statement Berhasil')
                                        ->body("Berhasil import {$stats['imported']} transaksi. ".
                                              ($stats['skipped'] ? "Ada {$stats['skipped']} transaksi yang dilewati." : ''))
                                        ->success()
                                        ->send();
                                }
                            } catch (Exception $e) {
                                Log::error('Bank Reconciliation Import Error', [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'payment_method_id' => $record->id,
                                    'file' => $data['mutasi_file'] ?? 'unknown',
                                ]);

                                Notification::make()
                                    ->title('Error Rekonsiliasi Bank')
                                    ->body('Gagal memproses file: '.$e->getMessage().
                                          '. Pastikan format file Excel sesuai dengan template yang diharapkan.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->modalHeading('Rekonsiliasi Bank')
                        ->modalDescription('Upload file mutasi bank untuk melakukan rekonsiliasi otomatis dengan transaksi sistem')
                        ->modalWidth('2xl'),
                    Action::make('export_transaksi')
                        ->label('Export Transaksi')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->action(function () {
                            Notification::make()
                                ->title('Export Transaksi')
                                ->body('Fitur export akan segera tersedia.')
                                ->info()
                                ->send();
                        }),
                ])
                    ->label('Aksi Lainnya')
                    ->color('gray')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-credit-card')
            ->emptyStateHeading('Tidak ada rekening ditemukan')
            ->emptyStateDescription('Silakan buat rekening baru untuk memulai mencatat transaksi keuangan.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Rekening Baru')
                    ->url(PaymentMethodResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50])
            ->striped()
            ->description('Kelola semua rekening bank dan kas tunai. Saldo dihitung otomatis berdasarkan transaksi masuk dan keluar.');
    }
}
