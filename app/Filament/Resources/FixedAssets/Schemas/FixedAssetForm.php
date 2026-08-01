<?php

namespace App\Filament\Resources\FixedAssets\Schemas;

use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;

class FixedAssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->tabs([
                        Tab::make('Informasi Aset')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('asset_code')
                                            ->label('Kode Aset')
                                            ->required()
                                            ->readOnly()
                                            ->unique(ignoreRecord: true)
                                            ->default(fn () => FixedAsset::generateAssetCode())
                                            ->maxLength(50),

                                        Select::make('category')
                                            ->label('Kategori')
                                            ->options(FixedAsset::CATEGORIES)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($state && ! $get('asset_code')) {
                                                    $set('asset_code', FixedAsset::generateAssetCode($state));
                                                }
                                            }),

                                        Select::make('condition')
                                            ->label('Kondisi')
                                            ->options(FixedAsset::CONDITIONS)
                                            ->default('GOOD')
                                            ->required(),
                                    ]),

                                TextInput::make('asset_name')
                                    ->label('Nama Aset')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('location')
                                            ->label('Lokasi')
                                            ->maxLength(255)
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Tempat aset berada saat ini. Contoh: "Lantai 2 Ruang Keuangan" atau "Gudang A Rak 3".'),

                                        Toggle::make('is_active')
                                            ->label('Aktif')
                                            ->default(true)
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Status aset: Aktif (masih digunakan) atau Tidak Aktif (sudah tidak digunakan).'),
                                    ]),
                            ]),

                        Tab::make('Informasi Pembelian')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        DatePicker::make('purchase_date')
                                            ->label('Tanggal Pembelian')
                                            ->required()
                                            ->default(now()),

                                        TextInput::make('purchase_price')
                                            ->label('Harga Pembelian')
                                            ->required()
                                            ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                            ->prefix('IDR')
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $set('current_book_value', $state);
                                            })
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Total biaya pembelian aset termasuk pajak, biaya pengiriman, dan instalasi.'),

                                        TextInput::make('salvage_value')
                                            ->label('Nilai Sisa')
                                            ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                            ->prefix('IDR')
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->default(0)
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Perkiraan harga jual aset setelah masa pakai habis (Residu). Jika dianggap tidak berharga lagi, isi 0. Contoh: Laptop Rp 10jt, nilai sisa Rp 1jt.'),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('supplier')
                                            ->label('Pemasok')
                                            ->maxLength(255)
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Nama perusahaan atau toko tempat membeli aset untuk referensi dan claim garansi.'),

                                        TextInput::make('invoice_number')
                                            ->label('Nomor Invoice')
                                            ->maxLength(255),

                                        DatePicker::make('warranty_expiry')
                                            ->label('Masa Garansi Berakhir'),
                                    ]),
                            ]),

                        Tab::make('Pengaturan Penyusutan')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('depreciation_method')
                                            ->label('Metode Penyusutan')
                                            ->options(FixedAsset::DEPRECIATION_METHODS)
                                            ->default('STRAIGHT_LINE')
                                            ->required()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Metode perhitungan penyusutan. Garis Lurus = nilai penyusutan sama setiap bulan (paling umum). Saldo Menurun = penyusutan lebih besar di awal.'),

                                        TextInput::make('useful_life_years')
                                            ->label('Masa Manfaat (Tahun)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Perkiraan berapa tahun aset masih bisa digunakan secara produktif. Panduan: Komputer/Laptop 3-5 tahun, Furniture 5-10 tahun, Kendaraan 5-8 tahun, Bangunan 20-50 tahun.'),

                                        TextInput::make('useful_life_months')
                                            ->label('Bulan Tambahan')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(11)
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Bulan tambahan selain tahun (0-11). Contoh: 3 tahun 6 bulan = Years: 3, Months: 6.'),
                                    ]),

                                Grid::make(2)
                                    ->schema([
                                        Select::make('chart_of_account_id')
                                            ->label('Akun Aset')
                                            ->relationship(
                                                'chartOfAccount',
                                                'account_name',
                                                fn (Builder $query) => $query->where('account_type', 'HARTA')
                                                    ->where('is_active', true)
                                            )
                                            ->getOptionLabelFromRecordUsing(fn (ChartOfAccount $record): string => "{$record->account_code} - {$record->account_name}")
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Pilih akun neraca untuk mencatat nilai aset ini. Biasanya "Aset Tetap - [Kategori]".'),

                                        Select::make('depreciation_account_id')
                                            ->label('Akun Akumulasi Penyusutan')
                                            ->relationship(
                                                'depreciationAccount',
                                                'account_name',
                                                fn (Builder $query) => $query->where('account_type', 'HARTA')
                                                    ->where('account_name', 'like', '%akumulasi%')
                                                    ->where('is_active', true)
                                            )
                                            ->getOptionLabelFromRecordUsing(fn (ChartOfAccount $record): string => "{$record->account_code} - {$record->account_name}")
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Akun untuk mencatat akumulasi penyusutan. Akan mengurangi nilai aset di neraca.'),
                                    ]),
                            ]),

                        Tab::make('Status Saat Ini')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('accumulated_depreciation')
                                            ->label('Akumulasi Penyusutan')
                                            ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                            ->prefix('IDR')
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->default(0)
                                            ->readOnly()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Total penyusutan yang sudah terjadi. Dihitung otomatis dari riwayat penyusutan bulanan.'),

                                        TextInput::make('current_book_value')
                                            ->label('Nilai Buku Saat Ini')
                                            ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                            ->prefix('IDR')
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->readOnly()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Nilai aset saat ini = Harga Beli - Akumulasi Penyusutan. Dihitung otomatis.'),
                                    ]),

                                Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Catatan tambahan tentang aset seperti spesifikasi detail, riwayat perbaikan, atau informasi penting lainnya.'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
