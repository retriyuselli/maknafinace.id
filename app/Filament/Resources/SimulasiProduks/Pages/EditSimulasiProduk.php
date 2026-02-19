<?php

namespace App\Filament\Resources\SimulasiProduks\Pages;

use App\Filament\Resources\SimulasiProduks\SimulasiProdukResource;
use App\Models\SimulasiProduk;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSimulasiProduk extends EditRecord
{
    protected static string $resource = SimulasiProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            Action::make('refresh_from_product')
                ->label('Refresh dari Produk')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (SimulasiProduk $record) {
                    if (! $record->product) {
                        return;
                    }

                    $totalPrice = (int) ($record->product->price ?? 0);
                    $promo = (int) ($record->promo ?? 0);
                    $penambahan = (int) ($record->penambahan ?? 0);
                    $pengurangan = (int) ($record->pengurangan ?? 0);
                    $grandTotal = $totalPrice + $penambahan - $promo - $pengurangan;

                    $record->update([
                        'total_price' => $totalPrice,
                        'grand_total' => $grandTotal,
                    ]);

                    $this->fillForm();

                    Notification::make()
                        ->title('Harga simulasi berhasil di-refresh dari produk')
                        ->success()
                        ->send();
                }),
            Action::make('penawaran')
                ->label('Preview')
                ->color('success')
                ->icon('heroicon-o-eye')
                ->url(fn (SimulasiProduk $record) => route('simulasi.show', $record))
                ->openUrlInNewTab(),
            Action::make('draftKontrak')
                ->label('Draft Kontrak')
                ->color('primary')
                ->icon('heroicon-o-document-text')
                ->url(fn (SimulasiProduk $record) => route('simulasi.draft-kontrak', $record))
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if ($record && $record->product) {
            $totalPrice = (int) ($record->product->price ?? 0);
            $promo = (int) ($record->promo ?? 0);
            $penambahan = (int) ($record->penambahan ?? 0);
            $pengurangan = (int) ($record->pengurangan ?? 0);
            $grandTotal = $totalPrice + $penambahan - $promo - $pengurangan;

            $data['total_price'] = $totalPrice;
            $data['grand_total'] = $grandTotal;
        }

        return $data;
    }
}