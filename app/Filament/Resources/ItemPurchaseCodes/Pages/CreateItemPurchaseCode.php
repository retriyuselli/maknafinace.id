<?php

namespace App\Filament\Resources\ItemPurchaseCodes\Pages;

use App\Filament\Resources\ItemPurchaseCodes\ItemPurchaseCodeResource;
use App\Models\ItemPurchaseCode;
use App\Models\ProspectApp;
use App\Services\ItemPurchaseCodeService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateItemPurchaseCode extends CreateRecord
{
    protected static string $resource = ItemPurchaseCodeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $app = ProspectApp::query()->findOrFail($data['prospect_app_id']);
        $service = app(ItemPurchaseCodeService::class);

        if (! $service->isPaid($app)) {
            $app->forceFill([
                'tgl_mulai' => $data['starts_at'] ?? $app->tgl_mulai,
                'tgl_berakhir' => $data['ends_at'] ?? $app->tgl_berakhir,
            ])->saveQuietly();
        } else {
            $app->forceFill([
                'tgl_mulai' => $data['starts_at'] ?? $app->tgl_mulai,
                'tgl_berakhir' => $data['ends_at'] ?? $app->tgl_berakhir,
                'company_name' => $data['company_name'] ?? $app->company_name,
            ])->saveQuietly();
        }

        /** @var ItemPurchaseCode $code */
        $code = $service->issueForProspectApp($app, (bool) ($data['force_new'] ?? false));

        $code->forceFill([
            'company_name' => $data['company_name'] ?? $code->company_name,
            'package' => $data['package'] ?? $code->package,
            'domain' => ItemPurchaseCodeService::normalizeDomain($data['domain'] ?? $code->domain),
            'starts_at' => $data['starts_at'] ?? $code->starts_at,
            'ends_at' => $data['ends_at'] ?? $code->ends_at,
            'notes' => $data['notes'] ?? $code->notes,
        ])->save();

        return $code;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
