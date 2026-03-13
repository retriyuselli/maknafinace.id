<?php

namespace App\Filament\Resources\CompanyLogos\Tables;

use App\Filament\Resources\CompanyLogos\CompanyLogoResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompanyLogosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->size(60)
                    ->circular(),
                TextColumn::make('company_name')
                    ->searchable()
                    ->sortable()
                    ->label('Company Name'),
                TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'client' => 'primary',
                        'partner' => 'success',
                        'vendor' => 'warning',
                        'sponsor' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('partnership_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'secondary',
                        'premium' => 'warning',
                        'enterprise' => 'success',
                        default => 'gray',
                    })
                    ->label('Type'),
                TextColumn::make('display_order')
                    ->numeric()
                    ->sortable()
                    ->label('Order'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('website_url')
                    ->searchable()
                    ->limit(30)
                    ->label('Website')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('contact_email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Contact'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'client' => 'Client',
                        'partner' => 'Partner',
                        'vendor' => 'Vendor',
                        'sponsor' => 'Sponsor',
                    ]),
                SelectFilter::make('partnership_type')
                    ->options([
                        'free' => 'Free',
                        'premium' => 'Premium',
                        'enterprise' => 'Enterprise',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order')
            ->emptyStateDescription('Silakan buat logo perusahaan baru untuk memulai.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Logo Perusahaan Baru')
                    ->url(fn (): string => CompanyLogoResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
