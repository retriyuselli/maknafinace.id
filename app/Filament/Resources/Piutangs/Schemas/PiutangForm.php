<?php

namespace App\Filament\Resources\Piutangs\Schemas;

use App\Enums\JenisPiutang;
use App\Enums\StatusPiutang;
use App\Models\Piutang;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class PiutangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Piutang')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('nomor_piutang')
                                    ->label('Nomor Piutang')
                                    ->default(fn () => Piutang::generateNomorPiutang())
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),

                                Select::make('jenis_piutang')
                                    ->label('Jenis Piutang')
                                    ->options(JenisPiutang::getOptions())
                                    ->required(),

                                Select::make('prioritas')
                                    ->label('Prioritas')
                                    ->options([
                                        'rendah' => 'Rendah',
                                        'sedang' => 'Sedang',
                                        'tinggi' => 'Tinggi',
                                        'mendesak' => 'Mendesak',
                                    ])
                                    ->default('sedang')
                                    ->required(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('nama_debitur')
                                    ->label('Nama Debitur')
                                    ->required()
                                    ->placeholder('Nama yang berhutang kepada kita'),

                                TextInput::make('kontak_debitur')
                                    ->label('Kontak Debitur')
                                    ->placeholder('No. HP/Telepon untuk follow up')
                                    ->tel(),
                            ]),

                        Textarea::make('keterangan')
                            ->label('Keterangan Piutang')
                            ->required()
                            ->placeholder('Jelaskan detail piutang, invoice, dll')
                            ->columnSpanFull(),
                    ]),

                Section::make('Detail Keuangan')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('jumlah_pokok')
                                    ->label('Jumlah Pokok')
                                    ->prefix('Rp. ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                    ->placeholder('0')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $pokok = (float) $state;
                                        $bunga = (float) $get('persentase_bunga') ?? 0;
                                        $totalBunga = ($pokok * $bunga) / 100;
                                        $total = $pokok + $totalBunga;
                                        $set('total_piutang', $total);
                                        $set('sisa_piutang', $total);
                                    }),

                                TextInput::make('persentase_bunga')
                                    ->label('Bunga (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $pokok = (float) $get('jumlah_pokok') ?? 0;
                                        $bunga = (float) $state ?? 0;
                                        $totalBunga = ($pokok * $bunga) / 100;
                                        $total = $pokok + $totalBunga;
                                        $set('total_piutang', $total);
                                        $set('sisa_piutang', $total);
                                    }),

                                TextInput::make('total_piutang')
                                    ->label('Total Piutang')
                                    ->prefix('Rp. ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                    ->placeholder('0')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),
                    ]),

                Section::make('Tanggal & Status')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('tanggal_piutang')
                                    ->label('Tanggal Piutang')
                                    ->required()
                                    ->default(now()),

                                DatePicker::make('tanggal_jatuh_tempo')
                                    ->label('Tanggal Jatuh Tempo')
                                    ->required()
                                    ->minDate(now()),

                                Select::make('status')
                                    ->label('Status')
                                    ->options(StatusPiutang::getOptions())
                                    ->default('aktif')
                                    ->required(),
                            ]),
                    ]),

                Section::make('Lampiran & Catatan')
                    ->schema([
                        FileUpload::make('lampiran')
                            ->label('Lampiran')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(2048)
                            ->helperText('Upload dokumen pendukung (PDF, gambar). Maksimal 2MB per file.'),

                        Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->placeholder('Catatan atau informasi tambahan tentang piutang ini'),
                    ]),

                Hidden::make('dibuat_oleh')
                    ->default(Auth::id()),
            ]);
    }
}
