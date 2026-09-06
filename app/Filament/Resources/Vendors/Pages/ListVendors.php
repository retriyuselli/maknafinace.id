<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Exports\VendorExport;
use App\Exports\VendorImportTemplateExport;
use App\Filament\Resources\Vendors\VendorResource;
use App\Filament\Resources\Vendors\Widgets\VendorOverview;
use App\Imports\VendorImport;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('downloadImportTemplate')
                    ->label('Template Import')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function (): BinaryFileResponse {
                        return Excel::download(
                            new VendorImportTemplateExport,
                            'template-import-vendor.xlsx'
                        );
                    }),
                Action::make('importExcel')
                    ->label('Import XLS')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->form([
                        FileUpload::make('file')
                            ->label('File Excel')
                            ->acceptedFileTypes([
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel.sheet.macroEnabled.12',
                                'text/csv',
                                'application/csv',
                                'text/plain',
                            ])
                            ->rules(['file', 'mimes:xls,xlsx,csv'])
                            ->storeFiles(false)
                            ->required()
                            ->helperText('Unggah file .xls / .xlsx. Kolom wajib: Nama Vendor dan Kategori (pilih dari dropdown). Deskripsi: Alt+Enter untuk baris baru, "- item" untuk bullet, "1. item" untuk numbered list.'),
                    ])
                    ->action(function (array $data): void {
                        $file = $data['file'] ?? null;

                        if (is_array($file)) {
                            $file = $file[0] ?? null;
                        }

                        if (! $file) {
                            Notification::make()
                                ->title('Import gagal')
                                ->body('File import tidak ditemukan.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $path = $file instanceof TemporaryUploadedFile
                            ? $file->getRealPath()
                            : (string) $file;

                        try {
                            $import = new VendorImport;
                            Excel::import($import, $path);
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Import gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Cache::forget('nav:vendors:count');

                        $body = sprintf(
                            'Ditambah: %d, diperbarui: %d, dilewati: %d.',
                            $import->getCreatedCount(),
                            $import->getUpdatedCount(),
                            $import->getSkippedCount()
                        );

                        $errors = $import->getErrors();
                        if ($errors !== []) {
                            $body .= ' '.implode(' ', array_slice($errors, 0, 5));
                            if (count($errors) > 5) {
                                $body .= ' dan '.(count($errors) - 5).' error lainnya.';
                            }
                        }

                        Notification::make()
                            ->title($errors === [] ? 'Import vendor selesai' : 'Import vendor selesai dengan catatan')
                            ->body($body)
                            ->color($errors === [] ? 'success' : 'warning')
                            ->send();
                    }),
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
            ])
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->button(),
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
