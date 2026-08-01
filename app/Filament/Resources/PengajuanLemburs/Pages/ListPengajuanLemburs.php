<?php

namespace App\Filament\Resources\PengajuanLemburs\Pages;

use App\Filament\Resources\PengajuanLemburs\PengajuanLemburResource;
use App\Services\PengajuanLemburService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListPengajuanLemburs extends ListRecords
{
    protected static string $resource = PengajuanLemburResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, PengajuanLemburService $service): Model {
                    $user = \App\Models\User::query()->findOrFail($data['user_id']);

                    return $service->ajukan($user, $data);
                }),
        ];
    }
}
