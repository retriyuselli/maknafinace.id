<?php

namespace App\Filament\Resources\PengaturanAbsensis\Pages;

use App\Filament\Resources\PengaturanAbsensis\PengaturanAbsensiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPengaturanAbsensis extends ListRecords
{
    protected static string $resource = PengaturanAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
