<?php

namespace App\Filament\Resources\PengajuanLemburs\Pages;

use App\Filament\Resources\PengajuanLemburs\PengajuanLemburResource;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPengajuanLembur extends EditRecord
{
    protected static string $resource = PengajuanLemburResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['mulai_pada']) && ! empty($data['selesai_pada'])) {
            $mulai = Carbon::parse($data['mulai_pada']);
            $selesai = Carbon::parse($data['selesai_pada']);
            $data['menit'] = max(0, $mulai->diffInMinutes($selesai));
        }

        return $data;
    }
}
