<?php

namespace App\Filament\Resources\NotaDinasDetails\Pages;

use App\Filament\Resources\NotaDinasDetails\NotaDinasDetailResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateNotaDinasDetail extends CreateRecord
{
    protected static string $resource = NotaDinasDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('panduan')
                ->label('Panduan')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalHeading('Panduan Pengisian Nota Dinas Detail')
                ->modalDescription('Ringkasan langkah dan aturan pengisian agar data sesuai penawaran dan realisasi.')
                ->modalWidth('4xl')
                ->modalContent(view('filament.modals.nota-dinas-guide'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }
}
