<?php

namespace App\Filament\Resources\Sops\Schemas;

use App\Models\Sop;
use App\Models\SopCategory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class SopForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Umum')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Judul SOP')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Masukkan judul SOP')
                                    ->columnSpanFull(),

                                Select::make('category_id')
                                    ->label('Kategori')
                                    ->options(SopCategory::active()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('version')
                                    ->label('Versi')
                                    ->default('1.0')
                                    ->required()
                                    ->maxLength(10),
                            ]),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->required()
                            ->rows(4)
                            ->placeholder('Masukkan deskripsi SOP'),

                        Textarea::make('keywords')
                            ->label('Kata Kunci')
                            ->placeholder('Masukkan kata kunci untuk pencarian (pisahkan dengan koma)')
                            ->helperText('Kata kunci akan membantu user menemukan SOP ini lebih mudah'),
                    ]),

                Section::make('Langkah-langkah')
                    ->schema([
                        Repeater::make('steps')
                            ->label('Langkah')
                            ->schema([
                                TextInput::make('step_number')
                                    ->label('No. Langkah')
                                    ->numeric()
                                    ->required()
                                    ->default(function ($livewire) {
                                        $steps = $livewire->data['steps'] ?? [];

                                        return count($steps) + 1;
                                    }),

                                TextInput::make('title')
                                    ->label('Judul Langkah')
                                    ->required()
                                    ->placeholder('Masukkan judul langkah'),

                                RichEditor::make('description')
                                    ->label('Deskripsi Langkah')
                                    ->required()
                                    ->placeholder('Jelaskan secara detail langkah ini'),

                                RichEditor::make('notes')
                                    ->label('Catatan')
                                    ->placeholder('Catatan tambahan untuk langkah ini (opsional)'),
                            ])
                            ->columns(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Langkah baru')
                            ->addActionLabel('Tambah Langkah')
                            ->defaultItems(1)
                            ->reorderable()
                            ->required(),
                    ]),

                Section::make('Dokumen Pendukung')
                    ->schema([
                        FileUpload::make('supporting_documents')
                            ->label('File Pendukung')
                            ->multiple()
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->directory('sop-documents')
                            ->visibility('private')
                            ->downloadable()
                            ->openable()
                            ->previewable(false)
                            ->helperText('Upload dokumen pendukung seperti PDF, gambar, atau dokumen Word'),
                    ]),

                Section::make('Pengaturan')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('effective_date')
                                    ->label('Tanggal Berlaku')
                                    ->default(now())
                                    ->required(),

                                DatePicker::make('review_date')
                                    ->label('Tanggal Review')
                                    ->helperText('Tanggal untuk review SOP ini'),

                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true)
                                    ->helperText('SOP yang tidak aktif tidak akan ditampilkan ke user'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('created_by')
                                    ->label('Dibuat Oleh')
                                    ->relationship('creator', 'name')
                                    ->default(Auth::id())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (?Sop $record) => $record !== null)
                                    ->dehydrated()
                                    ->helperText('User yang membuat SOP ini'),

                                Select::make('updated_by')
                                    ->label('Diperbarui Oleh')
                                    ->relationship('updater', 'name')
                                    ->default(Auth::id())
                                    ->searchable()
                                    ->preload()
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('User yang terakhir memperbarui SOP ini')
                                    ->visible(fn (?Sop $record) => $record !== null),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
