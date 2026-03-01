<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\Status;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema as DBSchema;

class UsersTable
{
    private static function isSuperAdmin(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $user->id)
            ->where('roles.name', 'super_admin')
            ->exists();
    }

    private static function isTargetUserSuperAdmin($record): bool
    {
        if (! $record) {
            return false;
        }

        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $record->id)
            ->where('roles.name', 'super_admin')
            ->exists();
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                ImageColumn::make('avatar_url')
                    ->label('Foto Profil')
                    ->disk('public')
                    ->defaultImageUrl(function ($record) {
                        $name = $record->name ?? 'User';
                        $initials = collect(explode(' ', $name))
                            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
                            ->take(2)
                            ->implode('');

                        return "https://ui-avatars.com/api/?name={$initials}&background=3b82f6&color=ffffff&size=128&font-size=0.33";
                    })
                    ->getStateUsing(function ($record) {
                        if ($record->avatar_url) {
                            return $record->avatar_url;
                        }

                        return null;
                    })
                    ->circular()
                    ->size(40)
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->tooltip(function ($record) {
                        if ($record->avatar_url) {
                            return 'Klik untuk melihat foto profil';
                        }

                        return 'Foto profil default berdasarkan inisial nama';
                    }),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->icon('heroicon-o-envelope'),

                TextColumn::make('phone_number')
                    ->label('Telepon')
                    ->searchable()
                    ->placeholder('Tidak ada')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-phone'),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(',')
                    ->color(function (string $state): string {
                        return match ($state) {
                            'super_admin' => 'danger',
                            'admin' => 'warning',
                            'Account Manager' => 'info',
                            'employee' => 'success',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('roles_count')
                    ->label('Jumlah Role')
                    ->getStateUsing(function (User $record): string {
                        $count = $record->roles_count ?? $record->roles()->count();

                        return $count.' Role'.($count > 1 ? 's' : '');
                    })
                    ->badge()
                    ->color(function (User $record): string {
                        $count = $record->roles_count ?? $record->roles()->count();

                        return match (true) {
                            $count === 0 => 'gray',
                            $count === 1 => 'success',
                            $count === 2 => 'warning',
                            $count >= 3 => 'danger',
                            default => 'primary',
                        };
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('roles_count', $direction);
                    })
                    ->icon('heroicon-o-user-group')
                    ->tooltip(function (User $record): string {
                        $roles = $record->roles->pluck('name')->toArray();

                        return empty($roles) ? 'Tidak ada role' : 'Roles: '.implode(', ', $roles);
                    }),

                TextColumn::make('statuses.status_name')
                    ->label('Status Jabatan')
                    ->badge()
                    ->searchable()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'Admin' => 'danger',
                            'Finance' => 'warning',
                            'HRD' => 'info',
                            'Account Manager' => 'primary',
                            'Staff' => 'success',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('status')
                    ->label('Status Akun')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'active' => 'success',
                            'inactive' => 'warning',
                            'terminated' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'active' => 'Aktif',
                            'inactive' => 'Nonaktif',
                            'terminated' => 'Terminated',
                            default => $state,
                        };
                    }),

                TextColumn::make('department')
                    ->label('Departemen')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'bisnis' => 'success',
                            'operasional' => 'primary',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'bisnis' => 'Bisnis',
                            'operasional' => 'Operasional',
                            default => $state,
                        };
                    }),

                TextColumn::make('payrolls.monthly_salary')
                    ->label('Gaji Bulanan')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('Belum diatur')
                    ->getStateUsing(function ($record) {
                        $latestPayroll = $record->payrolls()->latest()->first();

                        return $latestPayroll ? $latestPayroll->monthly_salary : null;
                    })
                    ->color(function ($state) {
                        if (! $state) {
                            return 'gray';
                        }
                        if ($state >= 8000000) {
                            return 'success';
                        }
                        if ($state >= 5000000) {
                            return 'warning';
                        }

                        return 'danger';
                    })
                    ->icon('heroicon-o-banknotes')
                    ->tooltip(function ($record) {
                        $latestPayroll = $record->payrolls()->latest()->first();
                        if (! $latestPayroll) {
                            return 'Belum ada data payroll';
                        }

                        return sprintf(
                            "Gaji Tahunan: %s\nBonus: %s\nTotal: %s\nPeriode: %s",
                            $latestPayroll->formatted_annual_salary_with_prefix,
                            $latestPayroll->formatted_bonus_with_prefix,
                            $latestPayroll->formatted_total_compensation_with_prefix,
                            $latestPayroll->pay_period ?? 'N/A'
                        );
                    }),

                TextColumn::make('total_leave_taken')
                    ->label('Cuti Diambil')
                    ->getStateUsing(function ($record) {
                        return $record->leaveRequests()
                            ->where('status', 'approved')
                            ->whereYear('start_date', date('Y'))
                            ->sum('total_days');
                    })
                    ->formatStateUsing(function ($state) {
                        return $state.' hari';
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state == 0) {
                            return 'gray';
                        }
                        if ($state <= 6) {
                            return 'success';
                        }
                        if ($state <= 12) {
                            return 'warning';
                        }

                        return 'danger';
                    })
                    ->icon('heroicon-o-calendar-days')
                    ->tooltip(function ($record) {
                        $currentYear = date('Y');
                        $totalApproved = $record->leaveRequests()
                            ->where('status', 'approved')
                            ->whereYear('start_date', $currentYear)
                            ->sum('total_days');

                        $totalPending = $record->leaveRequests()
                            ->where('status', 'pending')
                            ->whereYear('start_date', $currentYear)
                            ->sum('total_days');

                        $totalRejected = $record->leaveRequests()
                            ->where('status', 'rejected')
                            ->whereYear('start_date', $currentYear)
                            ->sum('total_days');

                        return sprintf(
                            "Tahun %s:\nDisetujui: %d hari\nMenunggu: %d hari\nDitolak: %d hari",
                            $currentYear,
                            $totalApproved,
                            $totalPending,
                            $totalRejected
                        );
                    })
                    ->sortable(),

                TextColumn::make('remaining_leave')
                    ->label('Sisa Cuti')
                    ->getStateUsing(function ($record) {
                        $annualLeaveAllowance = 12;
                        $usedLeave = $record->leaveRequests()
                            ->where('status', 'approved')
                            ->whereYear('start_date', date('Y'))
                            ->sum('total_days');

                        return max(0, $annualLeaveAllowance - $usedLeave);
                    })
                    ->formatStateUsing(function ($state) {
                        return $state.' hari';
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state >= 8) {
                            return 'success';
                        }
                        if ($state >= 4) {
                            return 'warning';
                        }
                        if ($state > 0) {
                            return 'danger';
                        }

                        return 'gray';
                    })
                    ->icon('heroicon-o-clock')
                    ->tooltip(function ($record) {
                        $annualLeaveAllowance = 12;
                        $currentYear = date('Y');
                        $usedLeave = $record->leaveRequests()
                            ->where('status', 'approved')
                            ->whereYear('start_date', $currentYear)
                            ->sum('total_days');

                        $remainingLeave = max(0, $annualLeaveAllowance - $usedLeave);
                        $percentage = $annualLeaveAllowance > 0 ? round(($usedLeave / $annualLeaveAllowance) * 100, 1) : 0;

                        return sprintf(
                            "Jatah Tahunan: %d hari\nTerpakai: %d hari (%.1f%%)\nSisa: %d hari",
                            $annualLeaveAllowance,
                            $usedLeave,
                            $percentage,
                            $remainingLeave
                        );
                    })
                    ->sortable(),

                TextColumn::make('hire_date')
                    ->label('Tanggal Mulai')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Tidak ada')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-calendar'),

                TextColumn::make('expire_date')
                    ->label('Kedaluwarsa')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Tidak ada batas')
                    ->sortable()
                    ->color(function ($record) {
                        if (! $record->expire_date) {
                            return 'gray';
                        }
                        if (method_exists($record, 'isExpired') && $record->isExpired()) {
                            return 'danger';
                        }
                        if (method_exists($record, 'isExpiringSoon') && $record->isExpiringSoon()) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->badge(function ($record) {
                        if (! $record->expire_date) {
                            return false;
                        }

                        return (method_exists($record, 'isExpired') && $record->isExpired()) ||
                               (method_exists($record, 'isExpiringSoon') && $record->isExpiringSoon());
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if (! $state) {
                            return 'Tidak ada batas';
                        }
                        if (method_exists($record, 'isExpired') && $record->isExpired()) {
                            return $state.' (Kedaluwarsa)';
                        }
                        if (method_exists($record, 'isExpiringSoon') && $record->isExpiringSoon()) {
                            $days = method_exists($record, 'getDaysUntilExpiration') ? $record->getDaysUntilExpiration() : 0;

                            return $state." ($days hari lagi)";
                        }

                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'male' => 'Laki-laki',
                            'female' => 'Perempuan',
                            default => 'Tidak diketahui',
                        };
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'male' => 'blue',
                            'female' => 'pink',
                            default => 'gray',
                        };
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->multiple(),

                SelectFilter::make('job_status')
                    ->label('Status Jabatan')
                    ->options(fn () => Status::query()->pluck('status_name', 'id')->all())
                    ->attribute('status_id'),

                SelectFilter::make('account_status')
                    ->label('Status Akun')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'terminated' => 'Terminated',
                    ])
                    ->attribute('status'),

                SelectFilter::make('department')
                    ->label('Departemen')
                    ->options([
                        'bisnis' => 'Bisnis',
                        'operasional' => 'Operasional',
                    ]),

                SelectFilter::make('salary_range')
                    ->label('Range Gaji')
                    ->options([
                        'below_5m' => 'Di bawah 5 Juta',
                        '5m_8m' => '5 - 8 Juta',
                        'above_8m' => 'Di atas 8 Juta',
                        'no_salary' => 'Belum Ada Gaji',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value']) || ! $data['value']) {
                            return $query;
                        }

                        switch ($data['value']) {
                            case 'below_5m':
                                return $query->whereHas('payrolls', function (Builder $q) {
                                    $q->where('monthly_salary', '<', 5000000);
                                });
                            case '5m_8m':
                                return $query->whereHas('payrolls', function (Builder $q) {
                                    $q->whereBetween('monthly_salary', [5000000, 8000000]);
                                });
                            case 'above_8m':
                                return $query->whereHas('payrolls', function (Builder $q) {
                                    $q->where('monthly_salary', '>', 8000000);
                                });
                            case 'no_salary':
                                return $query->whereDoesntHave('payrolls');
                            default:
                                return $query;
                        }
                    }),

                SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ]),

                Filter::make('expired')
                    ->label('Kedaluwarsa')
                    ->query(fn (Builder $query): Builder => $query->where('expire_date', '<', now()))
                    ->toggle(),

                Filter::make('active')
                    ->label('Aktif (Tanpa Batas)')
                    ->query(fn (Builder $query): Builder => $query->whereNull('expire_date'))
                    ->toggle(),

                SelectFilter::make('leave_usage')
                    ->label('Penggunaan Cuti')
                    ->options([
                        'no_leave' => 'Belum Pernah Cuti',
                        'low_usage' => 'Penggunaan Rendah (≤ 3 hari)',
                        'medium_usage' => 'Penggunaan Sedang (4-8 hari)',
                        'high_usage' => 'Penggunaan Tinggi (> 8 hari)',
                        'over_limit' => 'Melebihi Jatah (> 12 hari)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value']) || ! $data['value']) {
                            return $query;
                        }

                        $currentYear = date('Y');

                        switch ($data['value']) {
                            case 'no_leave':
                                return $query->whereDoesntHave('leaveRequests', function (Builder $q) use ($currentYear) {
                                    $q->where('status', 'approved')
                                        ->whereYear('start_date', $currentYear);
                                });

                            case 'low_usage':
                                return $query->whereHas('leaveRequests', function (Builder $q) use ($currentYear) {
                                    $q->where('status', 'approved')
                                        ->whereYear('start_date', $currentYear);
                                })->whereRaw("
                                    (SELECT COALESCE(SUM(total_days), 0) 
                                     FROM leave_requests 
                                     WHERE user_id = users.id 
                                     AND status = 'approved' 
                                     AND YEAR(start_date) = ?) <= 3
                                ", [$currentYear]);

                            case 'medium_usage':
                                return $query->whereRaw("
                                    (SELECT COALESCE(SUM(total_days), 0) 
                                     FROM leave_requests 
                                     WHERE user_id = users.id 
                                     AND status = 'approved' 
                                     AND YEAR(start_date) = ?) BETWEEN 4 AND 8
                                ", [$currentYear]);

                            case 'high_usage':
                                return $query->whereRaw("
                                    (SELECT COALESCE(SUM(total_days), 0) 
                                     FROM leave_requests 
                                     WHERE user_id = users.id 
                                     AND status = 'approved' 
                                     AND YEAR(start_date) = ?) BETWEEN 9 AND 12
                                ", [$currentYear]);

                            case 'over_limit':
                                return $query->whereRaw("
                                    (SELECT COALESCE(SUM(total_days), 0) 
                                     FROM leave_requests 
                                     WHERE user_id = users.id 
                                     AND status = 'approved' 
                                     AND YEAR(start_date) = ?) > 12
                                ", [$currentYear]);

                            default:
                                return $query;
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->color('info')
                    ->visible(function () {
                        return ! static::isSuperAdmin();
                    }),

                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat')
                        ->color('info'),

                    EditAction::make()
                        ->label('Edit')
                        ->color('warning')
                        ->visible(function ($record) {
                            if (static::isSuperAdmin()) {
                                return true;
                            }
                            $user = Auth::user();

                            return $user && $user->id === $record->id;
                        }),

                    Action::make('reset_password')
                        ->label('Reset Password')
                        ->icon('heroicon-o-key')
                        ->color('secondary')
                        ->schema([
                            TextInput::make('new_password')
                                ->label('Password Baru')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->maxLength(255),
                            TextInput::make('confirm_password')
                                ->label('Konfirmasi Password')
                                ->password()
                                ->required()
                                ->same('new_password'),
                        ])
                        ->action(function (array $data, $record): void {
                            $record->update([
                                'password' => Hash::make($data['new_password']),
                            ]);

                            Notification::make()
                                ->title('Password berhasil direset')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Reset Password User')
                        ->modalDescription('Masukkan password baru untuk user ini')
                        ->modalSubmitActionLabel('Reset Password')
                        ->modalCancelActionLabel('Cancel')
                        ->modalContent(view('filament.modal.reset-password-content'))
                        ->visible(function ($record) {
                            return static::isSuperAdmin();
                        }),

                    Action::make('manage_payroll')
                        ->label('Kelola Gaji')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->url(function ($record) {
                            $latestPayroll = $record->payrolls()->latest()->first();
                            if ($latestPayroll) {
                                return route('filament.admin.resources.payrolls.edit', $latestPayroll);
                            } else {
                                return route('filament.admin.resources.payrolls.create', ['user_id' => $record->id]);
                            }
                        })
                        ->openUrlInNewTab()
                        ->tooltip(function ($record) {
                            $latestPayroll = $record->payrolls()->latest()->first();
                            if ($latestPayroll) {
                                return sprintf(
                                    "Gaji saat ini: %s\nKlik untuk edit",
                                    'Rp '.number_format($latestPayroll->monthly_salary, 0, '.', '.')
                                );
                            }

                            return 'Belum ada data gaji. Klik untuk menambah.';
                        })
                        ->visible(function () {
                            return static::isSuperAdmin();
                        }),

                    Action::make('view_salary_history')
                        ->label('Riwayat Gaji')
                        ->icon('heroicon-o-chart-bar')
                        ->color('info')
                        ->modalHeading(function ($record) {
                            return "Riwayat Gaji - {$record->name}";
                        })
                        ->modalContent(function ($record) {
                            $payrolls = $record->payrolls()->orderBy('created_at', 'desc')->get();

                            if ($payrolls->isEmpty()) {
                                return view('filament.modals.no-payroll-history');
                            }

                            return view('filament.modals.salary-history', [
                                'payrolls' => $payrolls,
                                'user' => $record,
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->visible(function ($record) {
                            return static::isSuperAdmin() && $record->payrolls()->exists();
                        }),

                    Action::make('deactivate_user')
                        ->label('Nonaktifkan Permanen')
                        ->icon('heroicon-o-archive-box-x-mark')
                        ->color('danger')
                        ->action(function ($record): void {
                            $record->update([
                                'status' => 'terminated',
                                'expire_date' => now(),
                                'last_working_date' => now()->toDateString(),
                            ]);

                            Notification::make()
                                ->title("User {$record->name} berhasil dinonaktifkan permanen")
                                ->body('User telah dinonaktifkan dan tidak dapat mengakses sistem.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan User Permanen')
                        ->modalDescription(function ($record) {
                            return "Apakah Anda yakin ingin menonaktifkan {$record->name} secara permanen? User tidak akan bisa mengakses sistem lagi, namun data historis akan tetap tersimpan.";
                        })
                        ->visible(function ($record) {
                            return static::isSuperAdmin() && $record->status !== 'terminated';
                        }),

                    Action::make('delete_user')
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(function ($record) {
                            $tablesToCheck = [
                                'nota_dinas' => ['approved_by', 'pengirim_id'],
                                'leave_requests' => ['user_id', 'replacement_employee_id'],
                                'payrolls' => ['user_id'],
                                'leave_balances' => ['user_id'],
                                'annual_summaries' => ['user_id'],
                            ];

                            $constraintTables = [];
                            foreach ($tablesToCheck as $table => $columns) {
                                if (! DBSchema::hasTable($table)) {
                                    continue;
                                }
                                foreach ($columns as $column) {
                                    $count = DB::table($table)->where($column, $record->id)->count();
                                    if ($count > 0) {
                                        $constraintTables[] = $table;
                                        break;
                                    }
                                }
                            }

                            return empty($constraintTables) ? 'Hapus User' : 'Tidak Dapat Menghapus User';
                        })
                        ->modalDescription(function ($record) {
                            $tablesToCheck = [
                                'nota_dinas' => ['approved_by', 'pengirim_id'],
                                'leave_requests' => ['user_id', 'replacement_employee_id'],
                                'payrolls' => ['user_id'],
                                'leave_balances' => ['user_id'],
                                'annual_summaries' => ['user_id'],
                            ];

                            $details = [];
                            foreach ($tablesToCheck as $table => $columns) {
                                if (! DBSchema::hasTable($table)) {
                                    continue;
                                }
                                $tableCount = 0;
                                foreach ($columns as $column) {
                                    $c = DB::table($table)->where($column, $record->id)->count();
                                    $tableCount += $c;
                                }
                                if ($tableCount > 0) {
                                    if ($table === 'nota_dinas') {
                                        $approvedCount = DB::table('nota_dinas')->where('approved_by', $record->id)->count();
                                        $sentCount = DB::table('nota_dinas')->where('pengirim_id', $record->id)->count();
                                        $details[] = '• Nota Dinas: sebagai pengirim ('.$sentCount.') atau approver ('.$approvedCount.')';
                                    } elseif ($table === 'leave_requests') {
                                        $asUser = DB::table('leave_requests')->where('user_id', $record->id)->count();
                                        $asReplacement = DB::table('leave_requests')->where('replacement_employee_id', $record->id)->count();
                                        $details[] = '• Pengajuan Cuti: sebagai pemohon ('.$asUser.') atau pengganti ('.$asReplacement.')';
                                    } elseif ($table === 'payrolls') {
                                        $details[] = '• Payroll: data gaji terkait ('.$tableCount.')';
                                    } elseif ($table === 'leave_balances') {
                                        $details[] = '• Saldo Cuti: catatan saldo cuti ('.$tableCount.')';
                                    } elseif ($table === 'annual_summaries') {
                                        $details[] = '• Ringkasan Tahunan: laporan tahunan terkait ('.$tableCount.')';
                                    }
                                }
                            }

                            if (empty($details)) {
                                return 'Apakah Anda yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan.';
                            }

                            return "User tidak dapat dihapus karena masih memiliki data terkait:\n".implode("\n", $details);
                        })
                        ->action(function ($record) {
                            if (($record->status ?? null) === 'terminated') {
                                if (DBSchema::hasTable('nota_dinas')) {
                                    DB::table('nota_dinas')
                                        ->where('approved_by', $record->id)
                                        ->update(['approved_by' => null]);
                                }

                                if (DBSchema::hasTable('leave_requests')) {
                                    DB::table('leave_requests')
                                        ->where('replacement_employee_id', $record->id)
                                        ->update(['replacement_employee_id' => null]);
                                }

                                $pengirimBlocked = DBSchema::hasTable('nota_dinas')
                                    && DB::table('nota_dinas')->where('pengirim_id', $record->id)->exists();

                                if ($pengirimBlocked) {
                                    Notification::make()
                                        ->title('Tidak dapat dihapus')
                                        ->body('User adalah pengirim pada Nota Dinas. Reassign pengirim terlebih dahulu sebelum menghapus.')
                                        ->warning()
                                        ->persistent()
                                        ->send();

                                    return;
                                }

                                $record->delete();

                                Notification::make()
                                    ->success()
                                    ->title('User berhasil dihapus')
                                    ->send();

                                return;
                            }
                            $tablesToCheck = [
                                'nota_dinas' => ['approved_by', 'pengirim_id'],
                                'leave_requests' => ['user_id', 'replacement_employee_id'],
                                'payrolls' => ['user_id'],
                                'leave_balances' => ['user_id'],
                                'annual_summaries' => ['user_id'],
                            ];

                            $hasConstraints = false;
                            foreach ($tablesToCheck as $table => $columns) {
                                if (! DBSchema::hasTable($table)) {
                                    continue;
                                }
                                foreach ($columns as $column) {
                                    if (DB::table($table)->where($column, $record->id)->exists()) {
                                        $hasConstraints = true;
                                        break 2;
                                    }
                                }
                            }

                            if ($hasConstraints) {
                                Notification::make()
                                    ->title('Tidak dapat dihapus')
                                    ->body('User memiliki data terkait dan tidak dapat dihapus.')
                                    ->warning()
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            $record->delete();

                            Notification::make()
                                ->success()
                                ->title('User berhasil dihapus')
                                ->send();
                        })
                        ->visible(function ($record) {
                            return static::isSuperAdmin();
                        }),
                ])
                    ->label('Aksi')
                    ->color('primary')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->size('sm')
                    ->button()
                    ->visible(function () {
                        return static::isSuperAdmin();
                    }),
            ])
            ->headerActions([
                Action::make('download_blank_form')
                    ->label('Download Form Kosong')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->url(route('user-form.blank'))
                    ->openUrlInNewTab()
                    ->tooltip('Download formulir pendaftaran karyawan kosong untuk diisi manual')
                    ->visible(function () {
                        $user = Auth::user();

                        return static::isSuperAdmin() || ($user && ($user->roles->contains('name', 'hr_manager') || $user->roles->contains('name', 'admin')));
                    }),

                Action::make('hr_help')
                    ->label('Panduan HR')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('gray')
                    ->modalHeading('Panduan Penggunaan Form PDF')
                    ->modalContent(view('filament.modals.hr-form-help'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->visible(function () {
                        $user = Auth::user();

                        return static::isSuperAdmin() || ($user && ($user->roles->contains('name', 'hr_manager') || $user->roles->contains('name', 'admin')));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->action(function ($records, $livewire) {
                            if (! static::isSuperAdmin()) {
                                $records = $records->filter(function ($record) {
                                    return ! static::isTargetUserSuperAdmin($record);
                                });
                            }

                            $deletedCount = 0;
                            $failedCount = 0;
                            $failedUsers = [];

                            foreach ($records as $record) {
                                try {
                                    $hasConstraints = false;
                                    $constraintTables = [];

                                    $tablesToCheck = [
                                        'nota_dinas' => ['approved_by', 'pengirim_id'],
                                        'leave_requests' => ['user_id', 'replacement_employee_id'],
                                        'payrolls' => ['user_id'],
                                        'leave_balances' => ['user_id'],
                                        'annual_summaries' => ['user_id'],
                                    ];

                                    foreach ($tablesToCheck as $table => $columns) {
                                        if (! DBSchema::hasTable($table)) {
                                            continue;
                                        }
                                        foreach ($columns as $column) {
                                            $count = DB::table($table)->where($column, $record->id)->count();
                                            if ($count > 0) {
                                                $hasConstraints = true;
                                                $constraintTables[] = $table;
                                                break;
                                            }
                                        }
                                    }

                                    if ($hasConstraints) {
                                        $failedCount++;
                                        $failedUsers[] = [
                                            'name' => $record->name,
                                            'tables' => array_unique($constraintTables),
                                        ];
                                    } else {
                                        $record->delete();
                                        $deletedCount++;
                                    }
                                } catch (Exception $e) {
                                    $failedCount++;
                                    $failedUsers[] = [
                                        'name' => $record->name,
                                        'error' => 'Database constraint error',
                                    ];
                                }
                            }

                            if ($deletedCount > 0) {
                                Notification::make()
                                    ->title("$deletedCount user berhasil dihapus")
                                    ->success()
                                    ->send();
                            }

                            if ($failedCount > 0) {
                                $failedNames = collect($failedUsers)->pluck('name')->join(', ');
                                Notification::make()
                                    ->title("$failedCount user tidak dapat dihapus")
                                    ->body("User berikut masih memiliki data terkait: $failedNames")
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }

                            $livewire->dispatch('$refresh');
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Hapus User Terpilih')
                        ->modalDescription('User yang memiliki data terkait (nota dinas, cuti, gaji, dll) tidak akan dihapus untuk menjaga integritas data.')
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_deactivate_permanent')
                        ->label('Nonaktifkan Permanen')
                        ->icon('heroicon-o-archive-box-x-mark')
                        ->color('danger')
                        ->action(function ($records, $livewire): void {
                            if (! static::isSuperAdmin()) {
                                $records = $records->filter(function ($record) {
                                    return ! static::isTargetUserSuperAdmin($record);
                                });
                            }

                            $records = $records->filter(function ($record) {
                                return $record->status !== 'terminated';
                            });

                            $count = 0;
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => 'terminated',
                                    'expire_date' => now(),
                                    'last_working_date' => now()->toDateString(),
                                ]);
                                $count++;
                            }

                            Notification::make()
                                ->title("$count user berhasil dinonaktifkan permanen")
                                ->body('User telah dinonaktifkan dan tidak dapat mengakses sistem, namun data historis tetap tersimpan.')
                                ->success()
                                ->send();

                            $livewire->dispatch('$refresh');
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan User Permanen')
                        ->modalDescription('User akan dinonaktifkan permanen namun data historis tetap tersimpan. Ini lebih aman daripada menghapus user yang memiliki data terkait.')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->striped()
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->extremePaginationLinks()
            ->selectCurrentPageOnly()
            ->recordTitleAttribute('name')
            ->searchOnBlur()
            ->deferLoading();
    }
}
