<?php

namespace App\Filament\Resources\JadwalKerjas\Pages;

use App\Filament\Resources\JadwalKerjas\JadwalKerjaResource;
use App\Models\JadwalKerja;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJadwalKerja extends EditRecord
{
    protected static string $resource = JadwalKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['default'])) {
            JadwalKerja::query()
                ->where('default', true)
                ->whereKeyNot($this->record->getKey())
                ->update(['default' => false]);
        }

        return $data;
    }
}
