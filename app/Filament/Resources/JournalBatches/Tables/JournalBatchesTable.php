<?php

namespace App\Filament\Resources\JournalBatches\Tables;

use App\Models\JournalBatch;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('transaction_date', 'desc')
            ->columns([
                TextColumn::make('batch_number')
                    ->label('Nomor Batch')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->copyable(),

                TextColumn::make('transaction_date')
                    ->label('Tanggal Transaksi')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('total_debit')
                    ->label('Total Debit')
                    ->prefix('Rp. ')
                    ->sortable()
                    ->numeric()
                    ->alignEnd(),

                TextColumn::make('total_credit')
                    ->label('Total Kredit')
                    ->prefix('Rp. ')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'draft' => 'warning',
                        'posted' => 'success',
                        'reversed' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'reversed' => 'Reversed',
                        default => $state
                    }),

                TextColumn::make('reference_type')
                    ->label('Jenis Referensi')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(),

                TextColumn::make('approvedBy.name')
                    ->label('Disetujui Oleh')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Status Jurnal')
                    ->placeholder('Hanya Aktif')
                    ->trueLabel('Hanya Terhapus')
                    ->falseLabel('Dengan Terhapus'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'reversed' => 'Reversed',
                    ]),

                Filter::make('transaction_date')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('post_journal')
                        ->label('Post Jurnal')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (JournalBatch $record) {
                            if ($record->isBalanced()) {
                                $record->update(['status' => 'posted']);

                                Notification::make()
                                    ->title('Jurnal Berhasil Di-Post')
                                    ->body("Batch {$record->batch_number} telah di-post")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Jurnal Tidak Seimbang')
                                    ->body('Debit dan Kredit harus seimbang untuk posting')
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn (JournalBatch $record) => $record->status === 'draft'),

                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
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
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Buat Jurnal Pertama')
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
