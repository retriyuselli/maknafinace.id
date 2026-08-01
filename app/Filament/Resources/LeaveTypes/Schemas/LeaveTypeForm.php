<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use App\Models\LeaveType;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Jenis Cuti')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Jenis Cuti')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Cuti Tahunan, Cuti Sakit, Cuti Melahirkan')
                            ->unique(LeaveType::class, 'name', ignoreRecord: true),
                        TextInput::make('max_days_per_year')
                            ->label('Maksimal Hari Per Tahun')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(365)
                            ->suffix('hari')
                            ->placeholder('12')
                            ->helperText('Jumlah maksimal hari cuti yang dapat diambil dalam satu tahun'),
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Masukkan keterangan jenis cuti')
                            ->maxLength(500),
                    ])->columns(2),
            ]);
    }
}
