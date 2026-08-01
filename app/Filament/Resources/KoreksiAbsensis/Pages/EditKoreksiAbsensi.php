<?php

namespace App\Filament\Resources\KoreksiAbsensis\Pages;

use App\Filament\Resources\KoreksiAbsensis\KoreksiAbsensiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKoreksiAbsensi extends EditRecord
{
    protected static string $resource = KoreksiAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
