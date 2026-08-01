<?php

namespace App\Filament\Resources\BankStatements;

use App\Filament\Resources\Concerns\CachesNavigationBadge;

use App\Filament\Resources\BankStatements\Pages\CreateBankStatement;
use App\Filament\Resources\BankStatements\Pages\CashflowDetail;
use App\Filament\Resources\BankStatements\Pages\CashflowSourceDetail;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BankStatementResource extends Resource
{
    use CachesNavigationBadge;

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
            ]);
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
            'cashflow-detail' => CashflowDetail::route('/cashflow-detail'),
            'cashflow-source-detail' => CashflowSourceDetail::route('/cashflow-source-detail'),
            'index' => ListBankStatements::route('/'),
            'create' => CreateBankStatement::route('/create'),
            'view' => ViewBankStatement::route('/{record}'),
            'edit' => EditBankStatement::route('/{record}/edit'),
        ];
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
