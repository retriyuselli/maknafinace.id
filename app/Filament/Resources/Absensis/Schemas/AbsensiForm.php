<?php

namespace App\Filament\Resources\Absensis\Schemas;

use App\Models\Absensi;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AbsensiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Absensi')
                    ->schema([
                        Select::make('user_id')
                            ->label('Karyawan')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                Absensi::STATUS_HADIR => 'Hadir',
                                Absensi::STATUS_TERLAMBAT => 'Terlambat',
                                Absensi::STATUS_ALFA => 'Alfa',
                                Absensi::STATUS_CUTI => 'Cuti',
                                Absensi::STATUS_LIBUR => 'Libur',
                                Absensi::STATUS_LIBUR_MINGGUAN => 'Libur Mingguan',
                                Absensi::STATUS_SETENGAH_HARI => 'Setengah Hari',
                                Absensi::STATUS_REMOTE => 'Remote',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('sumber')
                            ->label('Sumber')
                            ->options([
                                'mobile' => 'Mobile',
                                'web' => 'Web',
                                'admin' => 'Admin',
                            ])
                            ->native(false)
                            ->nullable(),
                        DateTimePicker::make('jam_masuk')
                            ->label('Jam Masuk')
                            ->seconds(false)
                            ->native(false),
                        DateTimePicker::make('jam_pulang')
                            ->label('Jam Pulang')
                            ->seconds(false)
                            ->native(false),
                        TextInput::make('menit_kerja')
                            ->label('Menit Kerja')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('menit'),
                        TextInput::make('menit_terlambat')
                            ->label('Menit Terlambat')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('menit'),
                        TextInput::make('menit_pulang_cepat')
                            ->label('Menit Pulang Cepat')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->suffix('menit'),
                        Select::make('leave_request_id')
                            ->label('Permohonan Cuti')
                            ->relationship('leaveRequest', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} — {$record->user?->name} ({$record->status})")
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
