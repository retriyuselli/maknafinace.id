<?php

namespace App\Filament\Resources\PembayaranPiutangs\Schemas;

use App\Models\PembayaranPiutang;
use App\Models\Piutang;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PembayaranPiutangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pembayaran')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('piutang_id')
                                    ->label('Piutang')
                                    ->relationship('piutang', 'nomor_piutang', function (Builder $query) {
                                        return $query->whereIn('status', ['aktif', 'dibayar_sebagian', 'jatuh_tempo']);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $piutang = Piutang::find($state);
                                            $set('max_pembayaran', $piutang->sisa_piutang);
                                        }
                                    }),

                                TextInput::make('nomor_pembayaran')
                                    ->label('Nomor Pembayaran')
                                    ->default(fn () => PembayaranPiutang::generateNomorPembayaran())
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                            ]),

                        Grid::make(1)
                            ->schema([
                                Placeholder::make('info_piutang')
                                    ->label('Informasi Piutang')
                                    ->content(function (Get $get) {
                                        $piutangId = $get('piutang_id');
                                        if (! $piutangId) {
                                            return 'Pilih piutang terlebih dahulu';
                                        }

                                        $piutang = Piutang::find($piutangId);

                                        return "
                                            Debitur: {$piutang->nama_debitur}
                                            Total Piutang: Rp ".number_format($piutang->total_piutang, 0, ',', '.').'
                                            Sudah Dibayar: Rp '.number_format($piutang->sudah_dibayar, 0, ',', '.').'
                                            Sisa Piutang: Rp '.number_format($piutang->sisa_piutang, 0, ',', '.').'
                                        ';
                                    })
                                    ->visible(fn (Get $get) => $get('piutang_id')),
                            ]),
                    ]),

                Section::make('Detail Pembayaran')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('jumlah_pembayaran')
                                    ->label('Jumlah Pembayaran')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $pembayaran = (float) $state;
                                        $bunga = (float) $get('jumlah_bunga') ?? 0;
                                        $denda = (float) $get('denda') ?? 0;
                                        $set('total_pembayaran', $pembayaran + $bunga + $denda);
                                    }),

                                TextInput::make('jumlah_bunga')
                                    ->label('Bunga')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $pembayaran = (float) $get('jumlah_pembayaran') ?? 0;
                                        $bunga = (float) $state ?? 0;
                                        $denda = (float) $get('denda') ?? 0;
                                        $set('total_pembayaran', $pembayaran + $bunga + $denda);
                                    }),

                                TextInput::make('denda')
                                    ->label('Denda')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $pembayaran = (float) $get('jumlah_pembayaran') ?? 0;
                                        $bunga = (float) $get('jumlah_bunga') ?? 0;
                                        $denda = (float) $state ?? 0;
                                        $set('total_pembayaran', $pembayaran + $bunga + $denda);
                                    }),
                            ]),

                        TextInput::make('total_pembayaran')
                            ->label('Total Pembayaran')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),
                    ]),

                Section::make('Metode & Tanggal')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('payment_method_id')
                                    ->label('Metode Pembayaran')
                                    ->relationship('paymentMethod', 'name')
                                    ->required(),

                                DatePicker::make('tanggal_pembayaran')
                                    ->label('Tanggal Pembayaran')
                                    ->required()
                                    ->default(now()),

                                DatePicker::make('tanggal_dicatat')
                                    ->label('Tanggal Dicatat')
                                    ->default(now())
                                    ->required(),
                            ]),
                    ]),

                Section::make('Referensi & Konfirmasi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nomor_referensi')
                                    ->label('Nomor Referensi')
                                    ->placeholder('Nomor referensi bank/transfer'),

                                Select::make('dikonfirmasi_oleh')
                                    ->label('Dikonfirmasi Oleh')
                                    ->relationship('dikonfirmasiOleh', 'name')
                                    ->default(Auth::id()),
                            ]),
                    ]),

                Section::make('Status & Catatan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'dikonfirmasi' => 'Dikonfirmasi',
                                        'dibatalkan' => 'Dibatalkan',
                                    ])
                                    ->default('pending')
                                    ->required(),

                                FileUpload::make('bukti_pembayaran')
                                    ->label('Bukti Pembayaran')
                                    ->multiple()
                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                    ->maxSize(2048),
                            ]),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->placeholder('Catatan tambahan pembayaran')
                            ->columnSpanFull(),
                    ]),

                Hidden::make('dibayar_oleh')
                    ->default(Auth::id()),
            ]);
    }
}
