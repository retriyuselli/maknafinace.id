<?php

namespace App\Filament\Resources\ExpenseOps\Tables;

use App\Exports\ExpenseOpsExport;
use App\Models\ExpenseOps;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseOpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => Str::title($state))
                    ->description(fn (ExpenseOps $record): ?string => Str::limit($record->note, 50))
                    ->toggleable(),

                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->badge()
                    ->sortable()
                    ->color('primary')
                    ->toggleable(),

                TextColumn::make('amount')
                    ->numeric()
                    ->prefix('Rp. ')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->formatStateUsing(fn ($state): string => 'Total: Rp. '.number_format($state, 0, ',', '.')),
                    ])
                    ->toggleable(),

                TextColumn::make('kategori_transaksi')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'uang_masuk' => 'success',
                        'uang_keluar' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'uang_masuk' => 'Uang Masuk',
                        'uang_keluar' => 'Uang Keluar',
                        default => $state,
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('paymentMethod.name')
                    ->label('Sumber Pembayaran')
                    ->formatStateUsing(function ($record) {
                        $method = $record->paymentMethod;
                        if (! $method) {
                            return 'N/A';
                        }

                        return $method->is_cash ? 'Kas/Tunai' : ($method->bank_name ? "{$method->bank_name}" : $method->name);
                    })
                    ->description(fn (ExpenseOps $record): string => $record->paymentMethod?->no_rekening ?? 'N/A')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('date_expense')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('no_nd')
                    ->label('Nota Dinas')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('Nomor nota dinas berhasil disalin'),

                TextColumn::make('notaDinas.status')
                    ->label('Status ND')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'diajukan' => 'warning',
                        'disetujui' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'diajukan' => 'Diajukan',
                        'disetujui' => 'Disetujui',
                        default => $state,
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('bank_name')
                    ->label('Bank Transfer')
                    ->searchable()
                    ->toggleable()
                    ->description(fn (ExpenseOps $record): string => $record->account_holder ?? 'N/A'),

                TextColumn::make('tanggal_transfer')
                    ->label('Tgl Transfer')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('note')
                    ->searchable()
                    ->toggleable()
                    ->wrap(),

                ImageColumn::make('image')
                    ->alignCenter()
                    ->square()
                    ->label('Bukti')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->defaultImageUrl(url('/images/placeholder.png')),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date_expense', 'desc')
            ->filters([
                SelectFilter::make('payment_method_id')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->label('Payment Method'),
                SelectFilter::make('kategori_transaksi')
                    ->options([
                        'uang_masuk' => 'Uang Masuk',
                        'uang_keluar' => 'Uang Keluar',
                    ])
                    ->multiple()
                    ->label('Transaction Category'),
                Filter::make('date_expense')
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(2)->schema([
                            DatePicker::make('date_from')
                                ->label('Date From'),
                            DatePicker::make('date_until')
                                ->label('Date Until')
                                ->default(now()),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn (Builder $query, $date): Builder => $query->whereDate('date_expense', '>=', $date))
                            ->when($data['date_until'], fn (Builder $query, $date): Builder => $query->whereDate('date_expense', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators['from'] = 'From '.Carbon::parse($data['date_from'])->toFormattedDateString();
                        }
                        if ($data['date_until'] ?? null) {
                            $indicators['until'] = 'Until '.Carbon::parse($data['date_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    })
                    ->label('Expense Date Range'),
                SelectFilter::make('amount')
                    ->label('Amount Range')
                    ->options([
                        'low' => 'Low (< Rp. 1,000,000)',
                        'medium' => 'Medium (Rp. 1,000,000 - 5,000,000)',
                        'high' => 'High (> Rp. 5,000,000)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'low' => $query->where('amount', '<', 1000000),
                            'medium' => $query->whereBetween('amount', [1000000, 5000000]),
                            'high' => $query->where('amount', '>', 5000000),
                            default => $query,
                        };
                    }),
                TrashedFilter::make(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->modalWidth('lg'),
                    EditAction::make(),
                    Action::make('duplicate')
                        ->label('Duplikat')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->action(function (ExpenseOps $record, array $data): void {
                            ExpenseOps::create([
                                'name' => $record->name.' (Copy)',
                                'amount' => $record->amount,
                                'payment_method_id' => $record->payment_method_id,
                                'date_expense' => now(),
                                'kategori_transaksi' => $record->kategori_transaksi,
                                'no_nd' => $record->no_nd + 1,
                                'note' => $record->note,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Duplikat Pengeluaran')
                        ->modalDescription('Apakah Anda yakin ingin menduplikat pengeluaran ini?'),
                    Action::make('download_receipt')
                        ->label('Download Bukti')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->url(fn (ExpenseOps $record): ?string => $record->image ? Storage::url($record->image) : null, shouldOpenInNewTab: true)
                        ->visible(fn (ExpenseOps $record): bool => $record->image !== null),
                    DeleteAction::make()
                        ->requiresConfirmation(),
                    RestoreAction::make(),
                    ForceDeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Permanen Pengeluaran')
                        ->modalDescription('Apakah Anda yakin ingin menghapus pengeluaran ini secara permanen? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, hapus permanen'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Permanen Pengeluaran Terpilih')
                        ->modalDescription('PERHATIAN: Data akan dihapus secara permanen dan tidak dapat dikembalikan!')
                        ->modalSubmitActionLabel('Ya, Hapus Permanen')
                        ->modalCancelActionLabel('Batal'),
                    BulkAction::make('export')
                        ->label('Export ke Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            return Excel::download(new ExpenseOpsExport($records), 'pengeluaran-operasional-'.date('Y-m-d').'.xlsx');
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('mark_as_uang_keluar')
                        ->label('Tandai sebagai Uang Keluar')
                        ->icon('heroicon-o-arrow-down')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['kategori_transaksi' => 'uang_keluar']);
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ])->label('Aksi Massal'),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Buat Pengeluaran Pertama')
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->emptyStateHeading('Belum Ada Pengeluaran Operasional')
            ->emptyStateDescription('Mulai dengan membuat pengeluaran operasional pertama Anda.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->poll('60s')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }
}
