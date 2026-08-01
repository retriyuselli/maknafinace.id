<?php

namespace App\Filament\Resources\LokasiAbsensis\Pages;

use App\Filament\Resources\LokasiAbsensis\LokasiAbsensiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLokasiAbsensi extends EditRecord
{
    protected static string $resource = LokasiAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
