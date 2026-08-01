<?php

namespace App\Filament\Resources\KoreksiAbsensis\Pages;

use App\Filament\Resources\KoreksiAbsensis\KoreksiAbsensiResource;
use App\Models\Absensi;
use App\Models\User;
use App\Services\KoreksiAbsensiService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateKoreksiAbsensi extends CreateRecord
{
    protected static string $resource = KoreksiAbsensiResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $user */
        $user = User::query()->findOrFail($data['user_id']);
        /** @var Absensi $absensi */
        $absensi = Absensi::query()->findOrFail($data['absensi_id']);

        if ((int) $absensi->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'absensi_id' => ['Absensi tidak cocok dengan karyawan yang dipilih.'],
            ]);
        }

        return app(KoreksiAbsensiService::class)->ajukan($user, $absensi, [
            'jam_masuk_diajukan' => $data['jam_masuk_diajukan'] ?? null,
            'jam_pulang_diajukan' => $data['jam_pulang_diajukan'] ?? null,
            'alasan' => $data['alasan'],
        ]);
    }
}
