<?php

namespace App\Filament\Resources\SimulasiProduks;

use App\Filament\Resources\SimulasiProduks\Pages\CreateSimulasiProduk;
use App\Filament\Resources\SimulasiProduks\Pages\EditSimulasiProduk;
use App\Filament\Resources\SimulasiProduks\Pages\ListSimulasiProduks;
use App\Filament\Resources\SimulasiProduks\Pages\ViewSimulasiInvoice;
use App\Filament\Resources\SimulasiProduks\Schemas\SimulasiProdukForm;
use App\Filament\Resources\SimulasiProduks\Tables\SimulasiProduksTable;
use App\Models\SimulasiProduk;
use App\Support\Rupiah;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SimulasiProdukResource extends Resource
{
    protected static ?string $model = SimulasiProduk::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Simulasi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components(
            SimulasiProdukForm::configure()
        );
    }

    public static function table(Table $table): Table
    {
        return SimulasiProduksTable::configure($table);
    }

    public static function parseCurrency($value): float
    {
        return Rupiah::parse($value);
    }

    public static function recalculateGrandTotal(Get $get, Set $set, string $basePath = ''): void
    {
        $total_price = static::parseCurrency($get($basePath.'total_price'));
        $promo = static::parseCurrency($get($basePath.'promo'));
        $penambahan = static::parseCurrency($get($basePath.'penambahan'));
        $pengurangan = static::parseCurrency($get($basePath.'pengurangan'));

        $grand_total = $total_price + $penambahan - $promo - $pengurangan;
        $set($basePath.'grand_total', $grand_total);
        $set($basePath.'grand_total_display', Rupiah::format($grand_total));
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
            'index' => ListSimulasiProduks::route('/'),
            'create' => CreateSimulasiProduk::route('/create'),
            'edit' => EditSimulasiProduk::route('/{record}/edit'),
            'invoice' => ViewSimulasiInvoice::route('/{record}/invoice'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Example: If you want to scope simulations to the logged-in user (and admins see all)
        // if (!auth()->user()->hasRole('super_admin')) {
        //     return parent::getEloquentQuery()->where('user_id', auth()->id());
        // }
        return parent::getEloquentQuery();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total simulasi yang sedang diproses';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}