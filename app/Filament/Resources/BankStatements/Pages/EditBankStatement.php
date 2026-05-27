<?php

namespace App\Filament\Resources\BankStatements\Pages;

use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Imports\BankReconciliationImport;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class EditBankStatement extends EditRecord
{
    protected static string $resource = BankStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_template')
                ->label('Download Template Rekonsiliasi')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(route('bank-reconciliation.template'))
                ->openUrlInNewTab(),

            // Tombol hapus file rekonsiliasi — hanya tampil jika file ada
            Action::make('delete_reconciliation_file')
                ->label('Hapus File Rekonsiliasi')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus File Rekonsiliasi?')
                ->modalDescription('File rekonsiliasi akan dihapus permanen dari sistem. Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->visible(fn () => filled($this->record?->reconciliation_file))
                ->action(function (): void {
                    $record = $this->record;

                    // Hapus dari disk
                    foreach (['private', 'public'] as $disk) {
                        if (Storage::disk($disk)->exists($record->reconciliation_file)) {
                            Storage::disk($disk)->delete($record->reconciliation_file);
                            break;
                        }
                    }

                    // Bersihkan dari DB
                    $record->update([
                        'reconciliation_file'              => null,
                        'reconciliation_original_filename' => null,
                        'reconciliation_status'            => 'uploaded',
                        'total_records'                    => 0,
                        'total_debit_reconciliation'       => 0,
                        'total_credit_reconciliation'      => 0,
                        'processed_at'                     => null,
                    ]);

                    Notification::make()
                        ->title('File Rekonsiliasi Dihapus')
                        ->body('File rekonsiliasi berhasil dihapus.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'reconciliation_file',
                        'reconciliation_original_filename',
                        'reconciliation_status',
                    ]);
                }),

            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Jangan isi FileUpload dengan path file yang sudah ada di private disk.
        // Private disk tidak bisa diakses via URL → komponen FileUpload stuck di loading state.
        // File yang sudah ada ditampilkan di Placeholder 'reconciliation_file_info' di bawahnya.
        $data['reconciliation_file'] = null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['reconciliation_file'])) {
            // File baru diupload — jadwalkan import via session
            session(['pending_reconciliation_file' => $data['reconciliation_file']]);
        } else {
            // Tidak ada file baru — jangan timpa nilai yang ada di DB
            unset($data['reconciliation_file']);
            unset($data['reconciliation_original_filename']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // Check if there's a pending reconciliation file from session
        $reconciliationFile = session('pending_reconciliation_file');

        if ($reconciliationFile) {
            // Check if file is Excel format
            $fileExtension = strtolower(pathinfo($reconciliationFile, PATHINFO_EXTENSION));
            if (! in_array($fileExtension, ['xlsx', 'xls', 'csv'])) {
                Notification::make()
                    ->title('Format File Tidak Didukung')
                    ->body('Hanya file Excel (.xlsx, .xls) atau CSV yang dapat diimpor untuk rekonsiliasi.')
                    ->warning()
                    ->send();

                session()->forget('pending_reconciliation_file');

                return;
            }

            try {
                $record->update(['reconciliation_status' => 'processing']);

                // Delete + insert ditangani di dalam DB::transaction oleh importer
                $import = new BankReconciliationImport($record);
                $disk = Storage::disk('private')->exists($reconciliationFile) ? 'private' : 'public';
                Excel::import($import, Storage::disk($disk)->path($reconciliationFile));

                // Check for errors from the import
                $errors = $import->getErrors();
                $importedCount = $import->getImportedCount();

                if (! empty($errors)) {
                    $record->update(['reconciliation_status' => 'failed']);

                    $errorMessage = "Berhasil mengimpor {$importedCount} transaksi, tetapi ada ".count($errors)." error:\n";
                    $errorMessage .= implode("\n", array_slice($errors, 0, 5));
                    if (count($errors) > 5) {
                        $errorMessage .= "\n... dan ".(count($errors) - 5).' error lainnya';
                    }

                    Notification::make()
                        ->title('Import Rekonsiliasi Selesai dengan Error')
                        ->body($errorMessage)
                        ->warning()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Import Rekonsiliasi Berhasil!')
                        ->body("Berhasil mengimpor {$importedCount} transaksi rekonsiliasi.")
                        ->success()
                        ->send();
                }

            } catch (Exception $e) {
                $record->update(['reconciliation_status' => 'failed']);

                Notification::make()
                    ->title('Import Rekonsiliasi Gagal')
                    ->body('Error: '.$e->getMessage())
                    ->danger()
                    ->send();
            }

            session()->forget('pending_reconciliation_file');
        }
    }
}
