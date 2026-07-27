<?php

namespace App\Filament\Resources\PenugasanJadwals\Pages;

use App\Filament\Resources\PenugasanJadwals\PenugasanJadwalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPenugasanJadwals extends ListRecords
{
    protected static string $resource = PenugasanJadwalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
