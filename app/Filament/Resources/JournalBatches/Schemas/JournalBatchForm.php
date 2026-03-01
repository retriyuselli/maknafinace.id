<?php

namespace App\Filament\Resources\JournalBatches\Schemas;

use App\Models\JournalBatch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class JournalBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('manual_journal_warning')
                    ->content('⚠️ **Manual Journal Entry**: Gunakan hanya untuk jurnal penyesuaian, koreksi, transaksi aset tetap, atau entri non-operasional. Jurnal expense/payment umumnya di-generate otomatis dari data transaksi.')
                    ->columnSpanFull()
                    ->visible(fn ($livewire) => $livewire instanceof CreateRecord),

                Section::make('Informasi Jurnal')
                    ->schema([
                        Select::make('manual_journal_type')
                            ->label('Jenis Jurnal Manual')
                            ->options([
                                'adjustment' => 'Jurnal Penyesuaian (Depreciation, Accruals, etc.)',
                                'correction' => 'Jurnal Koreksi (Error correction, Reclassification)',
                                'asset' => 'Jurnal Aset Tetap (Purchase, Disposal, etc.)',
                                'financial' => 'Jurnal Keuangan (Loan, Investment, Bank charges)',
                                'tax' => 'Jurnal Pajak (Tax provision, Tax payment)',
                                'other' => 'Lainnya (Specify in description)',
                            ])
                            ->helperText('Pilih kategori jurnal manual untuk membantu tracking dan audit')
                            ->visible(fn ($livewire) => $livewire instanceof CreateRecord)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('batch_number')
                                    ->label('Nomor Batch')
                                    ->helperText('Nomor unik untuk identifikasi batch jurnal. Otomatis di-generate sistem.')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->default(fn () => JournalBatch::generateBatchNumber())
                                    ->maxLength(20),

                                DatePicker::make('transaction_date')
                                    ->label('Tanggal Transaksi')
                                    ->helperText('Tanggal ketika transaksi terjadi. Tidak boleh tanggal masa depan.')
                                    ->required()
                                    ->default(now()),

                                Select::make('status')
                                    ->label('Status')
                                    ->helperText('Draft: Dapat diedit. Posted: Sudah final. Reversed: Dibatalkan.')
                                    ->options([
                                        'draft' => 'Draft',
                                        'posted' => 'Posted',
                                        'reversed' => 'Reversed',
                                    ])
                                    ->default('draft')
                                    ->required(),
                            ]),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->helperText('Deskripsi transaksi jurnal. Contoh: "Pembelian equipment untuk acara Wedding Sari" atau "Pembayaran vendor catering"')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('reference_type')
                                    ->label('Jenis Referensi')
                                    ->helperText('Jenis dokumen yang menjadi dasar jurnal. Contoh: "expense", "payment", "revenue", "adjustment"')
                                    ->maxLength(255)
                                    ->placeholder('Contoh: expense, payment, revenue'),

                                TextInput::make('reference_id')
                                    ->label('ID Referensi')
                                    ->helperText('ID dari dokumen referensi (expense ID, payment ID, order ID, dll)')
                                    ->numeric()
                                    ->placeholder('Contoh: 144, 1052, 2031'),
                            ]),
                    ]),

                Section::make('Total Transaksi')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('total_debit')
                                    ->label('Total Debit')
                                    ->helperText('Total nilai debit dalam jurnal. Dihitung otomatis dari journal entries.')
                                    ->required()
                                    ->prefix('Rp. ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                    ->default(0)
                                    ->readOnly(),

                                TextInput::make('total_credit')
                                    ->label('Total Kredit')
                                    ->helperText('Total nilai kredit dalam jurnal. Harus sama dengan total debit.')
                                    ->required()
                                    ->prefix('Rp. ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrateStateUsing(fn ($state) => (int) preg_replace('/[^\d]/', '', (string) $state))
                                    ->default(0)
                                    ->readOnly(),
                            ]),
                    ]),

                Section::make('Approval')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('created_by')
                                    ->label('Dibuat Oleh')
                                    ->relationship('createdBy', 'name')
                                    ->default(1)
                                    ->required()
                                    ->disabled(),

                                Select::make('approved_by')
                                    ->label('Disetujui Oleh')
                                    ->relationship('approvedBy', 'name')
                                    ->nullable(),

                                DateTimePicker::make('approved_at')
                                    ->label('Tanggal Persetujuan')
                                    ->nullable(),
                            ]),
                    ]),
            ]);
    }
}
