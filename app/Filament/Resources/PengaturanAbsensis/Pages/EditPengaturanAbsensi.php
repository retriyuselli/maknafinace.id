<?php

namespace App\Filament\Resources\PengaturanAbsensis\Pages;

use App\Filament\Resources\PengaturanAbsensis\PengaturanAbsensiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPengaturanAbsensi extends EditRecord
{
    protected static string $resource = PengaturanAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
