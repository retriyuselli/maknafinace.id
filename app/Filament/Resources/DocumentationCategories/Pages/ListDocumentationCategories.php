<?php

namespace App\Filament\Resources\DocumentationCategories\Pages;

use App\Filament\Resources\DocumentationCategories\DocumentationCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentationCategories extends ListRecords
{
    protected static string $resource = DocumentationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
