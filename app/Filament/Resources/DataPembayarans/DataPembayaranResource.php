<?php

namespace App\Filament\Resources\DataPembayarans;

use App\Filament\Resources\DataPembayarans\Pages\EditDataPembayaran;
use App\Filament\Resources\DataPembayarans\Pages\ListDataPembayarans;
use App\Filament\Resources\DataPembayarans\Schemas\DataPembayaranForm;
use App\Filament\Resources\DataPembayarans\Tables\DataPembayaransTable;
use App\Filament\Resources\DataPembayarans\Widgets\DataPembayaranStatsOverview;
use App\Filament\Resources\DataPembayarans\Widgets\InvoiceStatsOverview;
use App\Models\DataPembayaran;
use Carbon\Carbon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// use App\Filament\Widgets\DataPembayaranStatsOverview;

class DataPembayaranResource extends Resource
{
    protected static ?string $model = DataPembayaran::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $recordTitleAttribute = 'keterangan';

    protected static bool $isGloballySearchable = false;

    protected static ?string $navigationLabel = 'Pendapatan Wedding';

    public static function form(Schema $schema): Schema
    {
        return DataPembayaranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataPembayaransTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Order' => $record->order?->name,
            'Amount' => 'Rp. '.number_format($record->nominal, 0, ',', '.'),
            'Date' => $record->tgl_bayar ? Carbon::parse($record->tgl_bayar)->format('d M Y') : '-',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataPembayarans::route('/'),
            'edit' => EditDataPembayaran::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            DataPembayaranStatsOverview::class,
            InvoiceStatsOverview::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Finance';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['paymentMethod', 'order'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var class-string<DataPembayaran> $model */
        $model = static::getModel();

        return cache()->remember('data_pembayaran_count', now()->addMinutes(5), function () use ($model) {
            return $model::query()
                ->whereNull('deleted_at')
                ->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        /** @var class-string<DataPembayaran> $model */
        $model = static::getModel();

        $count = cache()->remember('data_pembayaran_count', now()->addMinutes(5), function () use ($model) {
            return $model::query()
                ->whereNull('deleted_at')
                ->count();
        });

        return match (true) {
            $count > 10 => 'warning',
            $count > 0 => 'primary',
            default => 'secondary',
        };
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pembayaran dari konsumen ke perusahaan sebagai DP dan pembayaran lanjutan';
    }
}
