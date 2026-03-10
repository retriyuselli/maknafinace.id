<?php

namespace App\Filament\Resources\FixedAssets\Tables;

use App\Models\FixedAsset;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FixedAssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('asset_code')
            ->columns([
                TextColumn::make('asset_code')
                    ->label('Kode Aset')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->copyable(),

                TextColumn::make('asset_name')
                    ->label('Nama Aset')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(30),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn ($state) => FixedAsset::CATEGORIES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'BUILDING' => 'success',
                        'EQUIPMENT' => 'info',
                        'FURNITURE' => 'warning',
                        'VEHICLE' => 'danger',
                        'COMPUTER' => 'primary',
                        default => 'gray'
                    }),

                TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->numeric()
                    ->prefix('Rp. ')
                    ->sortable(),

                TextColumn::make('current_book_value')
                    ->label('Nilai Buku')
                    ->numeric()
                    ->prefix('Rp. ')
                    ->sortable()
                    ->color(fn ($record) => $record->current_book_value <= $record->salvage_value ? 'danger' : 'success'),

                TextColumn::make('condition')
                    ->label('Kondisi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => FixedAsset::CONDITIONS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'EXCELLENT' => 'success',
                        'GOOD' => 'info',
                        'FAIR' => 'warning',
                        'POOR' => 'danger',
                        'DAMAGED' => 'gray',
                        default => 'gray'
                    }),

                TextColumn::make('location')
                    ->label('Lokasi')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('purchase_date')
                    ->label('Tanggal Beli')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(FixedAsset::CATEGORIES),

                SelectFilter::make('condition')
                    ->label('Kondisi')
                    ->options(FixedAsset::CONDITIONS),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua aset')
                    ->trueLabel('Hanya aktif')
                    ->falseLabel('Hanya tidak aktif'),

                Filter::make('needs_maintenance')
                    ->label('Perlu Maintenance')
                    ->query(fn (Builder $query): Builder => $query->needsMaintenance())
                    ->toggle(),

                Filter::make('purchase_date')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('purchased_from')
                            ->label('Dibeli dari'),
                        \Filament\Forms\Components\DatePicker::make('purchased_until')
                            ->label('Dibeli sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['purchased_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '>=', $date),
                            )
                            ->when(
                                $data['purchased_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('purchase_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('depreciate')
                        ->label('Hitung Penyusutan')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->action(function (FixedAsset $record) {
                            $monthlyDepreciation = $record->calculateMonthlyDepreciation();
                            $record->accumulated_depreciation += $monthlyDepreciation;
                            $record->updateBookValue();

                            Notification::make()
                                ->title('Penyusutan Dihitung')
                                ->body('Penyusutan bulanan: IDR '.number_format($monthlyDepreciation))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(fn (FixedAsset $record) => ! $record->isFullyDepreciated()),

                    // Action::make('create_purchase_journal')
                    //     ->label('Buat Jurnal Pembelian')
                    //     ->icon('heroicon-o-document-plus')
                    //     ->color('info')
                    //     ->action(function (FixedAsset $record) {
                    //         $journal = $record->createPurchaseJournalEntry();

                    //         if ($journal) {
                    //             Notification::make()
                    //                 ->title('Jurnal Pembelian Dibuat')
                    //                 ->body("Batch: {$journal->batch_number}")
                    //                 ->success()
                    //                 ->actions([
                    //                     Action::make('view')
                    //                         ->label('Lihat Jurnal')
                    //                         ->url(fn (): string => route('filament.admin.resources.journal-batches.edit', $journal)),
                    //                 ])
                    //                 ->send();
                    //         } else {
                    //             Notification::make()
                    //                 ->title('Jurnal Sudah Ada')
                    //                 ->body('Jurnal pembelian untuk aset ini sudah dibuat sebelumnya')
                    //                 ->warning()
                    //                 ->send();
                    //         }
                    //     })
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Buat Jurnal Pembelian Aset')
                    //     ->modalDescription('Ini akan membuat jurnal entry untuk pembelian aset tetap'),

                    // Action::make('create_depreciation_journal')
                    //     ->label('Buat Jurnal Penyusutan')
                    //     ->icon('heroicon-o-document-text')
                    //     ->color('warning')
                    //     ->action(function (FixedAsset $record) {
                    //         $monthlyDepreciation = $record->calculateMonthlyDepreciation();
                    //         $journal = $record->createDepreciationJournalEntry($monthlyDepreciation);

                    //         if ($journal) {
                    //             Notification::make()
                    //                 ->title('Jurnal Penyusutan Dibuat')
                    //                 ->body("Batch: {$journal->batch_number}, Jumlah: IDR ".number_format($monthlyDepreciation))
                    //                 ->success()
                    //                 ->actions([
                    //                     Action::make('view')
                    //                         ->label('Lihat Jurnal')
                    //                         ->url(fn (): string => route('filament.admin.resources.journal-batches.edit', $journal)),
                    //                 ])
                    //                 ->send();
                    //         } else {
                    //             Notification::make()
                    //                 ->title('Tidak Ada Penyusutan')
                    //                 ->body('Aset ini sudah sepenuhnya tersusut atau tidak ada penyusutan')
                    //                 ->warning()
                    //                 ->send();
                    //         }
                    //     })
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Buat Jurnal Penyusutan')
                    //     ->modalDescription('Ini akan membuat jurnal entry untuk penyusutan bulanan aset')
                    //     ->visible(fn (FixedAsset $record) => ! $record->isFullyDepreciated()),

                    DeleteAction::make(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('bulk_depreciate')
                        ->label('Hitung Penyusutan untuk Terpilih')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->action(function ($records) {
                            $total = 0;
                            foreach ($records as $record) {
                                if (! $record->isFullyDepreciated()) {
                                    $monthlyDepreciation = $record->calculateMonthlyDepreciation();
                                    $record->accumulated_depreciation += $monthlyDepreciation;
                                    $record->updateBookValue();
                                    $total += $monthlyDepreciation;
                                }
                            }

                            Notification::make()
                                ->title('Penyusutan Bulk Dihitung')
                                ->body('Total penyusutan: IDR '.number_format($total))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Tambah Aset Tetap Pertama')
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultPaginationPageOption(25);
    }
}
