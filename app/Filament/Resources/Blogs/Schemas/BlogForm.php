<?php

namespace App\Filament\Resources\Blogs\Schemas;

use App\Models\Blog;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dasar')
                    ->description('Informasi utama artikel')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (string $context, $state, callable $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null)
                                    ->columnSpan(2),

                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Blog::class, 'slug', ignoreRecord: true)
                                    ->helperText('Versi judul yang ramah URL')
                                    ->columnSpan(2),
                            ]),

                        Textarea::make('excerpt')
                            ->required()
                            ->maxLength(500)
                            ->helperText('Deskripsi singkat artikel (maks 500 karakter)')
                            ->columnSpanFull(),
                    ]),

                Section::make('Konten')
                    ->description('Konten artikel dan media')
                    ->schema([
                        RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('featured_image')
                            ->url()
                            ->placeholder('https://example.com/image.jpg')
                            ->helperText('URL gambar utama (gunakan Unsplash atau sumber gambar lainnya)')
                            ->columnSpanFull(),
                    ]),

                Section::make('Kategorisasi')
                    ->description('Kategori dan tag')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('category')
                                    ->required()
                                    ->options([
                                        'Featured' => 'Featured',
                                        'Tutorial' => 'Tutorial',
                                        'Business' => 'Business',
                                        'Tips' => 'Tips',
                                        'Keuangan' => 'Keuangan',
                                    ])
                                    ->searchable()
                                    ->preload(),

                                TagsInput::make('tags')
                                    ->placeholder('Tambahkan tag')
                                    ->helperText('Tekan Enter untuk menambahkan setiap tag'),
                            ]),
                    ]),

                Section::make('Author Information')
                    ->description('Author details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('author_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->default(fn () => Auth::user()?->name ?? 'Admin WOFINS')
                                    ->helperText('Author name (defaults to current user)'),

                                Select::make('author_title')
                                    ->required()
                                    ->options([
                                        'Financial Expert' => 'Financial Expert',
                                        'Wedding Consultant' => 'Wedding Consultant',
                                        'Business Analyst' => 'Business Analyst',
                                        'Content Manager' => 'Content Manager',
                                        'Technical Expert' => 'Technical Expert',
                                        'Marketing Expert' => 'Marketing Expert',
                                        'SEO Specialist' => 'SEO Specialist',
                                        'Admin WOFINS' => 'Admin WOFINS',
                                    ])
                                    ->default(function () {
                                        $user = Auth::user();
                                        if (! $user) {
                                            return 'Financial Expert';
                                        }

                                        $email = strtolower($user->email);
                                        if (str_contains($email, 'admin') || str_contains($email, 'manager')) {
                                            return 'Admin WOFINS';
                                        } elseif (str_contains($email, 'tech') || str_contains($email, 'dev')) {
                                            return 'Technical Expert';
                                        } elseif (str_contains($email, 'marketing')) {
                                            return 'Marketing Expert';
                                        } else {
                                            return 'Financial Expert';
                                        }
                                    })
                                    ->searchable()
                                    ->helperText('Select appropriate author title'),
                            ]),
                    ]),

                Section::make('Publishing Settings')
                    ->description('Publication and visibility settings')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('read_time')
                                    ->required()
                                    ->numeric()
                                    ->default(5)
                                    ->suffix('minutes')
                                    ->minValue(1)
                                    ->maxValue(60),

                                Toggle::make('is_featured')
                                    ->helperText('Show in featured articles section'),

                                Toggle::make('is_published')
                                    ->helperText('Make article visible to public')
                                    ->default(true),
                            ]),

                        DateTimePicker::make('published_at')
                            ->default(now())
                            ->helperText('When should this article be published?'),
                    ]),

                Section::make('SEO Settings')
                    ->description('Search engine optimization')
                    ->collapsible()
                    ->schema([
                        TextInput::make('meta_title')
                            ->maxLength(255)
                            ->helperText('SEO title (leave empty to use article title)'),

                        Textarea::make('meta_description')
                            ->maxLength(160)
                            ->helperText('SEO description (max 160 characters)')
                            ->columnSpanFull(),

                        TextInput::make('views_count')
                            ->numeric()
                            ->default(0)
                            ->helperText('Article view count (for display purposes)'),
                    ]),
            ]);
    }
}
