<?php

namespace App\Filament\Resources\KoreksiAbsensis\Tables;

use App\Filament\Resources\KoreksiAbsensis\KoreksiAbsensiResource;
use App\Models\KoreksiAbsensi;
use App\Models\User;
use App\Services\KoreksiAbsensiService;
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

class KoreksiAbsensisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('absensi.tanggal')
                    ->label('Tanggal Absensi')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('jam_masuk_diajukan')
                    ->label('Masuk Diajukan')
                    ->dateTime('d/m H:i')
                    ->placeholder('-'),
                TextColumn::make('jam_pulang_diajukan')
                    ->label('Pulang Diajukan')
                    ->dateTime('d/m H:i')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        KoreksiAbsensi::STATUS_DISETUJUI => 'success',
                        KoreksiAbsensi::STATUS_DITOLAK => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        KoreksiAbsensi::STATUS_DISETUJUI => 'Disetujui',
                        KoreksiAbsensi::STATUS_DITOLAK => 'Ditolak',
                        default => 'Menunggu',
                    }),
                TextColumn::make('alasan')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('ditinjauOleh.name')
                    ->label('Ditinjau Oleh')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        KoreksiAbsensi::STATUS_MENUNGGU => 'Menunggu',
                        KoreksiAbsensi::STATUS_DISETUJUI => 'Disetujui',
                        KoreksiAbsensi::STATUS_DITOLAK => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (KoreksiAbsensi $record): bool => $record->sedangMenunggu())
                    ->form([
                        Textarea::make('catatan_peninjau')
                            ->label('Catatan (opsional)')
                            ->rows(2),
                    ])
                    ->requiresConfirmation()
                    ->action(function (KoreksiAbsensi $record, array $data, KoreksiAbsensiService $service): void {
                        /** @var User $peninjau */
                        $peninjau = Auth::user();
                        $service->setujui($record, $peninjau, $data['catatan_peninjau'] ?? null);

                        Notification::make()
                            ->title('Koreksi disetujui')
                            ->success()
                            ->send();
                    }),
                Action::make('tolak')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (KoreksiAbsensi $record): bool => $record->sedangMenunggu())
                    ->form([
                        Textarea::make('catatan_peninjau')
                            ->label('Alasan penolakan')
                            ->required()
                            ->rows(2),
                    ])
                    ->requiresConfirmation()
                    ->action(function (KoreksiAbsensi $record, array $data, KoreksiAbsensiService $service): void {
                        /** @var User $peninjau */
                        $peninjau = Auth::user();
                        $service->tolak($record, $peninjau, $data['catatan_peninjau'] ?? null);

                        Notification::make()
                            ->title('Koreksi ditolak')
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
                    ->label('Buat Koreksi')
                    ->url(fn (): string => KoreksiAbsensiResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
