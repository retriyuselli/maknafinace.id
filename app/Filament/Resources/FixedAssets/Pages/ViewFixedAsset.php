<?php

namespace App\Filament\Resources\FixedAssets\Pages;

use App\Filament\Resources\FixedAssets\FixedAssetResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewFixedAsset extends ViewRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('calculate_depreciation')
                ->label('Calculate Depreciation')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->action(function () {
                    if (! $this->record->isFullyDepreciated()) {
                        $monthlyDepreciation = $this->record->calculateMonthlyDepreciation();
                        $this->record->accumulated_depreciation += $monthlyDepreciation;
                        $this->record->updateBookValue();

                        Notification::make()
                            ->title('Depreciation Calculated')
                            ->body('Monthly depreciation: IDR '.number_format($monthlyDepreciation))
                            ->success()
                            ->send();

                        $this->refreshFormData(['accumulated_depreciation', 'current_book_value']);
                    }
                })
                ->requiresConfirmation()
                ->visible(fn () => ! $this->record->isFullyDepreciated()),
        ];
    }
}
