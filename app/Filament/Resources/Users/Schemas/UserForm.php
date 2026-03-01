<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Informasi Pengguna')
                    ->tabs([
                        Tab::make('Informasi Dasar')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Detail Akun')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Nama Lengkap')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->autocomplete('name')
                                                    ->placeholder('Masukkan nama lengkap'),

                                                TextInput::make('email')
                                                    ->label('Email')
                                                    ->email()
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(255)
                                                    ->autocomplete('email')
                                                    ->placeholder('user@example.com'),
                                            ]),
                                    ]),

                                Section::make('Peran & Status')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('roles')
                                                    ->label('Role')
                                                    ->relationship('roles', 'name')
                                                    ->multiple()
                                                    ->preload()
                                                    ->searchable()
                                                    ->placeholder('Pilih Role')
                                                    ->maxItems(5)
                                                    ->helperText('Pilih satu atau lebih role untuk pengguna (maksimal 5 role)')
                                                    ->createOptionForm([
                                                        TextInput::make('name')
                                                            ->label('Nama Role')
                                                            ->required()
                                                            ->unique('roles', 'name'),
                                                    ])
                                                    ->createOptionUsing(function (array $data) {
                                                        return Role::create($data)->getKey();
                                                    }),

                                                Select::make('statuses')
                                                    ->label('Status Jabatan')
                                                    ->relationship('statuses', 'status_name')
                                                    ->multiple()
                                                    ->preload()
                                                    ->required()
                                                    ->searchable()
                                                    ->native(false)
                                                    ->selectablePlaceholder(false)
                                                    ->placeholder('Pilih Status Jabatan')
                                                    ->helperText('Status jabatan pengguna (Admin, Finance, HRD, dll). Bisa pilih lebih dari satu.'),
                                            ]),
                                    ]),

                                Section::make('Keamanan')
                                    ->schema([
                                        TextInput::make('password')
                                            ->label('Password')
                                            ->password()
                                            ->required(fn (string $operation): bool => $operation === 'create')
                                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->minLength(8)
                                            ->maxLength(255)
                                            ->helperText('Minimal 8 karakter. Kosongkan jika tidak ingin mengubah password.')
                                            ->columnSpan(2),
                                    ]),
                            ]),

                        Tab::make('Personal & Kepegawaian')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Section::make('Informasi Personal')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('phone_number')
                                                    ->label('Nomor Telepon')
                                                    ->tel()
                                                    ->maxLength(255)
                                                    ->placeholder('08xx-xxxx-xxxx'),

                                                DatePicker::make('date_of_birth')
                                                    ->label('Tanggal Lahir')
                                                    ->displayFormat('d/m/Y')
                                                    ->maxDate(now()->subYears(17)),

                                                Select::make('gender')
                                                    ->label('Jenis Kelamin')
                                                    ->options([
                                                        'male' => 'Laki-laki',
                                                        'female' => 'Perempuan',
                                                    ])
                                                    ->placeholder('Pilih jenis kelamin'),

                                                Select::make('department')
                                                    ->label('Departemen')
                                                    ->options([
                                                        'bisnis' => 'Bisnis',
                                                        'operasional' => 'Operasional',
                                                    ])
                                                    ->default('operasional')
                                                    ->required(),
                                            ]),

                                        Textarea::make('address')
                                            ->label('Alamat')
                                            ->maxLength(500)
                                            ->rows(3)
                                            ->placeholder('Alamat lengkap')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Employment Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                DatePicker::make('hire_date')
                                                    ->label('Tanggal Mulai Kerja')
                                                    ->displayFormat('d/m/Y')
                                                    ->maxDate(now()),

                                                DatePicker::make('last_working_date')
                                                    ->label('Tanggal Berakhir Kerja')
                                                    ->displayFormat('d/m/Y')
                                                    ->helperText('Kosongkan jika masih aktif bekerja'),
                                            ]),
                                    ]),

                                Section::make('Account Settings')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                FileUpload::make('avatar_url')
                                                    ->label('Foto Profil')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('avatars')
                                                    ->visibility('public')
                                                    ->openable()
                                                    ->downloadable()
                                                    ->imageCropAspectRatio('1:1')
                                                    ->imageResizeTargetWidth('300')
                                                    ->imageResizeTargetHeight('300')
                                                    ->circleCropper()
                                                    ->maxSize(2048)
                                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                                    ->helperText('Upload foto profil (maksimal 2MB, format: JPG, PNG, WebP)')
                                                    ->imagePreviewHeight('150')
                                                    ->uploadingMessage('Mengupload foto...')
                                                    ->removeUploadedFileButtonPosition('right')
                                                    ->uploadButtonPosition('left')
                                                    ->extraAttributes(['class' => 'avatar-upload-field'])
                                                    ->columnSpan(1),

                                                FileUpload::make('signature_url')
                                                    ->label('Tanda Tangan Digital')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('signatures')
                                                    ->visibility('public')
                                                    ->openable()
                                                    ->downloadable()
                                                    ->maxSize(2048)
                                                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                                    ->helperText('Upload gambar tanda tangan (transparan lebih baik). Format: PNG, JPG.')
                                                    ->columnSpan(1),

                                                Select::make('status')
                                                    ->label('Status Akun')
                                                    ->options([
                                                        'active' => '🟢 Aktif - Dapat mengakses sistem',
                                                        'inactive' => '🟠 Nonaktif - Akses sementara diblokir',
                                                        'terminated' => '🔴 Terminated - Akses permanent diblokir',
                                                    ])
                                                    ->default('active')
                                                    ->required()
                                                    ->helperText('Mengatur tingkat akses pengguna ke sistem')
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        if ($state === 'terminated') {
                                                            $set('expire_date', now());
                                                        } else {
                                                            $set('expire_date', null);
                                                        }
                                                    }),
                                            ]),

                                        DateTimePicker::make('expire_date')
                                            ->label('Tanggal Kedaluwarsa Akun')
                                            ->helperText('Kosongkan jika akun tidak memiliki batas waktu. Otomatis diisi jika status Terminated.')
                                            ->displayFormat('d/m/Y H:i')
                                            ->disabled(fn ($get) => $get('status') === 'terminated')
                                            ->dehydrated()
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Documents & Notes')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Document Upload')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                FileUpload::make('contract_document')
                                                    ->label('Dokumen Kontrak')
                                                    ->directory('user-contracts')
                                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                    ->maxSize(5120)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->helperText('Upload dokumen kontrak kerja (PDF, JPG, PNG - maksimal 5MB)'),

                                                FileUpload::make('identity_document')
                                                    ->label('Dokumen Identitas')
                                                    ->directory('user-identity')
                                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                    ->maxSize(5120)
                                                    ->openable()
                                                    ->downloadable()
                                                    ->helperText('Upload dokumen identitas (KTP, SIM, Passport - maksimal 5MB)'),
                                            ]),

                                        FileUpload::make('additional_documents')
                                            ->label('Dokumen Tambahan')
                                            ->directory('user-documents')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                            ->maxSize(5120)
                                            ->maxFiles(5)
                                            ->openable()
                                            ->downloadable()
                                            ->helperText('Upload dokumen tambahan (maksimal 5 file, masing-masing 5MB)')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Notes & Comments')
                                    ->schema([
                                        Textarea::make('notes')
                                            ->label('Catatan Karyawan')
                                            ->placeholder('Tambahkan catatan khusus tentang karyawan ini (prestasi, peringatan, dll.)')
                                            ->rows(4)
                                            ->maxLength(2000)
                                            ->helperText('Catatan internal yang tidak terlihat oleh karyawan (maksimal 2000 karakter)')
                                            ->columnSpanFull(),

                                        Textarea::make('emergency_contact')
                                            ->label('Kontak Darurat')
                                            ->placeholder('Nama: [Nama]\nHubungan: [Hubungan]\nTelepon: [Nomor]\nAlamat: [Alamat]')
                                            ->rows(4)
                                            ->maxLength(1000)
                                            ->helperText('Informasi kontak darurat karyawan (maksimal 1000 karakter)')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
