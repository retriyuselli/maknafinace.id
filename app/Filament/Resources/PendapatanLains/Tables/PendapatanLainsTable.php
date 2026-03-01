<?php

namespace App\Filament\Resources\PendapatanLains\Tables;

use App\Models\PendapatanLain;
use App\Models\Vendor;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PendapatanLainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tgl_bayar', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pendapatan')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable()
                    ->description(fn (PendapatanLain $record): ?string => Str::limit($record->keterangan, 50)),

                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->placeholder('Tidak ada vendor')
                    ->toggleable(),

                TextColumn::make('nominal')
                    ->label('Jumlah')
                    ->numeric()
                    ->prefix('Rp. ')
                    ->sortable()
                    ->color('success')
                    ->alignment(Alignment::End)
                    ->summarize(
                        Sum::make()->prefix('Rp. ')
                    ),

                TextColumn::make('tgl_bayar')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('paymentMethod.name')
                    ->label('Metode Pembayaran')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('warning'),

                ImageColumn::make('image')
                    ->label('Bukti')
                    ->square()
                    ->size(60)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('kategori_transaksi')
                    ->label('Tipe')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (string $state): string => Str::title(str_replace('_', ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_method_id')
                    ->label('Metode Pembayaran')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->options(function () {
                        return Vendor::where('status', 'vendor')
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Filter::make('tgl_bayar')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('Dari Tanggal')
                            ->placeholder('Pilih tanggal mulai'),
                        DatePicker::make('date_until')
                            ->label('Sampai Tanggal')
                            ->placeholder('Pilih tanggal akhir'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn (Builder $query, $date): Builder => $query->whereDate('tgl_bayar', '>=', $date))
                            ->when($data['date_until'], fn (Builder $query, $date): Builder => $query->whereDate('tgl_bayar', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators['from'] = 'Dari: '.Carbon::parse($data['date_from'])->format('d M Y');
                        }
                        if ($data['date_until'] ?? null) {
                            $indicators['until'] = 'Sampai: '.Carbon::parse($data['date_until'])->format('d M Y');
                        }

                        return $indicators;
                    }),

                Filter::make('nominal_range')
                    ->label('Rentang Nominal')
                    ->schema([
                        TextInput::make('nominal_from')
                            ->label('Nominal Minimum')
                            ->numeric()
                            ->prefix('IDR'),
                        TextInput::make('nominal_until')
                            ->label('Nominal Maximum')
                            ->numeric()
                            ->prefix('IDR'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['nominal_from'], fn (Builder $query, $amount): Builder => $query->where('nominal', '>=', $amount))
                            ->when($data['nominal_until'], fn (Builder $query, $amount): Builder => $query->where('nominal', '<=', $amount));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['nominal_from'] ?? null) {
                            $indicators['from'] = 'Min: IDR '.number_format($data['nominal_from'], 0, ',', '.');
                        }
                        if ($data['nominal_until'] ?? null) {
                            $indicators['until'] = 'Max: IDR '.number_format($data['nominal_until'], 0, ',', '.');
                        }

                        return $indicators;
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat')
                        ->color('info')
                        ->tooltip('Lihat detail pendapatan'),
                    EditAction::make()
                        ->label('Edit')
                        ->color('warning')
                        ->tooltip('Edit pendapatan'),
                    ReplicateAction::make()
                        ->label('Duplikasi')
                        ->color('gray')
                        ->tooltip('Duplikasi pendapatan')
                        ->schema([
                            DatePicker::make('tgl_bayar')
                                ->label('Tanggal Pendapatan Baru')
                                ->default(now())
                                ->required(),
                            TextInput::make('name')
                                ->label('Nama Pendapatan')
                                ->required(),
                        ])
                        ->beforeReplicaSaved(function (array $data): array {
                            $data['tgl_bayar'] = $data['tgl_bayar'] ?? now();

                            return $data;
                        }),
                    DeleteAction::make()
                        ->label('Hapus')
                        ->color('danger')
                        ->tooltip('Hapus pendapatan'),
                    RestoreAction::make()
                        ->label('Pulihkan')
                        ->color('success')
                        ->tooltip('Pulihkan pendapatan'),
                    ForceDeleteAction::make()
                        ->label('Hapus Permanen')
                        ->color('danger')
                        ->tooltip('Hapus permanen'),
                ])
                    ->tooltip('Aksi Pendapatan')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Pendapatan Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus pendapatan yang dipilih?')
                        ->modalSubmitActionLabel('Ya, hapus'),
                    ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Permanen Pendapatan')
                        ->modalDescription('Tindakan ini tidak dapat dibatalkan!')
                        ->modalSubmitActionLabel('Ya, hapus permanen'),
                    RestoreBulkAction::make()
                        ->label('Pulihkan Terpilih')
                        ->requiresConfirmation()
                        ->modalHeading('Pulihkan Pendapatan')
                        ->modalDescription('Pendapatan yang dipilih akan dipulihkan.')
                        ->modalSubmitActionLabel('Ya, pulihkan'),
                ])->label('Aksi Massal'),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Tambah Pendapatan Pertama')
                    ->icon('heroicon-o-plus'),
            ])
            ->poll('60s')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50]);
    }
}
