<?php

namespace App\Filament\Resources\Sops\Tables;

use App\Models\Sop;
use App\Models\SopCategory;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SopsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul SOP')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->category?->color ?? 'gray'),

                TextColumn::make('version')
                    ->label('Versi')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('steps_count')
                    ->label('Langkah')
                    ->alignCenter()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('effective_date')
                    ->label('Tanggal Berlaku')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('review_date')
                    ->label('Review')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->needsReview() ? 'danger' : 'success')
                    ->badge(fn ($record) => $record->needsReview()),

                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(SopCategory::active()->pluck('name', 'id'))
                    ->multiple(),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),

                Filter::make('needs_review')
                    ->label('Perlu Review')
                    ->query(fn (Builder $query): Builder => $query->whereDate('review_date', '<', now())),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Preview')
                    ->icon('heroicon-o-eye'),

                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),

                Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Sop $record) {
                        $newSop = $record->replicate();
                        $newSop->title = $record->title.' (Copy)';
                        $newSop->version = '1.0';
                        $newSop->created_by = Auth::id();
                        $newSop->updated_by = Auth::id();
                        $newSop->save();

                        return redirect()->route('filament.admin.resources.sops.edit', $newSop);
                    })
                    ->requiresConfirmation(),

                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }
}
