<?php

namespace App\Filament\Resources\BankStatements\Schemas;

use App\Models\BankStatement;
use App\Models\PaymentMethod;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class BankStatementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('BankStatementTabs')
                    ->tabs([
                        Tab::make('Informasi Utama')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Informasi Rekening')
                                    ->description('Pilih rekening bank yang akan digunakan untuk rekening koran')
                                    ->schema([
                                        Select::make('payment_method_id')
                                            ->relationship(
                                                'paymentMethod',
                                                'no_rekening',
                                                fn (\Illuminate\Database\Eloquent\Builder $query) => $query
                                                    ->whereNotNull('no_rekening')
                                                    ->where('no_rekening', '!=', '')
                                                    ->whereNotNull('bank_name')
                                                    ->where('bank_name', '!=', '')
                                                    ->orderBy('bank_name')
                                                    ->orderBy('no_rekening')
                                            )
                                            ->searchable(['bank_name', 'no_rekening'])
                                            ->preload()
                                            ->required()
                                            ->label('Rekening Bank')
                                            ->placeholder('Pilih rekening bank...')
                                            ->helperText('Pilih rekening bank yang memiliki nomor rekening valid')
                                            ->getOptionLabelFromRecordUsing(fn (PaymentMethod $record) => $record->no_rekening && $record->bank_name
                                                    ? "{$record->bank_name} - {$record->no_rekening}".
                                                      ($record->cabang ? " - {$record->name}" : '')
                                                    : 'Data rekening tidak lengkap'
                                            )
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->label('Nama Metode Pembayaran')
                                                    ->required()
                                                    ->maxLength(255),
                                                TextInput::make('bank_name')
                                                    ->label('Nama Bank')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Contoh: BCA, Mandiri, BNI'),
                                                TextInput::make('no_rekening')
                                                    ->label('Nomor Rekening')
                                                    ->required()
                                                    ->maxLength(50)
                                                    ->placeholder('Masukkan nomor rekening'),
                                                TextInput::make('cabang')
                                                    ->label('Cabang')
                                                    ->maxLength(255)
                                                    ->placeholder('Nama cabang (opsional)'),
                                            ])
                                            ->afterStateUpdated(function (?int $state, callable $set) {
                                                if ($state) {
                                                    $paymentMethod = PaymentMethod::find($state);
                                                    if ($paymentMethod) {
                                                        $set('branch', $paymentMethod->cabang);
                                                    }
                                                } else {
                                                    $set('branch', null);
                                                }
                                            })
                                            ->live(),

                                        TextInput::make('branch')
                                            ->label('Cabang')
                                            ->maxLength(255)
                                            ->placeholder('Cabang akan terisi otomatis dari rekening yang dipilih')
                                            ->helperText('Informasi cabang dari rekening yang dipilih'),
                                    ])->columns(1),

                                Section::make('Periode Rekening Koran')
                                    ->description('Tentukan periode waktu untuk rekening koran')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                DatePicker::make('period_start')
                                                    ->label('Periode Awal')
                                                    ->required()
                                                    ->native(false)
                                                    ->displayFormat('d M Y')
                                                    ->placeholder('Pilih tanggal mulai')
                                                    ->helperText('Tanggal awal periode rekening koran')
                                                    ->live()
                                                    ->afterStateUpdated(function (?string $state, callable $set, callable $get) {
                                                        if ($state && ! $get('period_end')) {
                                                            $endDate = \Carbon\Carbon::parse($state)->addDays(29);
                                                            $set('period_end', $endDate->format('Y-m-d'));
                                                        }
                                                    }),
                                                DatePicker::make('period_end')
                                                    ->label('Periode Akhir')
                                                    ->required()
                                                    ->native(false)
                                                    ->displayFormat('d M Y')
                                                    ->placeholder('Pilih tanggal akhir')
                                                    ->helperText('Tanggal akhir periode rekening koran')
                                                    ->afterOrEqual('period_start'),
                                            ]),

                                        SchemaActions::make([
                                            Action::make('set_current_month')
                                                ->label('Bulan Ini')
                                                ->icon('heroicon-o-calendar')
                                                ->color('primary')
                                                ->action(function (callable $set) {
                                                    $now = \Carbon\Carbon::now();
                                                    // copy() agar startOfMonth/endOfMonth tidak mutasi instance yang sama
                                                    $set('period_start', $now->copy()->startOfMonth()->format('Y-m-d'));
                                                    $set('period_end', $now->copy()->endOfMonth()->format('Y-m-d'));
                                                }),
                                            Action::make('set_last_month')
                                                ->label('Bulan Lalu')
                                                ->icon('heroicon-o-calendar-days')
                                                ->color('gray')
                                                ->action(function (callable $set) {
                                                    // subMonthNoOverflow agar Jan → Des, bukan Feb 31 → Mar 3
                                                    $lastMonth = \Carbon\Carbon::now()->subMonthNoOverflow();
                                                    $set('period_start', $lastMonth->copy()->startOfMonth()->format('Y-m-d'));
                                                    $set('period_end', $lastMonth->copy()->endOfMonth()->format('Y-m-d'));
                                                }),
                                            Action::make('set_last_30_days')
                                                ->label('30 Hari Terakhir')
                                                ->icon('heroicon-o-clock')
                                                ->color('success')
                                                ->action(function (callable $set) {
                                                    // Gunakan copy() agar instance $end tidak termutasi saat subDays
                                                    $end   = \Carbon\Carbon::today();
                                                    $start = $end->copy()->subDays(30);
                                                    $set('period_start', $start->format('Y-m-d'));
                                                    $set('period_end', $end->format('Y-m-d'));
                                                }),
                                        ])->extraAttributes(['class' => 'mt-4']),
                                    ]),
                            ]),

                        Tab::make('File & Dokumen')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('File Rekening Koran')
                                    ->description('Upload file rekening koran dari bank')
                                    ->schema([
                                        Select::make('source_type')
                                            ->label('Tipe Sumber File')
                                            ->options(BankStatement::getSourceTypeOptions())
                                            ->required()
                                            ->default('pdf')
                                            ->helperText('Pilih jenis file yang akan diupload')
                                            ->live()
                                            ->afterStateUpdated(function (?string $state, callable $set) {
                                                if ($state === 'manual_input') {
                                                    $set('file_path', null);
                                                }
                                            }),

                                        FileUpload::make('file_path')
                                            ->label('Upload File Rekening Koran')
                                            ->disk('private')
                                            ->directory('bank-statements')
                                            ->storeFileNamesIn('original_filename')   // otomatis simpan nama file asli
                                            ->acceptedFileTypes(function (callable $get) {
                                                $sourceType = $get('source_type');

                                                return match ($sourceType) {
                                                    'pdf' => ['application/pdf'],
                                                    'excel' => [
                                                        'application/vnd.ms-excel',
                                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                        'text/csv',
                                                    ],
                                                    default => ['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                                                };
                                            })
                                            ->maxSize(10240)
                                            ->helperText(function (callable $get) {
                                                $sourceType = $get('source_type');

                                                return match ($sourceType) {
                                                    'pdf' => 'Upload file PDF rekening koran (max 10MB)',
                                                    'excel' => 'Upload file Excel/CSV (max 10MB)',
                                                    'manual_input' => 'File tidak diperlukan untuk input manual',
                                                    default => 'Upload file rekening koran (max 10MB)'
                                                };
                                            })
                                            ->required(fn (callable $get) => $get('source_type') !== 'manual_input')
                                            ->visible(fn (callable $get) => $get('source_type') !== 'manual_input')
                                            ->deletable(true)
                                            ->downloadable(false)
                                            ->previewable(false)
                                            ->loadingIndicatorPosition('left')
                                            ->removeUploadedFileButtonPosition('right')
                                            ->uploadButtonPosition('left')
                                            ->uploadProgressIndicatorPosition('left'),

                                        Placeholder::make('file_info')
                                            ->label('Informasi File')
                                            ->content(function (callable $get, $livewire) {
                                                $record = $livewire->record ?? null;
                                                if ($record && $record->file_path) {
                                                    $disk = null;
                                                    if (Storage::disk('private')->exists($record->file_path)) {
                                                        $disk = 'private';
                                                    } elseif (Storage::disk('public')->exists($record->file_path)) {
                                                        $disk = 'public';
                                                    }

                                                    if ($disk) {
                                                        $fileSize = Storage::disk($disk)->size($record->file_path);
                                                        $formattedSize = $fileSize > 1024 * 1024
                                                            ? round($fileSize / (1024 * 1024), 2).' MB'
                                                            : round($fileSize / 1024, 2).' KB';

                                                        return new HtmlString(
                                                            '<div class="space-y-2">'.
                                                            '<div><strong>Ukuran:</strong> '.$formattedSize.'</div>'.
                                                            '<div><strong>Diupload:</strong> '.$record->created_at->format('d M Y H:i').'</div>'.
                                                            '<div><a href="'.route('bank-statements.download', $record).'" target="_blank" class="text-primary-600 hover:text-primary-700 underline font-medium">📄 Buka File</a></div>'.
                                                            '</div>'
                                                        );
                                                    }
                                                }

                                                return 'Belum ada file yang diupload';
                                            })
                                            ->visible(fn ($record) => $record && filled($record->file_path))
                                            ->extraAttributes(['class' => 'text-sm bg-gray-50 p-3 rounded-lg']),
                                    ]),
                            ]),

                        Tab::make('Detail Keuangan')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Detail Finansial')
                                    ->description('Informasi saldo dan transaksi dari rekening koran')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('opening_balance')
                                                    ->label('Saldo Awal')
                                                    ->prefix('Rp ')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters(',')
                                                    ->dehydrateStateUsing(fn (mixed $state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                                    ->placeholder('0')
                                                    ->helperText('Saldo awal periode')
                                                    ->live(debounce: 500),

                                                TextInput::make('closing_balance')
                                                    ->label('Saldo Akhir')
                                                    ->prefix('Rp ')
                                                    ->placeholder('0')
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->dehydrateStateUsing(fn (mixed $state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                                    ->stripCharacters(',')
                                                    ->helperText('Saldo akhir periode')
                                                    ->live(debounce: 500),

                                                Placeholder::make('balance_difference')
                                                    ->label('Selisih Saldo')
                                                    ->content(function (callable $get) {
                                                        $openingRaw = $get('opening_balance') ?? '';
                                                        $closingRaw = $get('closing_balance') ?? '';

                                                        $opening = $openingRaw ? (float) str_replace(['.', ',', ' '], '', $openingRaw) : 0;
                                                        $closing = $closingRaw ? (float) str_replace(['.', ',', ' '], '', $closingRaw) : 0;

                                                        $difference = $closing - $opening;

                                                        if ($difference == 0) {
                                                            return new HtmlString(
                                                                '<div class="text-gray-600 font-medium text-lg">IDR 0</div>'
                                                            );
                                                        }

                                                        $color = $difference > 0 ? 'text-green-600' : 'text-red-600';
                                                        $sign = $difference > 0 ? '+' : '';

                                                        return new HtmlString(
                                                            '<div class="'.$color.' font-semibold text-lg">'.
                                                            $sign.'IDR '.number_format($difference, 0, ',', '.').
                                                            '</div>'
                                                        );
                                                    }),
                                            ]),

                                        Fieldset::make('Transaksi Debit')
                                            ->schema([
                                                Grid::make(1)
                                                    ->schema([
                                                        TextInput::make('no_of_debit')
                                                            ->label('Jumlah Transaksi Debit')
                                                            ->placeholder('0')
                                                            ->suffix('transaksi')
                                                            ->numeric()
                                                            ->helperText('Total jumlah transaksi debit'),

                                                        TextInput::make('tot_debit')
                                                            ->label('Total Nominal Debit')
                                                            ->dehydrateStateUsing(fn (mixed $state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                                            ->prefix('IDR')
                                                            ->placeholder('0')
                                                            ->mask(RawJs::make('$money($input)'))
                                                            ->stripCharacters(',')
                                                            ->helperText('Total nilai transaksi debit'),
                                                    ]),
                                            ]),

                                        Fieldset::make('Transaksi Kredit')
                                            ->schema([
                                                Grid::make(1)
                                                    ->schema([
                                                        TextInput::make('no_of_credit')
                                                            ->label('Jumlah Transaksi Kredit')
                                                            ->numeric()
                                                            ->placeholder('0')
                                                            ->suffix('transaksi')
                                                            ->helperText('Total jumlah transaksi kredit'),

                                                        TextInput::make('tot_credit')
                                                            ->label('Total Nominal Kredit')
                                                            ->dehydrateStateUsing(fn (mixed $state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                                            ->prefix('IDR')
                                                            ->placeholder('0')
                                                            ->mask(RawJs::make('$money($input)'))
                                                            ->stripCharacters(',')
                                                            ->helperText('Total nilai transaksi kredit'),
                                                    ]),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Rekonsiliasi')
                            ->icon('heroicon-o-scale')
                            ->schema([
                                Section::make('Upload File Excel Rekonsiliasi')
                                    ->description('Upload file Excel untuk rekonsiliasi bank (opsional)')
                                    ->schema([
                                        FileUpload::make('reconciliation_file')
                                            ->label('File Excel Rekonsiliasi')
                                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                                            ->disk('private')
                                            ->directory('bank-reconciliations')
                                            ->preserveFilenames()
                                            ->downloadable(false)
                                            ->previewable(false)
                                            ->deletable(true)
                                            ->removeUploadedFileButtonPosition('right')
                                            ->loadingIndicatorPosition('left')
                                            ->uploadButtonPosition('left')
                                            ->uploadProgressIndicatorPosition('left')
                                            ->maxSize(10240)
                                            // Existing file tidak diisi kembali ke field ini saat edit
                                            // (private disk tidak bisa di-serve via URL → stuck loading).
                                            // Untuk hapus file yang ada: gunakan tombol "Hapus File Rekonsiliasi" di header.
                                            ->helperText(function ($livewire) {
                                                $record = $livewire->record ?? null;
                                                if ($record && filled($record->reconciliation_file)) {
                                                    $name = $record->reconciliation_original_filename ?? basename($record->reconciliation_file);

                                                    return new \Illuminate\Support\HtmlString(
                                                        '<span class="text-amber-600 font-medium">⚠ File saat ini: '.e($name).'</span><br>'.
                                                        'Upload file baru di sini untuk mengganti. Untuk menghapus, gunakan tombol <strong>Hapus File Rekonsiliasi</strong> di bagian atas halaman.'
                                                    );
                                                }

                                                return 'Upload file Excel rekening koran. Didukung: (1) File asli Bank Mandiri (.xlsx) — langsung dari internet banking, atau (2) Template 4-kolom: Tanggal | Keterangan | Debit | Credit. Format terdeteksi otomatis.';
                                            })
                                            ->storeFileNamesIn('reconciliation_original_filename')
                                            ->nullable(),
                                        Placeholder::make('reconciliation_file_info')
                                            ->label('Informasi File Rekonsiliasi')
                                            ->content(function ($livewire) {
                                                $record = $livewire->record ?? null;
                                                if (! $record || ! $record->reconciliation_file) {
                                                    return 'Belum ada file rekonsiliasi yang diupload';
                                                }

                                                $disk = null;
                                                if (Storage::disk('private')->exists($record->reconciliation_file)) {
                                                    $disk = 'private';
                                                } elseif (Storage::disk('public')->exists($record->reconciliation_file)) {
                                                    $disk = 'public';
                                                }

                                                if (! $disk) {
                                                    return 'File rekonsiliasi tidak ditemukan di storage';
                                                }

                                                $fileSize = Storage::disk($disk)->size($record->reconciliation_file);
                                                $formattedSize = $fileSize > 1024 * 1024
                                                    ? round($fileSize / (1024 * 1024), 2).' MB'
                                                    : round($fileSize / 1024, 2).' KB';

                                                return new HtmlString(
                                                    '<div class="space-y-2">'.
                                                    '<div><strong>Ukuran:</strong> '.$formattedSize.'</div>'.
                                                    '<div><strong>Diupload:</strong> '.$record->updated_at->format('d M Y H:i').'</div>'.
                                                    '<div><a href="'.route('bank-statements.reconciliation.download', $record).'" target="_blank" class="text-primary-600 hover:text-primary-700 underline font-medium">📄 Buka File Rekonsiliasi</a></div>'.
                                                    '</div>'
                                                );
                                            })
                                            ->visible(fn ($record) => $record && filled($record->reconciliation_file))
                                            ->extraAttributes(['class' => 'text-sm bg-gray-50 p-3 rounded-lg']),
                                    ])->columns(1)
                                    ->collapsible()
                                    ->collapsed(),

                                Section::make('Status Rekonsiliasi')
                                    ->schema([
                                        Select::make('reconciliation_status')
                                            ->label('Status Rekonsiliasi')
                                            ->options(BankStatement::getReconciliationStatusOptions())
                                            ->default('uploaded')
                                            ->disabled(fn (string $operation): bool => $operation === 'create'),

                                        TextInput::make('total_records')
                                            ->label('Total Records')
                                            ->numeric()
                                            ->disabled()
                                            ->default(0),

                                        TextInput::make('total_debit_reconciliation')
                                            ->label('Total Debit Rekonsiliasi')
                                            ->prefix('Rp ')
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->dehydrateStateUsing(fn (mixed $state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                            ->disabled()
                                            ->dehydrated(true)   // disabled tidak otomatis dehydrate — harus eksplisit
                                            ->default(0),

                                        TextInput::make('total_credit_reconciliation')
                                            ->label('Total Credit Rekonsiliasi')
                                            ->dehydrateStateUsing(fn (mixed $state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                            ->prefix('Rp ')
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->disabled()
                                            ->dehydrated(true)   // disabled tidak otomatis dehydrate — harus eksplisit
                                            ->default(0),
                                    ])->columns(2)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ])->columnSpanFull(),

                // status diset default 'pending' saat create; Observer menangani uploaded_by & last_edited_by
                Hidden::make('status')->default('pending'),
                Hidden::make('original_filename'),
                Hidden::make('reconciliation_original_filename'),
            ]);
    }
}
