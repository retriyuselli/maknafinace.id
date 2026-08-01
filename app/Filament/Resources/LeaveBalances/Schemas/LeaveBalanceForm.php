<?php

namespace App\Filament\Resources\LeaveBalances\Schemas;

use App\Models\LeaveType;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class LeaveBalanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Karyawan & Jenis Cuti')
                    ->description('Data ini dikelola secara otomatis berdasarkan pengajuan cuti yang disetujui.')
                    ->schema([
                        Select::make('user_id')
                            ->label('Karyawan')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->getOptionLabelFromRecordUsing(function (User $record): string {
                                return "{$record->name} ({$record->employee_id})";
                            }),
                        Select::make('leave_type_id')
                            ->label('Jenis Cuti')
                            ->relationship('leaveType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->getOptionLabelFromRecordUsing(fn (LeaveType $record): string => "{$record->name} (Max: {$record->max_days_per_year} hari)")
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $leaveType = LeaveType::find($state);
                                    if ($leaveType) {
                                        $set('allocated_days', $leaveType->max_days_per_year);
                                    }
                                }
                            })
                            ->live(),
                    ])->columns(2),

                Section::make('Perhitungan Saldo Cuti')
                    ->description('Semua perhitungan dilakukan otomatis berdasarkan pengajuan cuti yang disetujui.')
                    ->schema([
                        TextInput::make('allocated_days')
                            ->label('Hak Cuti (Hari)')
                            ->required()
                            ->numeric()
                            ->readOnly()
                            ->minValue(0)
                            ->default(function ($get) {
                                $leaveTypeId = $get('leave_type_id');
                                if ($leaveTypeId) {
                                    $leaveType = LeaveType::find($leaveTypeId);

                                    return $leaveType?->max_days_per_year ?? 0;
                                }

                                return 0;
                            })
                            ->helperText('Otomatis mengikuti max_days_per_year dari jenis cuti. Dapat disesuaikan manual jika diperlukan.'),

                        TextInput::make('carried_over_days')
                            ->label('Cuti Carry Over')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->visible(fn () => Auth::user()->roles->contains('name', 'super_admin'))
                            ->helperText('Sisa cuti tahun lalu. Berlaku hingga 31 Maret tahun ini.'),

                        TextInput::make('used_days')
                            ->label('Cuti Terpakai (Hari)')
                            ->numeric()
                            ->minValue(0)
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Otomatis dihitung dari pengajuan cuti yang disetujui'),
                        TextInput::make('remaining_days')
                            ->label('Sisa Cuti (Hari)')
                            ->numeric()
                            ->readOnly()
                            ->dehydrated()
                            ->helperText('Total Sisa = (Hak Cuti + Carry Over Valid) - Terpakai'),
                    ])->columns(2),

                Section::make('Informasi Tambahan')
                    ->schema([
                        Placeholder::make('usage_info')
                            ->label('Statistik Penggunaan')
                            ->content(function ($record) {
                                if (! $record) {
                                    return 'Data akan tersedia setelah record disimpan';
                                }

                                $percentage = $record->usage_percentage;
                                $status = match (true) {
                                    $percentage >= 100 => '🔴 Saldo Habis',
                                    $percentage >= 80 => '🟡 Saldo Kritis',
                                    default => '🟢 Saldo Aman'
                                };

                                return "Penggunaan: {$percentage}% - Status: {$status}";
                            }),
                        Placeholder::make('auto_info')
                            ->label('Informasi Sistem')
                            ->content('Saldo cuti ini akan otomatis terupdate ketika ada pengajuan cuti yang disetujui atau ditolak.'),
                    ])->columns(2),
            ]);
    }
}
