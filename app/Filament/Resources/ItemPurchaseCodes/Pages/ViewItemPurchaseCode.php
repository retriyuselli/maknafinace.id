<?php

namespace App\Filament\Resources\ItemPurchaseCodes\Pages;

use App\Filament\Resources\ItemPurchaseCodes\ItemPurchaseCodeResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewItemPurchaseCode extends ViewRecord
{
    protected static string $resource = ItemPurchaseCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Item Purchase Code')
                    ->schema([
                        TextEntry::make('code')
                            ->label('Item Purchase Code')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('company_name')
                            ->label('Perusahaan'),
                        TextEntry::make('prospectApp.full_name')
                            ->label('Kontak'),
                        TextEntry::make('package')
                            ->label('Paket')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->getStateUsing(fn ($record) => $record->resolvedStatus()),
                        TextEntry::make('starts_at')
                            ->label('Mulai')
                            ->date('d M Y'),
                        TextEntry::make('ends_at')
                            ->label('Selesai')
                            ->date('d M Y'),
                        TextEntry::make('domain')
                            ->label('Domain cadangan')
                            ->placeholder('-'),
                        TextEntry::make('activated_domain')
                            ->label('Domain teraktivasi')
                            ->placeholder('-'),
                        TextEntry::make('activated_at')
                            ->label('Waktu aktivasi')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('last_verified_at')
                            ->label('Cek terakhir')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
