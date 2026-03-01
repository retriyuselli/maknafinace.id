<?php

namespace App\Filament\Resources\NotaDinasDetails\Tables;

use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\NotaDinasDetail;
use App\Models\PengeluaranLain;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class NotaDinasDetailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_status')
                    ->label('Status di Expense')
                    ->state(function (?NotaDinasDetail $record): string {
                        if (! $record) {
                            return '-';
                        }

                        $statuses = [];

                        $expense = Expense::where('nota_dinas_detail_id', $record->id)->first();
                        if ($expense) {
                            $statuses[] = 'Expense (Wedding)';
                        }

                        $expenseOps = ExpenseOps::where('nota_dinas_detail_id', $record->id)->first();
                        if ($expenseOps) {
                            $statuses[] = 'Expense Ops';
                        }

                        $pengeluaranLain = PengeluaranLain::where('nota_dinas_detail_id', $record->id)->first();
                        if ($pengeluaranLain) {
                            $statuses[] = 'Pengeluaran Lain';
                        }

                        if (empty($statuses)) {
                            return 'Belum Masuk';
                        }

                        return implode(', ', $statuses);
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state === 'Belum Masuk' => 'gray',
                        str_contains($state, 'Expense (Wedding)') => 'success',
                        str_contains($state, 'Expense Ops') => 'info',
                        str_contains($state, 'Pengeluaran Lain') => 'warning',
                        default => 'primary',
                    })
                    ->icon(fn (string $state): string => match (true) {
                        $state === 'Belum Masuk' => 'heroicon-o-clock',
                        str_contains($state, 'Expense') => 'heroicon-o-check-circle',
                        str_contains($state, 'Pengeluaran') => 'heroicon-o-check-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->searchable(false)
                    ->sortable(false),
                TextColumn::make('notaDinas.no_nd')
                    ->label('No. ND')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->limit(25),
                TextColumn::make('keperluan')
                    ->label('Keperluan')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('jenis_pengeluaran')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'wedding' => 'success',
                        'operasional' => 'info',
                        'lain-lain' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'wedding' => 'Wedding',
                        'operasional' => 'Operasional',
                        'lain-lain' => 'Lain-lain',
                        default => $state,
                    }),
                TextColumn::make('payment_stage')
                    ->label('Tahap')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DP' => 'warning',
                        'Payment 1' => 'info',
                        'Payment 2' => 'info',
                        'Payment 3' => 'info',
                        'Final Payment' => 'success',
                        'Additional' => 'gray',
                        default => 'primary',
                    })
                    ->placeholder('-'),
                TextColumn::make('event_display')
                    ->label('Event')
                    ->state(function (?NotaDinasDetail $record): string {
                        if (! $record) {
                            return '-';
                        }
                        if ($record->jenis_pengeluaran === 'wedding') {
                            return $record->order?->name ?? '-';
                        }

                        return $record->event ?? '-';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where('event', 'like', "%{$search}%")
                                ->orWhereHas('order', function (Builder $subQuery) use ($search) {
                                    $subQuery->where('name', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->limit(20)
                    ->placeholder('-'),
                TextColumn::make('jumlah_transfer')
                    ->label('Jumlah Transfer')
                    ->prefix('Rp. ')
                    ->sortable()
                    ->numeric()
                    ->alignEnd()
                    ->summarize([
                        Sum::make()
                            ->money('Rp.')
                            ->label('Total Keseluruhan'),
                        Average::make()
                            ->money('Rp.')
                            ->label('Rata-rata'),
                    ]),
                TextColumn::make('invoice_number')
                    ->label('No. Invoice')
                    ->searchable()
                    ->placeholder('-')
                    ->copyable(),
                IconColumn::make('invoice_file')
                    ->label('Invoice')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('status_invoice')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'belum_dibayar' => 'danger',
                        'menunggu' => 'warning',
                        'sudah_dibayar' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'belum_dibayar' => 'heroicon-o-clock',
                        'menunggu' => 'heroicon-o-exclamation-triangle',
                        'sudah_dibayar' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),
                TextColumn::make('notaDinas.status')
                    ->label('Status ND')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'diajukan' => 'warning',
                        'disetujui' => 'success',
                        'dibayar' => 'primary',
                        'ditolak' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('nota_dinas_id')
                    ->label('No. ND')
                    ->relationship('notaDinas', 'no_nd')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('jenis_pengeluaran')
                    ->label('Jenis Pengeluaran')
                    ->options([
                        'wedding' => 'Wedding',
                        'operasional' => 'Operasional',
                        'lain-lain' => 'Lain-lain',
                    ]),
                SelectFilter::make('status_invoice')
                    ->label('Status Invoice')
                    ->options([
                        'belum_dibayar' => 'Belum Dibayar',
                        'menunggu' => 'Menunggu Pembayaran',
                        'sudah_dibayar' => 'Sudah Dibayar',
                    ]),
                SelectFilter::make('nota_dinas_status')
                    ->label('Status ND')
                    ->relationship('notaDinas', 'status')
                    ->options([
                        'draft' => 'Draft',
                        'diajukan' => 'Diajukan',
                        'disetujui' => 'Disetujui',
                        'dibayar' => 'Dibayar',
                        'ditolak' => 'Ditolak',
                    ]),
                SelectFilter::make('vendor')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\Filter::make('created_date')
                    ->label('Tanggal Dibuat')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Dari '.\Carbon\Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Sampai '.\Carbon\Carbon::parse($data['until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
                SelectFilter::make('expense_status')
                    ->label('Status di Expense')
                    ->options([
                        'belum_masuk' => 'Belum Masuk',
                        'sudah_masuk' => 'Sudah Masuk',
                        'expense_wedding' => 'Expense (Wedding)',
                        'expense_ops' => 'Expense Ops',
                        'pengeluaran_lain' => 'Pengeluaran Lain',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value']) || $data['value'] === null) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'belum_masuk' => $query->whereDoesntHave('expenses')
                                ->whereDoesntHave('expenseOps')
                                ->whereDoesntHave('pengeluaranLains'),
                            'sudah_masuk' => $query->where(function (Builder $q) {
                                $q->whereHas('expenses')
                                    ->orWhereHas('expenseOps')
                                    ->orWhereHas('pengeluaranLains');
                            }),
                            'expense_wedding' => $query->whereHas('expenses'),
                            'expense_ops' => $query->whereHas('expenseOps'),
                            'pengeluaran_lain' => $query->whereHas('pengeluaranLains'),
                            default => $query,
                        };
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('mark_paid')
                    ->label('Tandai Dibayar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (NotaDinasDetail $record): bool => $record->status_invoice !== 'sudah_dibayar' &&
                        $record->notaDinas->status === 'disetujui'
                    )
                    ->requiresConfirmation()
                    ->action(function (NotaDinasDetail $record): void {
                        $record->update(['status_invoice' => 'sudah_dibayar']);

                        $allPaid = $record->notaDinas->details()
                            ->where('status_invoice', '!=', 'sudah_dibayar')
                            ->count() === 0;

                        if ($allPaid) {
                            $record->notaDinas->update(['status' => 'dibayar']);
                        }
                    }),
                Action::make('download_invoice')
                    ->label('Download Invoice')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn (NotaDinasDetail $record): bool => ! empty($record->invoice_file))
                    ->url(fn (NotaDinasDetail $record): string => Storage::url($record->invoice_file))
                    ->openUrlInNewTab(),
                Action::make('view_expense_records')
                    ->label('Lihat Record Expense')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(function (NotaDinasDetail $record): bool {
                        $hasExpense = Expense::where('nota_dinas_detail_id', $record->id)->exists();
                        $hasExpenseOps = ExpenseOps::where('nota_dinas_detail_id', $record->id)->exists();
                        $hasPengeluaranLain = PengeluaranLain::where('nota_dinas_detail_id', $record->id)->exists();

                        return $hasExpense || $hasExpenseOps || $hasPengeluaranLain;
                    })
                    ->modalHeading(fn (NotaDinasDetail $record): string => 'Record Expense - '.$record->keperluan)
                    ->modalDescription('Detail record yang terkait dengan Nota Dinas Detail ini')
                    ->modalContent(function (NotaDinasDetail $record): HtmlString {
                        $content = '<div class="space-y-4">';

                        $expenses = Expense::where('nota_dinas_detail_id', $record->id)->get();
                        if ($expenses->count() > 0) {
                            $content .= '<div class="bg-green-50 border border-green-200 rounded-lg p-4">';
                            $content .= '<h3 class="font-semibold text-green-800 mb-2">💒 Expense (Wedding) - '.$expenses->count().' record</h3>';
                            foreach ($expenses as $expense) {
                                $content .= '<div class="border-l-4 border-green-400 pl-3 py-2 bg-white rounded mb-2">';
                                $content .= '<p class="text-sm font-medium">Vendor: '.($expense->vendor?->name ?? 'N/A').'</p>';
                                $content .= '<p class="text-sm text-gray-600">Order: '.($expense->order?->name ?? 'N/A').'</p>';
                                $content .= '<p class="text-sm text-gray-600">Amount: Rp '.number_format($expense->amount, 0, ',', '.').'</p>';
                                $content .= '<p class="text-xs text-gray-500">Created: '.$expense->created_at->format('d-m-Y H:i').'</p>';
                                $content .= '</div>';
                            }
                            $content .= '</div>';
                        }

                        $expenseOps = ExpenseOps::where('nota_dinas_detail_id', $record->id)->get();
                        if ($expenseOps->count() > 0) {
                            $content .= '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">';
                            $content .= '<h3 class="font-semibold text-blue-800 mb-2">🏢 Expense Ops - '.$expenseOps->count().' record</h3>';
                            foreach ($expenseOps as $ops) {
                                $content .= '<div class="border-l-4 border-blue-400 pl-3 py-2 bg-white rounded mb-2">';
                                $content .= '<p class="text-sm font-medium">Vendor: '.($ops->vendor?->name ?? 'N/A').'</p>';
                                $content .= '<p class="text-sm text-gray-600">Description: '.($ops->description ?? 'N/A').'</p>';
                                $content .= '<p class="text-sm text-gray-600">Amount: Rp '.number_format($ops->amount, 0, ',', '.').'</p>';
                                $content .= '<p class="text-xs text-gray-500">Created: '.$ops->created_at->format('d-m-Y H:i').'</p>';
                                $content .= '</div>';
                            }
                            $content .= '</div>';
                        }

                        $pengeluaranLains = PengeluaranLain::where('nota_dinas_detail_id', $record->id)->get();
                        if ($pengeluaranLains->count() > 0) {
                            $content .= '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">';
                            $content .= '<h3 class="font-semibold text-yellow-800 mb-2">📋 Pengeluaran Lain - '.$pengeluaranLains->count().' record</h3>';
                            foreach ($pengeluaranLains as $lain) {
                                $content .= '<div class="border-l-4 border-yellow-400 pl-3 py-2 bg-white rounded mb-2">';
                                $content .= '<p class="text-sm font-medium">Description: '.($lain->description ?? 'N/A').'</p>';
                                $content .= '<p class="text-sm text-gray-600">Category: '.($lain->category ?? 'N/A').'</p>';
                                $content .= '<p class="text-sm text-gray-600">Amount: Rp '.number_format($lain->amount, 0, ',', '.').'</p>';
                                $content .= '<p class="text-xs text-gray-500">Created: '.$lain->created_at->format('d-m-Y H:i').'</p>';
                                $content .= '</div>';
                            }
                            $content .= '</div>';
                        }

                        $content .= '</div>';

                        return new HtmlString($content);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->visible(function (NotaDinasDetail $record): bool {
                            /** @var User $user */
                            $user = Auth::user();
                            $hasPermission = ($user ? $user->hasRole('super_admin') : false) && ! $record->trashed();
                            $hasNoExpenseRelations = ! $record->expenses()->exists() &&
                                                   ! $record->expenseOps()->exists() &&
                                                   ! $record->pengeluaranLains()->exists();

                            return $hasPermission && $hasNoExpenseRelations;
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Detail Nota Dinas')
                        ->modalDescription('Apakah Anda yakin ingin menghapus detail nota dinas ini? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, hapus')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->modalIconColor('danger'),
                    Action::make('cannot_delete')
                        ->label('Tidak Dapat Dihapus')
                        ->icon('heroicon-o-shield-exclamation')
                        ->color('warning')
                        ->visible(function (NotaDinasDetail $record): bool {
                            /** @var User $user */
                            $user = Auth::user();
                            $hasPermission = ($user ? $user->hasRole('super_admin') : false) && ! $record->trashed();
                            $hasExpenseRelations = $record->expenses()->exists() ||
                                                 $record->expenseOps()->exists() ||
                                                 $record->pengeluaranLains()->exists();

                            return $hasPermission && $hasExpenseRelations;
                        })
                        ->modalHeading(fn (NotaDinasDetail $record): string => 'Detail Nota Dinas Tidak Dapat Dihapus - '.$record->keperluan)
                        ->modalDescription('Detail nota dinas ini memiliki relasi dengan expense dan tidak dapat dihapus.')
                        ->modalContent(function (NotaDinasDetail $record): HtmlString {
                            $content = '<div class="space-y-4">';

                            $content .= '<div class="bg-red-50 border border-red-200 rounded-lg p-4">';
                            $content .= '<h3 class="font-semibold text-red-800 mb-2">🚫 Penghapusan Diblokir</h3>';
                            $content .= '<p class="text-sm text-red-700 mb-3">Detail nota dinas ini tidak dapat dihapus karena memiliki relasi dengan expense records:</p>';

                            $reasons = [];

                            if ($record->expenses()->exists()) {
                                $count = $record->expenses()->count();
                                $reasons[] = "• {$count} record di Expense (Wedding)";
                            }

                            if ($record->expenseOps()->exists()) {
                                $count = $record->expenseOps()->count();
                                $reasons[] = "• {$count} record di Expense Ops";
                            }

                            if ($record->pengeluaranLains()->exists()) {
                                $count = $record->pengeluaranLains()->count();
                                $reasons[] = "• {$count} record di Pengeluaran Lain";
                            }

                            $content .= '<div class="bg-white rounded p-3">';
                            $content .= implode('<br>', $reasons);
                            $content .= '</div>';
                            $content .= '</div>';

                            $content .= '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">';
                            $content .= '<h3 class="font-semibold text-blue-800 mb-2">💡 Solusi</h3>';
                            $content .= '<p class="text-sm text-blue-700">Untuk menghapus detail nota dinas ini, hapus terlebih dahulu semua expense records yang terkait di:</p>';
                            $content .= '<ul class="text-sm text-blue-700 mt-2 ml-4">';
                            $content .= '<li>• Menu Expense (Wedding)</li>';
                            $content .= '<li>• Menu Expense Ops</li>';
                            $content .= '<li>• Menu Pengeluaran Lain</li>';
                            $content .= '</ul>';
                            $content .= '</div>';

                            $content .= '</div>';

                            return new HtmlString($content);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
                RestoreAction::make()
                    ->visible(function (NotaDinasDetail $record): bool {
                        /** @var User $user */
                        $user = Auth::user();

                        return ($user ? $user->hasRole('super_admin') : false) && $record->trashed();
                    }),
                ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen Detail Nota Dinas')
                    ->modalDescription('Apakah Anda yakin ingin MENGHAPUS PERMANEN detail nota dinas ini? Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Ya, hapus permanen')
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->modalIconColor('danger')
                    ->visible(function (NotaDinasDetail $record): bool {
                        /** @var User $user */
                        $user = Auth::user();

                        return ($user ? $user->hasRole('super_admin') : false) && $record->trashed();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(function (): bool {
                            /** @var User $user */
                            $user = Auth::user();

                            return $user ? $user->hasRole('super_admin') : false;
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Detail Nota Dinas Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus detail nota dinas yang dipilih? Hanya record tanpa relasi expense yang akan dihapus.')
                        ->modalSubmitActionLabel('Ya, hapus yang dapat dihapus')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->modalIconColor('danger')
                        ->before(function ($livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            $protectedRecords = [];
                            $deletableCount = 0;

                            foreach ($selectedRecords as $record) {
                                $hasExpenseRelations = $record->expenses()->exists() ||
                                                     $record->expenseOps()->exists() ||
                                                     $record->pengeluaranLains()->exists();

                                if ($hasExpenseRelations) {
                                    $expenseTypes = [];
                                    if ($record->expenses()->exists()) {
                                        $expenseTypes[] = 'Expense';
                                    }
                                    if ($record->expenseOps()->exists()) {
                                        $expenseTypes[] = 'Expense Ops';
                                    }
                                    if ($record->pengeluaranLains()->exists()) {
                                        $expenseTypes[] = 'Pengeluaran Lain';
                                    }

                                    $protectedRecords[] = $record->keperluan.' (ada di: '.implode(', ', $expenseTypes).')';
                                } else {
                                    $deletableCount++;
                                }
                            }

                            if (! empty($protectedRecords)) {
                                $message = "Detail nota dinas berikut tidak dapat dihapus karena memiliki relasi expense:\n\n";
                                $message .= '• '.implode("\n• ", $protectedRecords);

                                if ($deletableCount > 0) {
                                    $message .= "\n\n{$deletableCount} record tanpa relasi expense akan dihapus.";
                                }

                                Notification::make()
                                    ->warning()
                                    ->title('Beberapa Record Dilindungi')
                                    ->body($message)
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->action(function ($livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            $deletedCount = 0;
                            $protectedCount = 0;

                            foreach ($selectedRecords as $record) {
                                $hasExpenseRelations = $record->expenses()->exists() ||
                                                     $record->expenseOps()->exists() ||
                                                     $record->pengeluaranLains()->exists();

                                if (! $hasExpenseRelations) {
                                    try {
                                        $record->delete();
                                        $deletedCount++;
                                    } catch (Exception $e) {
                                    }
                                } else {
                                    $protectedCount++;
                                }
                            }

                            if ($deletedCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Penghapusan Massal Selesai')
                                    ->body("{$deletedCount} detail nota dinas berhasil dihapus.".
                                           ($protectedCount > 0 ? " {$protectedCount} record dilindungi dari penghapusan." : ''))
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Tidak Ada Record Dihapus')
                                    ->body('Semua record yang dipilih memiliki relasi expense dan tidak dapat dihapus.')
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    RestoreBulkAction::make()
                        ->visible(function (): bool {
                            /** @var User $user */
                            $user = Auth::user();

                            return $user ? $user->hasRole('super_admin') : false;
                        })
                        ->deselectRecordsAfterCompletion(),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Permanen Detail Nota Dinas Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin MENGHAPUS PERMANEN detail nota dinas yang dipilih? Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data terkait.')
                        ->modalSubmitActionLabel('Ya, hapus permanen')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->modalIconColor('danger')
                        ->deselectRecordsAfterCompletion()
                        ->visible(function (): bool {
                            /** @var User $user */
                            $user = Auth::user();

                            return $user ? $user->hasRole('super_admin') : false;
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->deferLoading()
            ->striped()
            ->extremePaginationLinks();
    }
}
