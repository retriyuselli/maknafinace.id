<?php

namespace App\Filament\Resources\PengajuanLemburs\Tables;

use App\Filament\Resources\PengajuanLemburs\PengajuanLemburResource;
use App\Models\PengajuanLembur;
use App\Models\User;
use App\Services\PengajuanLemburService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PengajuanLembursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('mulai_pada')
                    ->label('Mulai')
                    ->dateTime('d/m H:i'),
                TextColumn::make('selesai_pada')
                    ->label('Selesai')
                    ->dateTime('d/m H:i'),
                TextColumn::make('menit')
                    ->label('Durasi')
                    ->formatStateUsing(fn (PengajuanLembur $record): string => $record->labelDurasi())
                    ->alignCenter(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PengajuanLembur::STATUS_DISETUJUI => 'success',
                        PengajuanLembur::STATUS_DITOLAK => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        PengajuanLembur::STATUS_DISETUJUI => 'Disetujui',
                        PengajuanLembur::STATUS_DITOLAK => 'Ditolak',
                        default => 'Menunggu',
                    }),
                TextColumn::make('alasan')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('disetujuiOleh.name')
                    ->label('Ditinjau Oleh')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        PengajuanLembur::STATUS_MENUNGGU => 'Menunggu',
                        PengajuanLembur::STATUS_DISETUJUI => 'Disetujui',
                        PengajuanLembur::STATUS_DITOLAK => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (PengajuanLembur $record): bool => $record->sedangMenunggu())
                    ->form([
                        Textarea::make('catatan')
                            ->label('Catatan (opsional)')
                            ->rows(2),
                    ])
                    ->requiresConfirmation()
                    ->action(function (PengajuanLembur $record, array $data, PengajuanLemburService $service): void {
                        /** @var User $peninjau */
                        $peninjau = Auth::user();
                        $service->setujui($record, $peninjau, $data['catatan'] ?? null);

                        Notification::make()
                            ->title('Lembur disetujui')
                            ->success()
                            ->send();
                    }),
                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (PengajuanLembur $record): bool => $record->sedangMenunggu())
                    ->form([
                        Textarea::make('catatan')
                            ->label('Alasan penolakan')
                            ->required()
                            ->rows(2),
                    ])
                    ->requiresConfirmation()
                    ->action(function (PengajuanLembur $record, array $data, PengajuanLemburService $service): void {
                        /** @var User $peninjau */
                        $peninjau = Auth::user();
                        $service->tolak($record, $peninjau, $data['catatan'] ?? null);

                        Notification::make()
                            ->title('Lembur ditolak')
                            ->warning()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Pengajuan')
                    ->url(fn (): string => PengajuanLemburResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
