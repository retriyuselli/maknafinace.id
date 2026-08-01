<?php

namespace App\Filament\Resources\JadwalKerjas\Pages;

use App\Filament\Resources\JadwalKerjas\JadwalKerjaResource;
use App\Filament\Resources\JadwalKerjas\Schemas\JadwalKerjaForm;
use App\Models\JadwalKerja;
use Filament\Resources\Pages\CreateRecord;

class CreateJadwalKerja extends CreateRecord
{
    protected static string $resource = JadwalKerjaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['default'])) {
            JadwalKerja::query()->where('default', true)->update(['default' => false]);
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'aktif' => true,
            'hariJadwalKerjas' => JadwalKerjaForm::defaultHariRows(),
        ]);
    }
}
