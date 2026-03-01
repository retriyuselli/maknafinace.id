<?php

namespace App\Filament\Resources\CompanyLogos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyLogoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Perusahaan')
                    ->schema([
                        TextInput::make('company_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama Perusahaan'),
                        TextInput::make('website_url')
                            ->url()
                            ->maxLength(255)
                            ->label('URL Situs')
                            ->placeholder('https://example.com'),
                        FileUpload::make('logo_path')
                            ->label('Logo Perusahaan')
                            ->image()
                            ->directory('company-logos')
                            ->visibility('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp'])
                            ->maxSize(2048)
                            ->hint('Ukuran disarankan: 200x100px, Maks: 2MB'),
                        TextInput::make('alt_text')
                            ->maxLength(255)
                            ->label('Teks Alt')
                            ->hint('Teks alternatif untuk logo'),
                    ])->columns(1),

                Section::make('Pengaturan Tampilan')
                    ->schema([
                        Select::make('category')
                            ->required()
                            ->options([
                                'client' => 'Klien',
                                'partner' => 'Mitra',
                                'vendor' => 'Vendor',
                                'sponsor' => 'Sponsor',
                            ])
                            ->default('client'),
                        Select::make('partnership_type')
                            ->required()
                            ->options([
                                'free' => 'Gratis',
                                'premium' => 'Premium',
                                'enterprise' => 'Enterprise',
                            ])
                            ->default('free'),
                        TextInput::make('display_order')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->label('Urutan Tampilan')
                            ->hint('Angka lebih kecil tampil lebih dahulu'),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->label('Aktif'),
                    ])->columns(1),

                Section::make('Informasi Tambahan')
                    ->schema([
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('contact_email')
                            ->email()
                            ->maxLength(255)
                            ->label('Email Kontak'),
                    ]),
            ]);
    }
}
