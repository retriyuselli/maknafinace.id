<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->default('System'),

                TextColumn::make('log_name')
                    ->label('Modul')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created'  => 'success',
                        'updated'  => 'warning',
                        'deleted'  => 'danger',
                        'restored' => 'info',
                        default    => 'gray',
                    }),

                TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn (?string $state) => $state
                        ? class_basename($state)
                        : '-'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subject_id')
                    ->label('ID Record')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('perubahan')
                    ->label('Perubahan')
                    ->getStateUsing(function ($record): string {
                        // Ambil langsung dari model — attribute_changes adalah Collection di Spatie v5
                        $changes = $record->attribute_changes;

                        if (! $changes || $changes->isEmpty()) {
                            return '-';
                        }

                        $data = $changes->toArray();
                        $old  = $data['old'] ?? [];
                        $new  = $data['attributes'] ?? [];

                        // Format nilai berdasarkan nama field
                        $formatValue = function (string $key, $value): string {
                            if ($value === null || $value === '') {
                                return '(kosong)';
                            }

                            $k = strtolower($key);

                            // Field mata uang → Rp 42.150.000
                            if (preg_match('/price|nominal|total|salary|gaji|tunjangan|potongan|amount|cost|fee|upah|honor|pendapatan|pengeluaran|piutang|tagihan|bayar/', $k)) {
                                return 'Rp ' . number_format((float) $value, 0, ',', '.');
                            }

                            // Field tanggal/datetime → 05 Des 2026
                            if (preg_match('/date|tanggal/', $k) || str_ends_with($k, '_at')) {
                                try {
                                    return \Carbon\Carbon::parse($value)->locale('id')->isoFormat('D MMM YYYY');
                                } catch (\Throwable) {
                                    return (string) $value;
                                }
                            }

                            // Field boolean → Ya / Tidak
                            if (preg_match('/^is_/', $k)) {
                                return $value ? 'Ya' : 'Tidak';
                            }

                            return (string) $value;
                        };

                        // Event deleted → tampilkan nilai sebelum dihapus (skip yang kosong)
                        if (! empty($old) && empty($new)) {
                            $result = collect($old)
                                ->filter(fn ($v) => $v !== null && $v !== '')
                                ->map(fn ($v, $k) => "$k: " . $formatValue($k, $v))
                                ->implode(' | ');

                            return $result ?: '-';
                        }

                        // Event created → tampilkan nilai baru (skip yang kosong)
                        if (empty($old) && ! empty($new)) {
                            $result = collect($new)
                                ->filter(fn ($v) => $v !== null && $v !== '')
                                ->map(fn ($v, $k) => "$k: " . $formatValue($k, $v))
                                ->implode(' | ');

                            return $result ?: '-';
                        }

                        // Event updated → tampilkan perubahan old → new (skip yang tidak berubah & kosong)
                        $result = collect($new)
                            ->filter(fn ($v) => $v !== null && $v !== '')
                            ->map(fn ($v, $k) => isset($old[$k]) && (string) $old[$k] !== (string) $v
                                ? "$k: " . $formatValue($k, $old[$k]) . ' → ' . $formatValue($k, $v)
                                : "$k: " . $formatValue($k, $v)
                            )
                            ->implode(' | ');

                        return $result ?: '-';
                    })
                    ->wrap()
                    ->limit(300)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Modul')
                    ->options([
                        'order'             => 'Order',
                        'pembayaran'        => 'Pembayaran',
                        'expense'           => 'Biaya Wedding',
                        'expense_ops'       => 'Biaya Operasional',
                        'payroll'           => 'Payroll',
                        'piutang'           => 'Piutang',
                        'pembayaran_piutang' => 'Pembayaran Piutang',
                        'pendapatan_lain'   => 'Pendapatan Lain',
                        'pengeluaran_lain'  => 'Pengeluaran Lain',
                        'user'              => 'User',
                        'vendor'            => 'Vendor',
                        'leave_request'     => 'Cuti',
                        'nota_dinas'        => 'Nota Dinas',
                        'fixed_asset'       => 'Aset Tetap',
                        'bank_transaction'  => 'Transaksi Bank',
                        'default'           => 'Lainnya',
                    ]),

                SelectFilter::make('description')
                    ->label('Aksi')
                    ->options([
                        'created'  => 'Dibuat',
                        'updated'  => 'Diubah',
                        'deleted'  => 'Dihapus',
                        'restored' => 'Dipulihkan',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}
