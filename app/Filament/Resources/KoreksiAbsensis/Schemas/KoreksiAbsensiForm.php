<?php

namespace App\Filament\Resources\KoreksiAbsensis\Schemas;

use App\Models\KoreksiAbsensi;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KoreksiAbsensiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengajuan Koreksi')
                    ->schema([
                        Select::make('user_id')
                            ->label('Karyawan')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('absensi_id')
                            ->label('Absensi')
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
                            ->required(),
                        DateTimePicker::make('jam_masuk_diajukan')
                            ->label('Jam Masuk Diajukan')
                            ->seconds(false)
                            ->native(false),
                        DateTimePicker::make('jam_pulang_diajukan')
                            ->label('Jam Pulang Diajukan')
                            ->seconds(false)
                            ->native(false),
                        Textarea::make('alasan')
                            ->label('Alasan')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                KoreksiAbsensi::STATUS_MENUNGGU => 'Menunggu',
                                KoreksiAbsensi::STATUS_DISETUJUI => 'Disetujui',
                                KoreksiAbsensi::STATUS_DITOLAK => 'Ditolak',
                            ])
                            ->default(KoreksiAbsensi::STATUS_MENUNGGU)
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('catatan_peninjau')
                            ->label('Catatan Peninjau')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
