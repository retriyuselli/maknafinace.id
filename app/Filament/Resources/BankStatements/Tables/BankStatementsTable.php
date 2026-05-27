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
use Illuminate\Support\Facades\DB;
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
                TextColumn::make('match_percentage')
                    ->label('Tingkat Kecocokan')
                    ->getStateUsing(function (BankStatement $record): string {
                        // Hanya hitung jika ada data rekonsiliasi
                        if ($record->reconciliation_status === 'processing') {
                            return 'processing';
                        }
                        if (empty($record->reconciliation_file) || $record->total_records == 0) {
                            return 'none';
                        }
                        if ($record->reconciliation_status === 'failed') {
                            return 'failed';
                        }

                        // UNION COUNT — satu query untuk semua 5 tabel sumber
                        $result = DB::selectOne('
                            SELECT SUM(cnt) AS total FROM (
                                SELECT COUNT(*) AS cnt FROM data_pembayarans
                                    WHERE matched_bank_item_id IN (SELECT id FROM bank_reconciliation_items WHERE bank_reconciliation_id = ?)
                                UNION ALL
                                SELECT COUNT(*) AS cnt FROM pendapatan_lains
                                    WHERE matched_bank_item_id IN (SELECT id FROM bank_reconciliation_items WHERE bank_reconciliation_id = ?)
                                UNION ALL
                                SELECT COUNT(*) AS cnt FROM expenses
                                    WHERE matched_bank_item_id IN (SELECT id FROM bank_reconciliation_items WHERE bank_reconciliation_id = ?)
                                UNION ALL
                                SELECT COUNT(*) AS cnt FROM expense_ops
                                    WHERE matched_bank_item_id IN (SELECT id FROM bank_reconciliation_items WHERE bank_reconciliation_id = ?)
                                UNION ALL
                                SELECT COUNT(*) AS cnt FROM pengeluaran_lains
                                    WHERE matched_bank_item_id IN (SELECT id FROM bank_reconciliation_items WHERE bank_reconciliation_id = ?)
                            ) AS counts
                        ', array_fill(0, 5, $record->id));

                        $matched = (int) ($result->total ?? 0);
                        $total   = (int) $record->total_records;
                        $pct     = $total > 0 ? round($matched / $total * 100, 1) : 0;

                        // Encode sebagai "pct|matched|total" untuk formatStateUsing
                        return "{$pct}|{$matched}|{$total}";
                    })
                    ->formatStateUsing(function (string $state): HtmlString {
                        // State khusus
                        if ($state === 'none') {
                            return new HtmlString('<span class="text-gray-400 text-xs">—</span>');
                        }
                        if ($state === 'processing') {
                            return new HtmlString(
                                '<span class="inline-flex items-center gap-1 text-blue-600 text-xs">'.
                                '<svg class="animate-spin h-3 w-3" viewBox="0 0 24 24" fill="none">'.
                                '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>'.
                                'Memproses…</span>'
                            );
                        }
                        if ($state === 'failed') {
                            return new HtmlString('<span class="text-red-500 text-xs font-medium">Gagal</span>');
                        }

                        [$pct, $matched, $total] = explode('|', $state);
                        $pct = (float) $pct;

                        // Warna berdasarkan persentase
                        [$barColor, $textColor, $bgColor] = match (true) {
                            $pct >= 85 => ['bg-green-500',  'text-green-700',  'bg-green-100'],
                            $pct >= 60 => ['bg-yellow-400', 'text-yellow-700', 'bg-yellow-100'],
                            $pct >= 30 => ['bg-orange-400', 'text-orange-700', 'bg-orange-100'],
                            default    => ['bg-red-400',    'text-red-700',    'bg-red-100'],
                        };

                        // Progress bar width (bulatkan ke 5% terdekat agar tidak terlalu presisi secara visual)
                        $barWidth = min(100, max(0, (int) round($pct)));

                        return new HtmlString(
                            '<div class="flex flex-col items-center gap-1 min-w-20">'.
                            // Badge persentase
                            '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold '.$textColor.' '.$bgColor.'">'.
                            number_format($pct, 1).'%</span>'.
                            // Progress bar
                            '<div class="w-full bg-gray-200 rounded-full h-1.5">'.
                            '<div class="'.$barColor.' h-1.5 rounded-full transition-all" style="width:'.$barWidth.'%"></div>'.
                            '</div>'.
                            // Label cocok/total
                            '<span class="text-gray-400 text-xs leading-none">'.$matched.'/'.$total.' cocok</span>'.
                            '</div>'
                        );
                    })
                    ->alignCenter()
                    ->sortable(false),
                TextColumn::make('paymentMethod.no_rekening')
                    ->label('No. Rekening / Pemilik')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (mixed $_state, BankStatement $record): HtmlString {
                        if (! $record->paymentMethod) {
                            return new HtmlString('<span class="text-gray-400">—</span>');
                        }

                        $pm      = $record->paymentMethod;
                        $rekening = e($pm->bank_name.' - '.$pm->no_rekening);
                        $pemilik  = e($pm->name ?? '');

                        return new HtmlString(
                            '<div class="flex flex-col gap-0.5">'.
                            '<span class="font-medium text-sm">'.$rekening.'</span>'.
                            ($pemilik ? '<span class="text-xs text-gray-400">'.$pemilik.'</span>' : '').
                            '</div>'
                        );
                    }),
                TextColumn::make('period_start')
                    ->label('Periode')
                    ->sortable()
                    ->formatStateUsing(function (mixed $_state, BankStatement $record): HtmlString {
                        $start = $record->period_start?->format('d M Y') ?? '—';
                        $end   = $record->period_end?->format('d M Y') ?? '—';

                        return new HtmlString(
                            '<div class="flex flex-col gap-0.5">'.
                            '<span class="text-sm font-medium">'.$start.'</span>'.
                            '<span class="text-xs text-gray-400">'.$end.'</span>'.
                            '</div>'
                        );
                    }),
                TextColumn::make('branch')
                    ->label('Cabang')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('opening_balance')
                    ->label('Saldo Awal / Akhir')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(function (mixed $_state, BankStatement $record): HtmlString {
                        $fmt = fn (?int $val): string => 'Rp '.number_format((int) $val, 0, ',', '.');

                        return new HtmlString(
                            '<div class="flex flex-col gap-0.5 items-end">'.
                            '<span class="text-sm font-medium">'.$fmt($record->opening_balance).'</span>'.
                            '<span class="text-xs text-gray-400">'.$fmt($record->closing_balance).'</span>'.
                            '</div>'
                        );
                    }),
                TextColumn::make('tot_debit')
                    ->label('Total Debit / Kredit')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(function (mixed $_state, BankStatement $record): HtmlString {
                        $fmt = fn (?int $val): string => 'Rp '.number_format((int) $val, 0, ',', '.');

                        $txDebit  = $record->no_of_debit  ? '('.$record->no_of_debit.' tx)'  : '';
                        $txCredit = $record->no_of_credit ? '('.$record->no_of_credit.' tx)' : '';

                        return new HtmlString(
                            '<div class="flex flex-col gap-0.5 items-end">'.
                            '<span class="text-sm font-medium text-danger-600">'.$fmt($record->tot_debit).'<span class="text-xs font-normal text-gray-400 ml-1">'.$txDebit.'</span></span>'.
                            '<span class="text-xs font-medium text-success-600">'.$fmt($record->tot_credit).'<span class="text-xs font-normal text-gray-400 ml-1">'.$txCredit.'</span></span>'.
                            '</div>'
                        );
                    }),
                TextColumn::make('reconciliation_status')
                    ->label('Status Rekonsiliasi')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'uploaded'   => 'warning',
                        'processing' => 'info',
                        'completed'  => 'success',
                        'failed'     => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? (BankStatement::getReconciliationStatusOptions()[$state] ?? $state)
                        : '-'
                    ),

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
