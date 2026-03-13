<?php

namespace App\Filament\Resources\BankStatements;

use App\Filament\Resources\BankStatements\Pages\CreateBankStatement;
use App\Filament\Resources\BankStatements\Pages\EditBankStatement;
use App\Filament\Resources\BankStatements\Pages\ListBankStatements;
use App\Filament\Resources\BankStatements\Pages\ViewReconciliation;
use App\Filament\Resources\BankStatements\Pages\ViewBankStatement;
use App\Filament\Resources\BankStatements\RelationManagers\BankReconciliationItemsRelationManager;
use App\Filament\Resources\BankStatements\Schemas\BankStatementForm;
use App\Filament\Resources\BankStatements\Tables\BankStatementsTable;
use App\Filament\Resources\BankStatements\Widgets\BankStatementOverview;
use App\Models\BankStatement;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BankStatementResource extends Resource
{
    protected static ?string $model = BankStatement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Rekening Koran';

    public static function form(Schema $schema): Schema
    {
        return BankStatementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BankStatementsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'paymentMethod:id,name,bank_name,no_rekening',
                'lastEditedBy:id,name',
            ])
            ->withCount('transactions');
    }

    public static function getRelations(): array
    {
        return [
            BankReconciliationItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'reconciliation' => ViewReconciliation::route('/{record}/reconciliation'),
            'index' => ListBankStatements::route('/'),
            'create' => CreateBankStatement::route('/create'),
            'view' => ViewBankStatement::route('/{record}'),
            'edit' => EditBankStatement::route('/{record}/edit'),
        ];
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_at'] = Carbon::now();
        if (! isset($data['total_records'])) {
            $data['total_records'] = 0;
        }
        if (! isset($data['reconciliation_status'])) {
            $data['reconciliation_status'] = 'uploaded';
        }
        if (! empty($data['file_path']) && empty($data['original_filename'])) {
            $data['original_filename'] = basename($data['file_path']);
        }
        foreach (['total_debit_reconciliation', 'total_credit_reconciliation'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = (float) str_replace(['.', ',', ' ', 'IDR'], '', $data[$field]) ?: 0;
            } elseif (! isset($data[$field])) {
                $data[$field] = 0;
            }
        }

        return $data;
    }

    protected static function mutateFormDataBeforeFill(array $data): array
    {
        // Pastikan field numerik diformat dengan benar saat dimuat untuk edit
        return $data;
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        // Bersihkan masalah formatting sebelum menyimpan
        $numericFields = ['opening_balance', 'closing_balance', 'tot_debit', 'tot_credit'];

        foreach ($numericFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                // Hapus format dan konversi ke angka
                $data[$field] = (float) str_replace(['.', ',', ' ', 'IDR'], '', $data[$field]) ?: null;
            }
        }

        return $data;
    }

    public static function getNavigationBadge(): ?string
    {
        // Menampilkan jumlah total rekening koran sebagai badge
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        // Memberikan warna pada badge untuk visibilitas yang lebih baik
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total rekening koran yang terdaftar';
    }

    public static function getWidgets(): array
    {
        return [
            BankStatementOverview::class,
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return Gate::allows('ViewAny:BankStatement');
    }
}
