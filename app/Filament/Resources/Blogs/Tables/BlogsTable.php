<?php

namespace App\Filament\Resources\Blogs\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Image')
                    ->square()
                    ->size(60),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->wrap(),

                BadgeColumn::make('category')
                    ->searchable()
                    ->colors([
                        'primary' => 'Featured',
                        'success' => 'Tutorial',
                        'warning' => 'Business',
                        'info' => 'Tips',
                        'danger' => 'Keuangan',
                    ]),

                TextColumn::make('author_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('read_time')
                    ->label('Read Time')
                    ->suffix(' min')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('M j, Y')
                    ->sortable(),

                TextColumn::make('views_count')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'Featured' => 'Featured',
                        'Tutorial' => 'Tutorial',
                        'Business' => 'Business',
                        'Tips' => 'Tips',
                        'Keuangan' => 'Keuangan',
                    ]),

                Filter::make('is_featured')
                    ->label('Featured Articles')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),

                Filter::make('is_published')
                    ->label('Published Articles')
                    ->query(fn (Builder $query): Builder => $query->where('is_published', true)),

                Filter::make('published_this_month')
                    ->label('Published This Month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('published_at', now()->month)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('toggle_featured')
                        ->label('Toggle Featured')
                        ->icon('heroicon-o-star')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_featured' => ! $record->is_featured]);
                            }
                        }),
                    BulkAction::make('toggle_published')
                        ->label('Toggle Published')
                        ->icon('heroicon-o-eye')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_published' => ! $record->is_published]);
                            }
                        }),
                ]),
            ]);
    }
}
