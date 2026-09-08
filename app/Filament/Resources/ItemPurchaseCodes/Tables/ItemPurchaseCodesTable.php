<?php

namespace App\Filament\Resources\ItemPurchaseCodes\Tables;

use App\Enums\ItemPurchaseCodeStatus;
use App\Filament\Resources\ItemPurchaseCodes\ItemPurchaseCodeResource;
use App\Models\ItemPurchaseCode;
use App\Services\ItemPurchaseCodeService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ItemPurchaseCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Item Purchase Code')
                    ->copyable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('company_name')
                    ->label('Perusahaan')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ItemPurchaseCode $record): string => $record->prospectApp?->full_name ?? ''),

                TextColumn::make('package')
                    ->label('Paket')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'hastana' => 'Hastana',
                        'non_hastana' => 'Non Hastana',
                        'lain_lain' => 'Lain-lain',
                        default => $state ? (string) $state : '-',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (ItemPurchaseCode $record) => $record->resolvedStatus()),

                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('activated_domain')
                    ->label('Domain aktif')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('activated_at')
                    ->label('Aktivasi')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_verified_at')
                    ->label('Cek terakhir')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ItemPurchaseCodeStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('revoke')
                        ->label('Cabut kode')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (ItemPurchaseCode $record): bool => ! in_array($record->resolvedStatus(), [
                            ItemPurchaseCodeStatus::Revoked,
                            ItemPurchaseCodeStatus::Expired,
                        ], true))
                        ->action(function (ItemPurchaseCode $record): void {
                            app(ItemPurchaseCodeService::class)->revoke($record, 'Dicabut dari daftar admin.');

                            Notification::make()
                                ->title('Kode dicabut')
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->striped()
            ->emptyStateHeading('Belum ada Item Purchase Code')
            ->emptyStateDescription('Kode terbit otomatis saat aplikasi prospek lunas, atau buat manual.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Terbitkan kode')
                    ->url(fn (): string => ItemPurchaseCodeResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
