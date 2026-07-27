<?php

namespace App\Filament\Resources\PengajuanLemburs\Pages;

use App\Filament\Resources\PengajuanLemburs\PengajuanLemburResource;
use App\Models\User;
use App\Services\PengajuanLemburService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePengajuanLembur extends CreateRecord
{
    protected static string $resource = PengajuanLemburResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $user */
        $user = User::query()->findOrFail($data['user_id']);

        return app(PengajuanLemburService::class)->ajukan($user, $data);
    }
}
