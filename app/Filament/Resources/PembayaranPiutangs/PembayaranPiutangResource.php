<?php

namespace App\Filament\Resources\PembayaranPiutangs;

use App\Filament\Resources\PembayaranPiutangs\Pages\CreatePembayaranPiutang;
use App\Filament\Resources\PembayaranPiutangs\Pages\EditPembayaranPiutang;
use App\Filament\Resources\PembayaranPiutangs\Pages\ListPembayaranPiutangs;
use App\Filament\Resources\PembayaranPiutangs\Pages\ViewPembayaranPiutang;
use App\Filament\Resources\PembayaranPiutangs\Schemas\PembayaranPiutangForm;
use App\Filament\Resources\PembayaranPiutangs\Tables\PembayaranPiutangsTable;
use App\Models\PembayaranPiutang;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;

class PembayaranPiutangResource extends Resource
{
    protected static ?string $model = PembayaranPiutang::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Pembayaran Piutang';

    protected static ?string $modelLabel = 'Pembayaran Piutang';

    protected static ?string $pluralModelLabel = 'Pembayaran Piutang';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return PembayaranPiutangForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PembayaranPiutangsTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'piutang:id,nomor_piutang,nama_debitur,total_piutang,sudah_dibayar,sisa_piutang',
                'paymentMethod:id,name',
                'dikonfirmasiOleh:id,name',
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pembayaran')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('nomor_pembayaran')
                                    ->label('Nomor Pembayaran')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('piutang.nomor_piutang')
                                    ->label('Nomor Piutang')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'pending' => 'warning',
                                        'dikonfirmasi' => 'success',
                                        'dibatalkan' => 'danger',
                                        default => 'gray',
                                    }),
                            ]),

                        TextEntry::make('piutang.nama_debitur')
                            ->label('Debitur'),

                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->catatan),
                    ]),

                Section::make('Detail Keuangan')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('jumlah_pembayaran')
                                    ->label('Jumlah Pembayaran')
                                    ->money('IDR'),

                                TextEntry::make('jumlah_bunga')
                                    ->label('Bunga')
                                    ->money('IDR'),

                                TextEntry::make('denda')
                                    ->label('Denda')
                                    ->money('IDR'),

                                TextEntry::make('total_pembayaran')
                                    ->label('Total Pembayaran')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold),
                            ]),
                    ]),

                Section::make('Informasi Piutang')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('piutang.total_piutang')
                                    ->label('Total Piutang')
                                    ->money('IDR'),

                                TextEntry::make('piutang.sudah_dibayar')
                                    ->label('Sudah Dibayar')
                                    ->money('IDR')
                                    ->color('success'),

                                TextEntry::make('piutang.sisa_piutang')
                                    ->label('Sisa Piutang')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold)
                                    ->color('danger'),
                            ]),
                    ]),

                Section::make('Metode & Tanggal')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('paymentMethod.name')
                                    ->label('Metode Pembayaran'),

                                TextEntry::make('tanggal_pembayaran')
                                    ->label('Tanggal Pembayaran')
                                    ->date('d M Y'),

                                TextEntry::make('tanggal_dicatat')
                                    ->label('Tanggal Dicatat')
                                    ->date('d M Y'),
                            ]),
                    ]),

                Section::make('Referensi & Konfirmasi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('nomor_referensi')
                                    ->label('Nomor Referensi')
                                    ->visible(fn ($record) => $record->nomor_referensi),

                                TextEntry::make('dikonfirmasiOleh.name')
                                    ->label('Dikonfirmasi Oleh')
                                    ->visible(fn ($record) => $record->dikonfirmasi_oleh),
                            ]),
                    ]),

                Section::make('Lampiran')
                    ->schema([
                        TextEntry::make('bukti_pembayaran')
                            ->label('Bukti Pembayaran')
                            ->visible(fn ($record) => $record->bukti_pembayaran),
                    ])
                    ->visible(fn ($record) => $record->bukti_pembayaran),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPembayaranPiutangs::route('/'),
            'create' => CreatePembayaranPiutang::route('/create'),
            'view' => ViewPembayaranPiutang::route('/{record}'),
            'edit' => EditPembayaranPiutang::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
