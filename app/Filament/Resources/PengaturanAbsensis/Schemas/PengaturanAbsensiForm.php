<?php

namespace App\Filament\Resources\PengaturanAbsensis\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PengaturanAbsensiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aturan Jam Kerja')
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Aturan Kantor Default'),
                        TimePicker::make('jam_masuk')
                            ->label('Jam Masuk')
                            ->required()
                            ->seconds(false),
                        TimePicker::make('jam_pulang')
                            ->label('Jam Pulang')
                            ->required()
                            ->seconds(false),
                        TextInput::make('toleransi_terlambat_menit')
                            ->label('Toleransi Terlambat')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(15)
                            ->suffix('menit'),
                        TextInput::make('toleransi_pulang_cepat_menit')
                            ->label('Toleransi Pulang Cepat')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(10)
                            ->suffix('menit'),
                        TextInput::make('zona_waktu')
                            ->label('Zona Waktu')
                            ->required()
                            ->default('Asia/Jakarta')
                            ->maxLength(64),
                        Toggle::make('libur_sabtu')
                            ->label('Libur Sabtu')
                            ->default(true)
                            ->helperText('Jika aktif, Sabtu dihitung libur mingguan'),
                        Toggle::make('libur_minggu')
                            ->label('Libur Minggu')
                            ->default(true)
                            ->helperText('Jika aktif, Minggu dihitung libur mingguan'),
                        Toggle::make('aktif')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(2),
                Section::make('Tarif Usulan Payroll')
                    ->description('Digunakan sebagai usulan pengurangan/bonus saat membuat payroll. Isi 0 untuk menonaktifkan perhitungan otomatis.')
                    ->schema([
                        TextInput::make('denda_terlambat_per_menit')
                            ->label('Denda Terlambat / Menit')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('Rp')
                            ->helperText('Usulan pengurangan = total menit terlambat × tarif ini'),
                        TextInput::make('tarif_lembur_per_menit')
                            ->label('Tarif Lembur / Menit')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('Rp')
                            ->helperText('Usulan bonus = total menit lembur disetujui × tarif ini'),
                    ])->columns(2),
                Section::make('Kewajiban & Geofence')
                    ->schema([
                        Toggle::make('wajib_pulang')
                            ->label('Wajib Absen Pulang')
                            ->default(true),
                        Toggle::make('wajib_lokasi')
                            ->label('Wajib Lokasi (GPS)')
                            ->default(true),
                        Toggle::make('wajib_foto')
                            ->label('Wajib Foto')
                            ->default(true),
                        Toggle::make('tolak_jika_di_luar_radius')
                            ->label('Tolak Jika Di Luar Radius')
                            ->default(true)
                            ->helperText('Jika aktif, absen jauh dari kantor ditolak (bukan hanya peringatan)'),
                        TextInput::make('akurasi_gps_maksimal_meter')
                            ->label('Akurasi GPS Maksimal')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('meter')
                            ->nullable()
                            ->helperText('Tolak jika akurasi GPS lebih kasar dari nilai ini'),
                        TextInput::make('ukuran_foto_maks_kb')
                            ->label('Ukuran Foto Maksimal')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(2048)
                            ->suffix('KB'),
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
