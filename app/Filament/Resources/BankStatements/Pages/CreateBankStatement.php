<?php

namespace App\Filament\Resources\BankStatements\Pages;

use App\Filament\Resources\BankStatements\BankStatementResource;
use App\Imports\BankReconciliationImport;
use App\Imports\BankStatementImport;
use App\Models\BankStatement;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CreateBankStatement extends CreateRecord
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
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['reconciliation_file'])) {
            session(['pending_reconciliation_file' => $data['reconciliation_file']]);
        }

        if (! empty($data['file_path']) && ($data['source_type'] ?? 'upload') !== 'manual_input') {
            session(['pending_statement_file' => $data['file_path']]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($statementFile = session('pending_statement_file')) {
            $this->processStatementImport($record, $statementFile);
        }

        if ($reconciliationFile = session('pending_reconciliation_file')) {
            $this->processReconciliationImport($record, $reconciliationFile);
        }
    }

    protected function processStatementImport(BankStatement $record, string $filePath): void
    {
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (! in_array($fileExtension, ['xlsx', 'xls', 'csv'])) {
            Notification::make()
                ->title('Format File Tidak Didukung')
                ->body('Hanya file Excel (.xlsx, .xls) atau CSV yang dapat diimpor sebagai statement.')
                ->warning()
                ->send();

            session()->forget('pending_statement_file');

            return;
        }

        try {
            $record->update(['status' => 'processing']);

            $import = new BankStatementImport($record);
            $disk = Storage::disk('private')->exists($filePath) ? 'private' : 'public';
            Excel::import($import, Storage::disk($disk)->path($filePath));

            $record->update(['status' => 'parsed']);

            Notification::make()
                ->title('Import Statement Berhasil!')
                ->body('File statement berhasil diimpor.')
                ->success()
                ->send();
        } catch (Exception $e) {
            $record->update(['status' => 'failed']);

            Notification::make()
                ->title('Import Statement Gagal')
                ->body('Error: '.$e->getMessage())
                ->danger()
                ->send();
        }

        session()->forget('pending_statement_file');
    }

    protected function processReconciliationImport(BankStatement $record, string $filePath): void
    {
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
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

            $import = new BankReconciliationImport($record);
            $disk = Storage::disk('private')->exists($filePath) ? 'private' : 'public';
            Excel::import($import, Storage::disk($disk)->path($filePath));

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
