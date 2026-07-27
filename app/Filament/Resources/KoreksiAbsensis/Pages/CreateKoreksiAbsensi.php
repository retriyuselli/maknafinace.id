<?php

namespace App\Filament\Resources\KoreksiAbsensis\Pages;

use App\Filament\Resources\KoreksiAbsensis\KoreksiAbsensiResource;
use App\Models\KoreksiAbsensi;
use Filament\Resources\Pages\CreateRecord;

class CreateKoreksiAbsensi extends CreateRecord
{
    protected static string $resource = KoreksiAbsensiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = KoreksiAbsensi::STATUS_MENUNGGU;

        return $data;
    }
}
