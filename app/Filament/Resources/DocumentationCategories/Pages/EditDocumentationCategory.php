<?php

namespace App\Filament\Resources\DocumentationCategories\Pages;

use App\Filament\Resources\DocumentationCategories\DocumentationCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentationCategory extends EditRecord
{
    protected static string $resource = DocumentationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
