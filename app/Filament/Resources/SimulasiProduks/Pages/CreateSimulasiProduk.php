<?php

namespace App\Filament\Resources\SimulasiProduks\Pages;

use App\Filament\Resources\SimulasiProduks\SimulasiProdukResource;
use App\Models\Prospect;
use App\Models\SimulasiProduk;
use Filament\Resources\Pages\CreateRecord;

class CreateSimulasiProduk extends CreateRecord
{
    protected static string $resource = SimulasiProdukResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['name']);

        $base = (string) ($data['slug'] ?? '');
        if ($base === '' && isset($data['prospect_id'])) {
            $base = (string) Prospect::query()->whereKey($data['prospect_id'])->value('name_event');
        }

        if ($base !== '') {
            $data['slug'] = SimulasiProduk::generateUniqueSlug($base);
        }

        return $data;
    }
}
