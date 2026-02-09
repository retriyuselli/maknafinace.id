<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components(ProductForm::configure());
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Data Produk yang telah dibuat dan dikelola';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
            'view' => ViewProduct::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'items.vendor:id,name,harga_publish,harga_vendor,description',
                'penambahanHarga.vendor:id,name,harga_publish,harga_vendor,description',
            ])
            ->withCount([
                'orders as unique_orders_count',
            ])
            // Bonus: Ini juga akan mengaktifkan kolom 'Total Sold'
            ->withSum('orderItems as total_quantity_sold', 'quantity');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return static::mutateFormDataBeforeSave($data);
    }

    protected function mutateFormDataBeforeUpdate(array $data): array
    {
        // Preserve existing image if not changed
        // This logic might need adjustment based on how FileUpload handles empty states
        // For now, we assume $data will not contain 'image' if it's not being updated.
        return static::mutateFormDataBeforeSave($data);
    }

    /**
     * Mutate form data before saving (both create and update).
     * This method recalculates product_price, pengurangan, penambahan, and price on the server-side
     * based on the submitted repeater data to ensure data integrity.
     */
    protected static function mutateFormDataBeforeSave(array $data): array
    {
        // Helper function to clean currency string values and convert to float
        $cleanCurrencyValue = function ($value): int {
            return ProductForm::stripCurrency($value);
        };

        // 1. Recalculate 'product_price' from 'items' (vendor repeater)
        $calculatedProductPrice = 0;
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                // 'price_public' is 'harga_publish' * 'quantity' for each vendor item
                $calculatedProductPrice += $cleanCurrencyValue($item['price_public'] ?? '0');
            }
        }
        $data['product_price'] = $calculatedProductPrice;

        // 2. Recalculate 'pengurangan' from 'itemsPengurangan' (discount repeater)
        $calculatedPengurangan = 0;
        if (isset($data['itemsPengurangan']) && is_array($data['itemsPengurangan'])) {
            foreach ($data['itemsPengurangan'] as $item) {
                $calculatedPengurangan += $cleanCurrencyValue($item['amount'] ?? '0');
            }
        }
        $data['pengurangan'] = $calculatedPengurangan;

        // 3. Recalculate 'penambahan_publish' and 'penambahan_vendor' from 'penambahanHarga' (addition repeater)
        $calculatedPenambahanPublish = 0;
        $calculatedPenambahanVendor = 0;
        if (isset($data['penambahanHarga']) && is_array($data['penambahanHarga'])) {
            foreach ($data['penambahanHarga'] as $key => $item) {
                $calculatedPenambahanPublish += $cleanCurrencyValue($item['harga_publish'] ?? '0');
                $calculatedPenambahanVendor += $cleanCurrencyValue($item['harga_vendor'] ?? '0');

                // Set amount field to harga_publish for compatibility with database
                $data['penambahanHarga'][$key]['amount'] = $cleanCurrencyValue($item['harga_publish'] ?? '0');
            }
        }
        $data['penambahan_publish'] = $calculatedPenambahanPublish;
        $data['penambahan_vendor'] = $calculatedPenambahanVendor;

        // 4. Recalculate final 'price'
        $data['price'] = $data['product_price'] - $data['pengurangan'] + $data['penambahan_publish'];

        return $data;
    }
}