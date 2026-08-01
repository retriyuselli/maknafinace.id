<?php

namespace App\Filament\Resources\KoreksiAbsensis\Pages;

use App\Filament\Resources\KoreksiAbsensis\KoreksiAbsensiResource;
use App\Models\KoreksiAbsensi;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKoreksiAbsensis extends ListRecords
{
    protected static string $resource = KoreksiAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['status'] = KoreksiAbsensi::STATUS_MENUNGGU;

                    return $data;
                }),
        ];
    }
}
