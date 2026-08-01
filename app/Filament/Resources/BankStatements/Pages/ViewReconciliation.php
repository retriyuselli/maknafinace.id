<?php

namespace App\Filament\Resources\BankStatements\Pages;

use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Services\ReconciliationService;
use Filament\Resources\Pages\ViewRecord;

class ViewReconciliation extends ViewRecord
{
    protected static string $resource = BankStatementResource::class;

    protected string $view = 'filament.resources.bank-statement-resource.pages.view-reconciliation';

    protected static ?string $title = 'Rekonsiliasi Perbandingan';

    protected function getViewData(): array
    {
        $service     = app(ReconciliationService::class);
        $paymentId   = $this->record->payment_method_id;
        $periodStart = $this->record->period_start->format('Y-m-d');
        $periodEnd   = $this->record->period_end->format('Y-m-d');

        // Hanya baca dari DB — tidak menulis apapun saat halaman dibuka.
        // Penulisan (auto-match) dilakukan oleh tombol ⚡ Auto Match via endpoint terpisah.
        $results = $service->getStoredMatches($paymentId, $periodStart, $periodEnd);

        return [
            'reconciliationResults' => $results,
            'statistics'            => $results['statistics'],
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [
            url()->route('filament.admin.resources.bank-statements.index') => 'Bank Statements',
            url()->route('filament.admin.resources.bank-statements.view', ['record' => $this->record]) => 'Bank Statement #' . $this->record->id,
            '#' => 'Rekonsiliasi Perbandingan',
        ];
    }
}
