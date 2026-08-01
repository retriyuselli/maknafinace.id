<?php

namespace App\Filament\Resources\LokasiAbsensis\Pages;

use App\Filament\Resources\LokasiAbsensis\LokasiAbsensiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLokasiAbsensis extends ListRecords
{
    protected static string $resource = LokasiAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
