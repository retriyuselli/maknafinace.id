<?php

namespace App\Filament\Resources\BankStatements\Pages;

use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Models\BankStatement;
use App\Services\ReconciliationService;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ViewReconciliation extends ViewRecord
{
    protected static string $resource = BankStatementResource::class;

    protected string $view = 'filament.resources.bank-statement-resource.pages.view-reconciliation';

    protected static ?string $title = 'Rekonsiliasi Perbandingan';

    protected function getViewData(): array
    {
        // Initialize reconciliation service
        $reconciliationService = app(ReconciliationService::class);

        // Run reconciliation
        $reconciliationResults = $reconciliationService->reconcile(
            $this->record->payment_method_id,
            $this->record->period_start->format('Y-m-d'),
            $this->record->period_end->format('Y-m-d'),
            true
        );

        $storedResults = $reconciliationService->getStoredMatches(
            $this->record->payment_method_id,
            $this->record->period_start->format('Y-m-d'),
            $this->record->period_end->format('Y-m-d')
        );

        $reconciliationResults['matched'] = $storedResults['matched'];
        
        return [
            'reconciliationResults' => $reconciliationResults,
            'statistics' => $reconciliationResults['statistics'],
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
