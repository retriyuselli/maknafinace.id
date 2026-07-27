<?php

namespace App\Filament\Resources\PengajuanLemburs\Schemas;

use App\Models\PengajuanLembur;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PengajuanLemburForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengajuan Lembur')
                    ->schema([
                        Select::make('user_id')
                            ->label('Karyawan')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('absensi_id')
                            ->label('Absensi (opsional)')
                            ->relationship(
                                'absensi',
                                'tanggal',
                                fn ($query, $get) => $query
                                    ->when($get('user_id'), fn ($q, $userId) => $q->where('user_id', $userId))
                                    ->orderByDesc('tanggal')
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => $record->tanggal?->format('d/m/Y').' · '.strtoupper($record->status)
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->native(false)
                            ->default(now()),
                        DateTimePicker::make('mulai_pada')
                            ->label('Mulai')
                            ->required()
                            ->seconds(false)
                            ->native(false),
                        DateTimePicker::make('selesai_pada')
                            ->label('Selesai')
                            ->required()
                            ->seconds(false)
                            ->native(false),
                        TextInput::make('menit')
                            ->label('Durasi (menit)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Dihitung otomatis saat disimpan'),
                        Textarea::make('alasan')
                            ->label('Alasan')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                PengajuanLembur::STATUS_MENUNGGU => 'Menunggu',
                                PengajuanLembur::STATUS_DISETUJUI => 'Disetujui',
                                PengajuanLembur::STATUS_DITOLAK => 'Ditolak',
                            ])
                            ->default(PengajuanLembur::STATUS_MENUNGGU)
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('catatan')
                            ->label('Catatan Peninjau')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
