<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ProductResource::mutateFormDataBeforeSave($data);
    }

    protected function afterCreate(): void
    {
        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
    }
}
