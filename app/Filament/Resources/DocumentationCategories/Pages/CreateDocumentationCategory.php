<?php

namespace App\Filament\Resources\DocumentationCategories\Pages;

use App\Filament\Resources\DocumentationCategories\DocumentationCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentationCategory extends CreateRecord
{
    protected static string $resource = DocumentationCategoryResource::class;
}
