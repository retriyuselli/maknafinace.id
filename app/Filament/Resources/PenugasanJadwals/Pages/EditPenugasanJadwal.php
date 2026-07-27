<?php

namespace App\Filament\Resources\PenugasanJadwals\Pages;

use App\Filament\Resources\PenugasanJadwals\PenugasanJadwalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPenugasanJadwal extends EditRecord
{
    protected static string $resource = PenugasanJadwalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
