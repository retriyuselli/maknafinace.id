<?php

namespace App\Filament\Resources\NotaDinasDetails\Pages;

use App\Filament\Resources\NotaDinasDetails\NotaDinasDetailResource;
use Filament\Actions\CreateAction;
use App\Filament\Resources\NotaDinasDetails\Widgets\NotaDinasDetailOverviewWidget;
use Filament\Resources\Pages\ListRecords;

class ListNotaDinasDetails extends ListRecords
{
    protected static string $resource = NotaDinasDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            NotaDinasDetailOverviewWidget::class,
        ];
    }
}
