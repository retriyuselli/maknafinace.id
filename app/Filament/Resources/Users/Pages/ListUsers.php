<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Widget expiration dimatikan di list — menambah 1 request + query berat.
     * Stats bisa dilihat di dashboard jika diperlukan.
     */
    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
