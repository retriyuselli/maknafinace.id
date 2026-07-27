<?php

namespace App\Filament\Resources\LogAbsensis\Pages;

use App\Filament\Resources\LogAbsensis\LogAbsensiResource;
use Filament\Resources\Pages\EditRecord;

class EditLogAbsensi extends EditRecord
{
    protected static string $resource = LogAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
