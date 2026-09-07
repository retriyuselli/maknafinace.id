<?php

namespace App\Filament\Resources\DocumentCategories\Tables;

use App\Enums\DocumentCategoryType;
use App\Filament\Resources\DocumentCategories\DocumentCategoryResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DocumentCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_approval_required')
                    ->boolean()
                    ->label('Approval'),
                TextColumn::make('parent.name')
                    ->label('Kategori Induk')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('Status')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Aktif')
                    ->badge()
                    ->color(fn ($state) => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state ? 'Dihapus' : 'Aktif'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Filter Status')
                    ->placeholder('Semua Data')
                    ->trueLabel('Hanya yang Dihapus')
                    ->falseLabel('Tanpa yang Dihapus'),
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(DocumentCategoryType::class),
                TernaryFilter::make('is_approval_required')
                    ->label('Perlu Approval')
                    ->placeholder('Semua')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),
            ])
            ->recordActions([
                EditAction::make(),
                RestoreAction::make()
                    ->successNotificationTitle('Kategori dokumen berhasil dipulihkan'),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Kategori Dokumen')
                    ->modalDescription('Data akan dipindahkan ke trash dan dapat dipulihkan.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->successNotificationTitle('Kategori dokumen berhasil dihapus'),
                ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen Kategori Dokumen')
                    ->modalDescription('Data tidak dapat dipulihkan!')
                    ->modalSubmitActionLabel('Ya, Hapus Permanen')
                    ->successNotificationTitle('Kategori dokumen berhasil dihapus permanen'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make()
                        ->successNotificationTitle('Kategori terpilih berhasil dipulihkan'),
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Kategori Terpilih')
                        ->modalDescription('Data akan dipindahkan ke trash dan dapat dipulihkan.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->successNotificationTitle('Kategori terpilih berhasil dihapus'),
                    ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Permanen Kategori Terpilih')
                        ->modalDescription('Data tidak dapat dipulihkan!')
                        ->modalSubmitActionLabel('Ya, Hapus Permanen Semua')
                        ->successNotificationTitle('Kategori terpilih berhasil dihapus permanen'),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateDescription('Silakan buat kategori dokumen baru untuk memulai.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Kategori Baru')
                    ->url(fn (): string => DocumentCategoryResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
