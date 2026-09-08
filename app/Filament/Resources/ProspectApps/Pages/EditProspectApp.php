<?php

namespace App\Filament\Resources\ProspectApps\Pages;

use App\Filament\Resources\ProspectApps\ProspectAppResource;
use App\Services\ItemPurchaseCodeService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProspectApp extends EditRecord
{
    protected static string $resource = ProspectAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateProposal')
                ->label('Generate Proposal')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->url(fn () => route('prospect-app.proposal.pdf', $this->record))
                ->openUrlInNewTab(),
            Action::make('issuePurchaseCode')
                ->label('Terbitkan Item Purchase Code')
                ->icon('heroicon-o-key')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Terbitkan Item Purchase Code')
                ->modalDescription('Kode baru akan dibuat. Kode lama (jika ada) dicabut dan tidak bisa dipakai di aplikasi klien.')
                ->action(function (): void {
                    $code = app(ItemPurchaseCodeService::class)->issueForProspectApp($this->record, true);

                    Notification::make()
                        ->title('Item Purchase Code diterbitkan')
                        ->body($code->code)
                        ->success()
                        ->send();

                    $this->refreshFormData(['item_purchase_code_display']);
                }),
            DeleteAction::make(),
        ];
    }
}
