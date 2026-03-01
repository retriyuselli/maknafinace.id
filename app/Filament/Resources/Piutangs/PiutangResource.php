<?php

namespace App\Filament\Resources\Piutangs;

use App\Enums\JenisPiutang;
use App\Enums\StatusPiutang;
use App\Filament\Resources\Piutangs\Pages\CreatePiutang;
use App\Filament\Resources\Piutangs\Pages\EditPiutang;
use App\Filament\Resources\Piutangs\Pages\ListPiutangs;
use App\Filament\Resources\Piutangs\Pages\ViewPiutang;
use App\Filament\Resources\Piutangs\Schemas\PiutangForm;
use App\Filament\Resources\Piutangs\Tables\PiutangsTable;
use App\Filament\Resources\Piutangs\Widgets\PiutangJatuhTempoWidget;
use App\Filament\Resources\Piutangs\Widgets\PiutangOverviewWidget;
use App\Filament\Resources\Piutangs\Widgets\TopDebiturWidget;
use App\Models\Piutang;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Table;

class PiutangResource extends Resource
{
    protected static ?string $model = Piutang::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Piutang';

    protected static ?string $modelLabel = 'Piutang';

    protected static ?string $pluralModelLabel = 'Piutang';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PiutangForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PiutangsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Piutang')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('nomor_piutang')
                                    ->label('Nomor Piutang')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('jenis_piutang')
                                    ->label('Jenis Piutang')
                                    ->formatStateUsing(fn ($state) => $state instanceof JenisPiutang ? $state->getLabel() : JenisPiutang::from($state)->getLabel())
                                    ->badge(),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->formatStateUsing(fn ($state) => $state instanceof StatusPiutang ? $state->getLabel() : StatusPiutang::from($state)->getLabel())
                                    ->badge()
                                    ->color(fn ($state) => $state instanceof StatusPiutang ? $state->getColor() : StatusPiutang::from($state)->getColor()),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('nama_debitur')
                                    ->label('Debitur'),

                                TextEntry::make('kontak_debitur')
                                    ->label('Kontak Debitur')
                                    ->visible(fn ($record) => $record->kontak_debitur),
                            ]),

                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->columnSpanFull(),
                    ]),

                Section::make('Detail Keuangan')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('jumlah_pokok')
                                    ->label('Jumlah Pokok')
                                    ->money('IDR'),

                                TextEntry::make('persentase_bunga')
                                    ->label('Bunga')
                                    ->suffix('%'),

                                TextEntry::make('total_piutang')
                                    ->label('Total Piutang')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('sudah_dibayar')
                                    ->label('Sudah Dibayar')
                                    ->money('IDR')
                                    ->color('success'),

                                TextEntry::make('sisa_piutang')
                                    ->label('Sisa Piutang')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold)
                                    ->color('danger'),
                            ]),
                    ]),

                Section::make('Tanggal')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('tanggal_piutang')
                                    ->label('Tanggal Piutang')
                                    ->date('d M Y'),

                                TextEntry::make('tanggal_jatuh_tempo')
                                    ->label('Jatuh Tempo')
                                    ->date('d M Y'),

                                TextEntry::make('tanggal_lunas')
                                    ->label('Tanggal Lunas')
                                    ->date('d M Y')
                                    ->visible(fn ($record) => $record->tanggal_lunas),
                            ]),
                    ]),

                Section::make('Catatan & Lampiran')
                    ->schema([
                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->visible(fn ($record) => $record->catatan),

                        TextEntry::make('lampiran')
                            ->label('Lampiran')
                            ->visible(fn ($record) => $record->lampiran),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPiutangs::route('/'),
            'create' => CreatePiutang::route('/create'),
            'view' => ViewPiutang::route('/{record}'),
            'edit' => EditPiutang::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'aktif')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getWidgets(): array
    {
        return [
            PiutangOverviewWidget::class,
            PiutangJatuhTempoWidget::class,
            TopDebiturWidget::class,
        ];
    }
}
