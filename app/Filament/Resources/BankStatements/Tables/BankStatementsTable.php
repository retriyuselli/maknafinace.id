<?php

namespace App\Filament\Resources\BankStatements\Tables;

use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Models\BankStatement;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class BankStatementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('period_start', 'desc')
            ->columns([
                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),
                TextColumn::make('paymentMethod.no_rekening')
                    ->label('No. Rekening')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, BankStatement $record): string {
                        if ($record->paymentMethod) {
                            return $record->paymentMethod->bank_name.' - '.$record->paymentMethod->no_rekening;
                        }
                        return '-';
                    }),
                TextColumn::make('paymentMethod.name')
                    ->label('Pemilik'),
                TextColumn::make('period_start')
                    ->label('Tanggal Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('period_end')
                    ->label('Tanggal Akhir')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('branch')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('opening_balance')
                    ->label('Saldo Awal')
                    ->prefix('Rp. ')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('closing_balance')
                    ->label('Saldo Akhir')
                    ->prefix('Rp. ')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('no_of_debit')
                    ->label('Jumlah Debit')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->suffix(' transaksi')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tot_debit')
                    ->label('Total Debit')
                    ->prefix('Rp. ')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->color('danger'),
                TextColumn::make('no_of_credit')
                    ->label('Jumlah Kredit')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->suffix(' transaksi')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tot_credit')
                    ->label('Total Kredit')
                    ->prefix('Rp. ')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->color('success'),
                TextColumn::make('source_type')
                    ->label('Tipe Sumber')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pdf' => 'danger',
                        'excel' => 'success',
                        'manual_input' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => BankStatement::getSourceTypeOptions()[$state] ?? $state),

                TextColumn::make('reconciliation_status')
                    ->label('Status Rekonsiliasi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'uploaded' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => BankStatement::getReconciliationStatusOptions()[$state] ?? $state),

                TextColumn::make('total_records')
                    ->label('Total Records')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->suffix(' records')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reconciliation_original_filename')
                    ->label('File Rekonsiliasi')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if (! $state || ! $record->reconciliation_file) {
                            return new HtmlString('<span class="text-gray-400">Tidak ada</span>');
                        }
                        $fileName = $state;
                        $url = route('bank-statements.reconciliation.download', $record);

                        return new HtmlString(
                            '<div class="flex items-center space-x-2">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                <a href="'.$url.'" target="_blank" class="text-blue-600 hover:text-blue-800 truncate max-w-32" title="'.htmlspecialchars($fileName).'">
                                    '.\Illuminate\Support\Str::limit(htmlspecialchars($fileName), 20).'
                                </a>
                            </div>'
                        );
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('lastEditedBy.name')
                    ->label('Terakhir Diedit Oleh')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ?? 'System'),

                TextColumn::make('updated_at')
                    ->label('Waktu Edit Terakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->tooltip(fn ($record) => $record->updated_at?->format('d F Y H:i:s')),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('d F Y H:i:s')),
            ])
            ->filters([
                SelectFilter::make('payment_method_id')
                    ->relationship(
                        'paymentMethod',
                        'no_rekening',
                        fn ($query) => $query->whereNotNull('no_rekening')->where('no_rekening', '!=', '')
                    )
                    ->label('Rekening Bank')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('Pilih Rekening Bank')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->no_rekening ? ($record->bank_name.' - '.$record->no_rekening) : 'Nomor rekening tidak tersedia'),

                Filter::make('period_date')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('period_start_from')
                            ->label('Periode Mulai Dari')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('period_end_until')
                            ->label('Periode Selesai Hingga')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['period_start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('period_start', '>=', $date),
                            )
                            ->when(
                                $data['period_end_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('period_end', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['period_start_from'] ?? null) {
                            $indicators['period_start_from'] = 'Periode mulai dari '.Carbon::parse($data['period_start_from'])->format('d M Y');
                        }
                        if ($data['period_end_until'] ?? null) {
                            $indicators['period_end_until'] = 'Periode selesai hingga '.Carbon::parse($data['period_end_until'])->format('d M Y');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('source_type')
                    ->label('Sumber File')
                    ->options(BankStatement::getSourceTypeOptions()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(BankStatement::getStatusOptions())
                    ->multiple(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat Detail')
                        ->color('info')
                        ->tooltip('Lihat detail rekening koran'),
                    EditAction::make()
                        ->label('Edit')
                        ->color('warning')
                        ->tooltip('Edit rekening koran'),
                    Action::make('reconcile_comparison')
                        ->label('Rekonsiliasi Perbandingan')
                        ->icon('heroicon-o-scale')
                        ->color('primary')
                        ->visible(fn (BankStatement $record): bool => $record->payment_method_id &&
                            $record->reconciliationItems()->count() > 0
                        )
                        ->tooltip('Bandingkan transaksi aplikasi dengan mutasi bank')
                        ->url(fn (BankStatement $record): string => BankStatementResource::getUrl('reconciliation', ['record' => $record]))
                        ->openUrlInNewTab(false),
                    Action::make('download')
                        ->label('Unduh File')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->url(fn (BankStatement $record): string => $record->file_path ? route('bank-statements.download', $record) : '#')
                        ->openUrlInNewTab()
                        ->visible(fn (BankStatement $record): bool => ! empty($record->file_path))
                        ->tooltip('Unduh file rekening koran'),
                    \Filament\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->color('danger')
                        ->tooltip('Hapus rekening koran')
                        ->modalHeading('Hapus Rekening Koran')
                        ->modalDescription('Apakah Anda yakin ingin menghapus rekening koran ini? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Hapus'),
                ])
                    ->tooltip('Aksi Rekening Koran')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Rekening Koran Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus rekening koran yang dipilih?')
                        ->modalSubmitActionLabel('Ya, hapus'),
                ])->label('Aksi Massal'),
            ])
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('Belum ada rekening koran')
            ->emptyStateDescription('Mulai dengan membuat rekening koran pertama Anda untuk melacak transaksi keuangan.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Rekening Koran Baru')
                    ->url(BankStatementResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
