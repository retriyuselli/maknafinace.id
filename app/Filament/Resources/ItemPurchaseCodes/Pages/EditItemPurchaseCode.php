<?php

namespace App\Filament\Resources\ItemPurchaseCodes\Pages;

use App\Enums\ItemPurchaseCodeStatus;
use App\Filament\Resources\ItemPurchaseCodes\ItemPurchaseCodeResource;
use App\Services\ItemPurchaseCodeService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditItemPurchaseCode extends EditRecord
{
    protected static string $resource = ItemPurchaseCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('revoke')
                ->label('Cabut kode')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => ! in_array($this->record->resolvedStatus(), [
                    ItemPurchaseCodeStatus::Revoked,
                    ItemPurchaseCodeStatus::Expired,
                ], true))
                ->action(function (): void {
                    app(ItemPurchaseCodeService::class)->revoke($this->record, 'Dicabut dari halaman edit.');

                    Notification::make()
                        ->title('Kode dicabut')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'revoked_at', 'notes']);
                }),
            DeleteAction::make(),
        ];
    }
}
