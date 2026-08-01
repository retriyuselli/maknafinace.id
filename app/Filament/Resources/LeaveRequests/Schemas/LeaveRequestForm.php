<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Models\LeaveBalance;
use App\Models\LeaveBalanceHistory;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Permohonan Cuti')
                    ->description('Informasi dasar tentang permohonan cuti')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Karyawan')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->disabled(function () {
                                        $user = Auth::user();

                                        return $user ? ! $user->roles->contains('name', 'super_admin') : true;
                                    })
                                    ->dehydrated(true)
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => Auth::id())
                                    ->columnSpan(1)
                                    ->live(),

                                Select::make('leave_type_id')
                                    ->label('Jenis Cuti')
                                    ->relationship('leaveType', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1)
                                    ->live(),

                                Select::make('leave_balance_history_id')
                                    ->label('Pilih Sumber Cuti Pengganti')
                                    ->visible(fn ($get) => LeaveType::find($get('leave_type_id'))?->name === 'Cuti Pengganti')
                                    ->options(function ($get, ?LeaveRequest $record) {
                                        $userId = $get('user_id');
                                        $leaveTypeId = $get('leave_type_id');

                                        if (! $userId || ! $leaveTypeId) {
                                            return [];
                                        }

                                        $leaveType = LeaveType::find($leaveTypeId);
                                        if (! $leaveType || $leaveType->name !== 'Cuti Pengganti') {
                                            return [];
                                        }

                                        $balance = LeaveBalance::where('user_id', $userId)
                                            ->where('leave_type_id', $leaveTypeId)
                                            ->first();

                                        if (! $balance) {
                                            return [];
                                        }

                                        $usedHistoryIds = LeaveRequest::query()
                                            ->whereNotNull('leave_balance_history_id')
                                            ->when($record, function ($query) use ($record) {
                                                $query->where('id', '!=', $record->id);
                                            })
                                            ->pluck('leave_balance_history_id')
                                            ->toArray();

                                        return $balance->histories()
                                            ->whereNotIn('id', $usedHistoryIds)
                                            ->get()
                                            ->mapWithKeys(function ($history) {
                                                $date = Carbon::parse($history->transaction_date)->format('d/m/Y');

                                                return [$history->id => "{$date} - {$history->reason} (+{$history->amount})"];
                                            });
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if (! $state) {
                                            return;
                                        }
                                        $history = LeaveBalanceHistory::find($state);
                                        if ($history) {
                                            $set('substitution_date', $history->transaction_date);
                                            $set('substitution_notes', $history->reason);
                                        }
                                    })
                                    ->columnSpan(2),

                                DatePicker::make('substitution_date')
                                    ->label('Tanggal Pengganti')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required(fn ($get) => LeaveType::find($get('leave_type_id'))?->name === 'Cuti Pengganti')
                                    ->visible(fn ($get) => LeaveType::find($get('leave_type_id'))?->name === 'Cuti Pengganti'),

                                TextInput::make('substitution_notes')
                                    ->label('Alasan Pengganti')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required(fn ($get) => LeaveType::find($get('leave_type_id'))?->name === 'Cuti Pengganti')
                                    ->visible(fn ($get) => LeaveType::find($get('leave_type_id'))?->name === 'Cuti Pengganti'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Tanggal Mulai')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $endDate = $get('end_date');
                                        if ($state && $endDate) {
                                            $startDate = Carbon::parse($state);
                                            $endDate = Carbon::parse($endDate);
                                            $totalDays = $startDate->diffInDays($endDate) + 1;
                                            $set('total_days', $totalDays);
                                        }
                                    }),

                                DatePicker::make('end_date')
                                    ->label('Tanggal Selesai')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $startDate = $get('start_date');
                                        if ($startDate && $state) {
                                            $startDate = Carbon::parse($startDate);
                                            $endDate = Carbon::parse($state);
                                            $totalDays = $startDate->diffInDays($endDate) + 1;
                                            $set('total_days', $totalDays);
                                        }
                                    }),

                                TextInput::make('total_days')
                                    ->label('Total Hari')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(),
                            ]),

                        Textarea::make('reason')
                            ->label('Alasan Cuti')
                            ->rows(3)
                            ->placeholder('Silakan berikan alasan untuk permohonan cuti Anda...'),

                        TextInput::make('emergency_contact')
                            ->label('Kontak Darurat')
                            ->placeholder('Informasi kontak darurat (opsional)')
                            ->helperText('Nama dan nomor telepon yang dapat dihubungi selama cuti'),

                        FileUpload::make('documents')
                            ->label('Dokumen Pendukung')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(2048)
                            ->directory('leave-documents')
                            ->multiple()
                            ->openable()
                            ->maxFiles(3)
                            ->helperText('Upload dokumen pendukung (PDF - maksimal 2MB per file, maksimal 3 file)'),

                        Select::make('replacement_employee_id')
                            ->label('Karyawan Pengganti')
                            ->relationship('replacementEmployee', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih karyawan pengganti (opsional)')
                            ->helperText('Pilih karyawan yang akan menangani tanggung jawab Anda selama cuti')
                            ->options(function () {
                                return User::where('status', 'active')
                                    ->where('id', '!=', Auth::id())
                                    ->pluck('name', 'id');
                            }),
                    ])
                    ->columnSpanFull(),

                Section::make('Informasi Persetujuan')
                    ->description('Status dan detail persetujuan')
                    ->visible(function () {
                        $user = Auth::user();

                        return $user ? $user->roles->contains('name', 'super_admin') : false;
                    })
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'pending' => 'Menunggu',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                    ])
                                    ->default('pending')
                                    ->required()
                                    ->reactive(),

                                Select::make('approved_by')
                                    ->label('Disetujui Oleh')
                                    ->relationship('approver', 'name', function (Builder $query) {
                                        return $query->whereHas('roles', function ($q) {
                                            $q->where('name', 'super_admin');
                                        });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (callable $get) => in_array($get('status'), ['approved', 'rejected'])),
                            ]),

                        Textarea::make('approval_notes')
                            ->label('Catatan Persetujuan')
                            ->rows(2)
                            ->placeholder('Tambahkan catatan tentang persetujuan/penolakan...')
                            ->visible(fn (callable $get) => in_array($get('status'), ['approved', 'rejected'])),
                    ]),
            ]);
    }
}
