<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            Action::make('penawaran')
                ->label('Preview')
                ->color('success')
                ->icon('heroicon-o-eye')
                ->url(fn (Product $record): string => route('products.details', ['product' => $record, 'action' => 'preview'])) // <-- Use 'products.details'
                ->openUrlInNewTab(),
            Action::make('refresh_vendor_prices')
                ->label('Refresh Harga Vendor')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (Product $record) {
                    DB::transaction(function () use ($record) {
                        $record->load(['items.vendor', 'pengurangans', 'penambahanHarga.vendor']);

                        $itemsUpdated = 0;
                        $additionsUpdated = 0;

                        foreach ($record->items as $item) {
                            $vendor = $item->vendor;
                            if (! $vendor) {
                                continue;
                            }

                            $active = $vendor->activePrice();
                            $hargaPublish = (float) ($active?->harga_publish ?? $vendor->harga_publish ?? 0);
                            $hargaVendor = (float) ($active?->harga_vendor ?? $vendor->harga_vendor ?? 0);

                            $item->harga_publish = $hargaPublish;
                            $item->harga_vendor = $hargaVendor;
                            $item->price_public = $hargaPublish * (int) ($item->quantity ?? 1);
                            $item->save();
                            $itemsUpdated++;
                        }

                        foreach ($record->penambahanHarga as $addition) {
                            $vendor = $addition->vendor;
                            if (! $vendor) {
                                continue;
                            }

                            $active = $vendor->activePrice();
                            $addition->harga_publish = (float) ($active?->harga_publish ?? $vendor->harga_publish ?? 0);
                            $addition->harga_vendor = (float) ($active?->harga_vendor ?? $vendor->harga_vendor ?? 0);
                            $addition->save();
                            $additionsUpdated++;
                        }

                        $productPrice = (float) $record->items()->sum('price_public');
                        $pengurangan = (float) $record->pengurangans()->sum('amount');
                        $penambahanPublish = (float) $record->penambahanHarga()->sum('harga_publish');
                        $penambahanVendor = (float) $record->penambahanHarga()->sum('harga_vendor');

                        $record->product_price = $productPrice;
                        $record->pengurangan = $pengurangan;
                        $record->penambahan_publish = $penambahanPublish;
                        $record->penambahan_vendor = $penambahanVendor;
                        $record->price = $productPrice - $pengurangan + $penambahanPublish;
                        $record->save();

                        Log::info('Refresh vendor prices on product (edit)', [
                            'product_id' => $record->id,
                            'product_slug' => $record->slug,
                            'items_updated' => $itemsUpdated,
                            'additions_updated' => $additionsUpdated,
                            'user_id' => Auth::id(),
                            'timestamp' => now()->toIso8601String(),
                        ]);
                    });

                    Notification::make()
                        ->title('Harga vendor berhasil di-refresh')
                        ->body('Items: '.($record->items->count()).' • Penambahan: '.($record->penambahanHarga->count()))
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // $this->record is available in EditRecord context
        return ProductResource::mutateFormDataBeforeSave($data, $this->record->id);
    }

    protected function afterSave(): void
    {
        // Force a redirect to the new slug URL after saving to avoid "headers" error
        // and handle slug changes gracefully in Livewire 3
        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
    }
}
