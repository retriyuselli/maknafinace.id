<?php

namespace App\Filament\Resources\Expenses;

use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Resources\Expenses\Schemas\ExpenseForm;
use App\Filament\Resources\Expenses\Tables\ExpensesTable;
use App\Filament\Resources\Expenses\Widgets\ExpenseOverview;
use App\Models\Expense;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Pengeluaran Wedding';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    public static function form(Schema $schema): Schema
    {
        return ExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpensesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ExpenseOverview::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Finance';
    }

    private static function getCachedNavigationBadgeCount(): int
    {
        $modelClass = static::getModel();

        return Cache::remember(
            'nav:expenses:count',
            60,
            fn (): int => (int) $modelClass::count()
        );
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getCachedNavigationBadgeCount();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        // Memberikan warna pada badge untuk visibilitas yang lebih baik
        // Pilihan lain: 'primary', 'success', 'danger', 'info'
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pengeluaran wedding yang dikeluarkan untuk berbagai keperluan proyek, termasuk pembayaran vendor dan biaya lainnya';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'order.prospect:id,name_event',
                'vendor:id,name',
                'paymentMethod:id,bank_name,name,no_rekening',
            ]);
    }
}
