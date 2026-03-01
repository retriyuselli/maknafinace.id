<?php

namespace App\Filament\Resources\NotaDinas\Schemas;

use App\Models\NotaDinas;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotaDinasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi ND')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('kategori_nd')
                                ->label('Kategori Nota Dinas')
                                ->options(NotaDinas::getKategoriOptions())
                                ->default('BIS')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    $tahun = date('Y');
                                    $nomorBaru = NotaDinas::generateNomorND($state, $tahun);
                                    $set('no_nd', $nomorBaru);
                                }),
                            DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->required()
                                ->default(now()),
                        ]),

                        TextInput::make('no_nd')
                            ->label('Nomor ND')
                            ->required()
                            ->readOnly()
                            ->unique(table: 'nota_dinas', column: 'no_nd', ignoreRecord: true)
                            ->placeholder('ND/BIS/001/2024')
                            ->maxLength(255)
                            ->default(function () {
                                return NotaDinas::generateNomorND('BIS');
                            }),
                    ]),

                Section::make('Pihak')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('pengirim_id')
                                ->label('Pengirim')
                                ->relationship('pengirim', 'name')
                                ->default(Auth::id())
                                ->disabled(function (): bool {
                                    $uid = Auth::id();
                                    if (! $uid) {
                                        return true;
                                    }

                                    $isSuperAdmin = DB::table('model_has_roles')
                                        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                                        ->where('model_has_roles.model_type', User::class)
                                        ->where('model_has_roles.model_id', $uid)
                                        ->where('roles.name', 'super_admin')
                                        ->exists();

                                    return ! $isSuperAdmin;
                                })
                                ->dehydrated()
                                ->required(),

                            Select::make('penerima_id')
                                ->label('Penerima')
                                ->relationship('penerima', 'name')
                                ->searchable()
                                ->preload(),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'diajukan' => 'Diajukan',
                                    'disetujui' => 'Disetujui',
                                    'ditolak' => 'Ditolak',
                                ])
                                ->default('diajukan')
                                ->required(),
                        ]),
                    ]),

                Section::make('Konten')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('sifat')
                                ->label('Sifat')
                                ->options([
                                    'Segera' => 'Segera',
                                    'Biasa' => 'Biasa',
                                    'Rahasia' => 'Rahasia',
                                ])
                                ->placeholder('Pilih sifat nota dinas')
                                ->required(),

                            TextInput::make('hal')
                                ->label('Hal')
                                ->placeholder('Perihal nota dinas')
                                ->maxLength(255),
                        ]),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->placeholder('Jika ada catatan tambahan, tuliskan disini...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Lampiran')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        FileUpload::make('nd_upload')
                            ->label('Upload File Nota Dinas')
                            ->directory('nota-dinas-uploads')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(2048)
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->columnSpanFull()
                            ->helperText('PERHATIAN : Setelah ND ditanda tangani, SEGERA masukkan persetujuannya kesini. Max 2MB.'),
                    ]),
            ]);
    }
}
