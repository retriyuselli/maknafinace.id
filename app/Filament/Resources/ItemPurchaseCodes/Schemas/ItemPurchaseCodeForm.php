<?php

namespace App\Filament\Resources\ItemPurchaseCodes\Schemas;

use App\Enums\ItemPurchaseCodeStatus;
use App\Models\ProspectApp;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ItemPurchaseCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Purchase Code')
                    ->description('Kode diterbitkan maknafinance.id setelah pembayaran lunas. Klien menempelkannya di aplikasi WOFINS.')
                    ->icon('heroicon-o-key')
                    ->schema([
                        Select::make('prospect_app_id')
                            ->label('Aplikasi Prospek')
                            ->relationship(
                                name: 'prospectApp',
                                titleAttribute: 'company_name',
                                modifyQueryUsing: fn ($query) => $query->orderBy('company_name'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (ProspectApp $record): string => $record->company_name.' — '.$record->full_name)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->disabledOn('edit')
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (! $state) {
                                    return;
                                }

                                $app = ProspectApp::query()->find($state);
                                if (! $app) {
                                    return;
                                }

                                $set('company_name', $app->company_name);
                                $set('package', $app->service);
                                $set('domain', $app->name_of_website);
                                $set('starts_at', $app->tgl_mulai?->toDateString());
                                $set('ends_at', $app->tgl_berakhir?->toDateString());
                            }),

                        Toggle::make('force_new')
                            ->label('Terbitkan kode baru (cabut yang lama)')
                            ->helperText('Hanya untuk perpanjang atau ganti kode. Kode lama tidak bisa dipakai lagi.')
                            ->default(false)
                            ->visibleOn('create')
                            ->dehydrated(),

                        TextInput::make('code')
                            ->label('Item Purchase Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->copyable()
                            ->placeholder('Otomatis UUID saat diterbitkan')
                            ->visibleOn('edit'),

                        TextInput::make('company_name')
                            ->label('Perusahaan')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('package')
                            ->label('Paket')
                            ->maxLength(255),

                        TextInput::make('domain')
                            ->label('Domain (opsional)')
                            ->helperText('Boleh diisi dulu. Domain pasti terikat saat klien aktivasi di aplikasinya.')
                            ->maxLength(255),

                        DatePicker::make('starts_at')
                            ->label('Tanggal mulai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d M Y'),

                        DatePicker::make('ends_at')
                            ->label('Tanggal selesai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->minDate(fn (Get $get) => $get('starts_at') ?: null),

                        Select::make('status')
                            ->label('Status')
                            ->options(ItemPurchaseCodeStatus::class)
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),

                        TextInput::make('activated_domain')
                            ->label('Domain teraktivasi')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),

                        Textarea::make('notes')
                            ->label('Catatan internal')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
