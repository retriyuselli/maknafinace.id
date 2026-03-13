<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn (Employee $record) => $record->name ? 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&color=FFFFFF&background=6366F1' : null),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (Employee $record): string => $record->position ?? ''),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->prefix('+62')
                    ->description(fn (Employee $record): string => $record->email ?? '')
                    ->searchable(),

                TextColumn::make('date_of_join')
                    ->label('Join Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('date_of_out')
                    ->label('End Date')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('Active'),

                IconColumn::make('active_status')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(function ($record) {
                        if (empty($record->date_of_join)) {
                            return false;
                        }

                        $joinDate = $record->date_of_join instanceof Carbon
                            ? $record->date_of_join
                            : Carbon::parse($record->date_of_join);

                        if ($joinDate->isFuture()) {
                            return false;
                        }

                        if (empty($record->date_of_out)) {
                            return true;
                        }

                        $outDate = $record->date_of_out instanceof Carbon
                            ? $record->date_of_out
                            : Carbon::parse($record->date_of_out);

                        return $outDate->isFuture();
                    })
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('salary')
                    ->label('Salary')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('no_rek')
                    ->label('Account Number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('position')
                    ->options([
                        'Account Manager' => 'Account Manager',
                        'Event Manager' => 'Event Manager',
                        'Crew' => 'Crew',
                        'Finance' => 'Finance',
                        'Founder' => 'Founder',
                        'Co Founder' => 'Co Founder',
                        'Direktur' => 'Direktur',
                        'Wakil Direktur' => 'Wakil Direktur',
                        'Other' => 'Other',
                    ])
                    ->multiple(),

                TernaryFilter::make('active')
                    ->label('Employment Status')
                    ->placeholder('All Employees')
                    ->trueLabel('Active Employees')
                    ->falseLabel('Former Employees')
                    ->queries(
                        true: fn (Builder $query) => $query->where(function ($query) {
                            $query->where('date_of_join', '<=', now())
                                ->where(function ($query) {
                                    $query->whereNull('date_of_out')
                                        ->orWhere('date_of_out', '>=', now());
                                });
                        }),
                        false: fn (Builder $query) => $query->where('date_of_out', '<', now()),
                        blank: fn (Builder $query) => $query
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date_of_join', 'desc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->emptyStateDescription('Silakan buat data pribadi baru untuk memulai.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Data Pribadi Baru')
                    ->url(fn () => route('filament.admin.resources.data-pribadis.create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
