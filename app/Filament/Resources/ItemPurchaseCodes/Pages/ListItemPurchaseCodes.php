<?php

namespace App\Filament\Resources\ItemPurchaseCodes\Pages;

use App\Filament\Resources\ItemPurchaseCodes\ItemPurchaseCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListItemPurchaseCodes extends ListRecords
{
    protected static string $resource = ItemPurchaseCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Terbitkan kode')
                ->icon('heroicon-o-plus'),
        ];
    }
}
