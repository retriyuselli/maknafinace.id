<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Exports\VendorExport;
use App\Filament\Resources\Vendors\VendorResource;
use App\Filament\Resources\Vendors\Widgets\VendorOverview;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (): BinaryFileResponse {
                    return Excel::download(
                        new VendorExport,
                        'vendors-'.now()->format('YmdHis').'.xlsx'
                    );
                }),
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('New Vendor'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VendorOverview::class,
        ];
    }
}
