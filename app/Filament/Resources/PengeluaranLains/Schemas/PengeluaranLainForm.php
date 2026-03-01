<?php

namespace App\Filament\Resources\PengeluaranLains\Schemas;

use App\Models\NotaDinas;
use App\Models\NotaDinasDetail;
use App\Models\PengeluaranLain;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class PengeluaranLainForm
{
    private static function safeFloatVal($value): float
    {
        if (is_null($value)) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return floatval($value);
        }

        if (is_string($value)) {
            $cleaned = preg_replace('/[^\d.,]/', '', $value);
            $cleaned = str_replace(',', '', $cleaned);
            if ($cleaned === '' || $cleaned === '.') {
                return 0.0;
            }

            return floatval($cleaned);
        }

        if (is_array($value)) {
            return 0.0;
        }

        return 0.0;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pengeluaran Lain')
                ->description('Detail pengeluaran di luar operasional harian')
                ->icon('heroicon-o-credit-card')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Nama Pengeluaran')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->placeholder('Akan terisi otomatis dari detail nota dinas atau isi manual')
                            ->helperText('Terisi otomatis dari "Keperluan + Event" pada detail nota dinas yang dipilih')
                            ->columnSpan(1),
                        TextInput::make('amount')
                            ->required()
                            ->label('Nominal')
                            ->prefix('Rp. ')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->placeholder('0')
                            ->columnSpan(1),
                    ]),
                ]),

            Section::make('Detail Transaksi & Nota Dinas')
                ->description('Informasi pembayaran melalui Nota Dinas dan dokumentasi')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('nota_dinas_id')
                                ->label('Nota Dinas')
                                ->options(function () {
                                    return NotaDinas::whereIn('status', ['disetujui', 'diajukan'])
                                        ->whereHas('details', function ($query) {
                                            $query->where('jenis_pengeluaran', 'lain-lain');
                                        })
                                        ->orderBy('created_at', 'desc')
                                        ->pluck('no_nd', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        $set('nota_dinas_detail_id', null);
                                        $set('vendor_id', null);
                                        $set('bank_name', null);
                                        $set('account_holder', null);
                                        $set('bank_account', null);
                                        $set('amount', null);
                                        $set('note', null);
                                        $set('name', null);
                                    }
                                })
                                ->columnSpan(1),

                            Select::make('nota_dinas_detail_id')
                                ->label('Detail Nota Dinas')
                                ->options(function (callable $get) {
                                    $notaDinasId = $get('nota_dinas_id');
                                    if (! $notaDinasId) {
                                        return [];
                                    }

                                    try {
                                        $currentDetailId = $get('nota_dinas_detail_id');

                                        $usedDetailIds = PengeluaranLain::whereNotNull('nota_dinas_detail_id')
                                            ->when($get('id'), function ($query) use ($get) {
                                                return $query->where('id', '!=', $get('id'));
                                            })
                                            ->pluck('nota_dinas_detail_id')
                                            ->toArray();

                                        $availableDetails = NotaDinasDetail::with('vendor')
                                            ->where('nota_dinas_id', $notaDinasId)
                                            ->where('jenis_pengeluaran', 'lain-lain')
                                            ->whereNotIn('id', $usedDetailIds)
                                            ->whereHas('vendor')
                                            ->get();

                                        if ($currentDetailId && ! $availableDetails->contains('id', $currentDetailId)) {
                                            $currentDetail = NotaDinasDetail::with('vendor')->find($currentDetailId);
                                            if ($currentDetail && $currentDetail->vendor) {
                                                $availableDetails->prepend($currentDetail);
                                            }
                                        }

                                        return $availableDetails->mapWithKeys(function ($detail) use ($usedDetailIds) {
                                            $vendorName = $detail->vendor->name ?? 'N/A';
                                            $keperluan = $detail->keperluan ?? 'N/A';
                                            $jumlah = number_format($detail->jumlah_transfer, 0, ',', '.');

                                            $usedIndicator = in_array($detail->id, $usedDetailIds) ? ' (Tersedia kembali)' : '';

                                            $label = "{$vendorName} | {$keperluan} | Rp {$jumlah}{$usedIndicator}";

                                            return [$detail->id => $label];
                                        })->toArray();
                                    } catch (Exception $e) {
                                        Log::error('Error in nota_dinas_detail_id options: '.$e->getMessage());

                                        return [];
                                    }
                                })
                                ->searchable()
                                ->reactive()
                                ->live()
                                ->helperText(function (callable $get) {
                                    try {
                                        $notaDinasId = $get('nota_dinas_id');
                                        if (! $notaDinasId) {
                                            return 'Pilih Nota Dinas terlebih dahulu';
                                        }

                                        $usedDetailIds = PengeluaranLain::whereNotNull('nota_dinas_detail_id')
                                            ->when($get('id'), function ($query) use ($get) {
                                                return $query->where('id', '!=', $get('id'));
                                            })
                                            ->pluck('nota_dinas_detail_id')
                                            ->toArray();

                                        $actualUsedCount = count($usedDetailIds);
                                        $totalCount = NotaDinasDetail::where('nota_dinas_id', $notaDinasId)
                                            ->where('jenis_pengeluaran', 'lain-lain')
                                            ->count();

                                        return "Pilih detail nota dinas 'Lain-lain' yang akan dibayar (Sudah dipilih: {$actualUsedCount}/{$totalCount})";
                                    } catch (Exception $e) {
                                        return 'Pilih detail nota dinas yang akan dibayar';
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    try {
                                        if (! $state) {
                                            $set('vendor_id', null);
                                            $set('bank_name', null);
                                            $set('account_holder', null);
                                            $set('bank_account', null);
                                            $set('amount', null);
                                            $set('note', null);
                                            $set('name', null);

                                            return;
                                        }

                                        $notaDinasDetail = NotaDinasDetail::with('vendor')->find($state);
                                        if ($notaDinasDetail) {
                                            $set('vendor_id', $notaDinasDetail->vendor_id);
                                            $set('bank_name', $notaDinasDetail->bank_name ?? $notaDinasDetail->vendor->bank_name);
                                            $set('account_holder', $notaDinasDetail->account_holder ?? $notaDinasDetail->vendor->account_holder);
                                            $set('bank_account', $notaDinasDetail->bank_account ?? $notaDinasDetail->vendor->bank_account);
                                            $set('amount', self::safeFloatVal($notaDinasDetail->jumlah_transfer ?? 0));
                                            $set('note', $notaDinasDetail->keperluan ?? null);

                                            $nameComponents = [];
                                            if ($notaDinasDetail->keperluan) {
                                                $nameComponents[] = $notaDinasDetail->keperluan;
                                            }
                                            if ($notaDinasDetail->event && $notaDinasDetail->event !== $notaDinasDetail->keperluan) {
                                                $nameComponents[] = $notaDinasDetail->event;
                                            }

                                            $autoName = ! empty($nameComponents) ? implode(' - ', $nameComponents) : 'Pengeluaran Lain';
                                            $set('name', $autoName);

                                            $notaDinas = $notaDinasDetail->notaDinas;
                                            if ($notaDinas) {
                                                $set('no_nd', $notaDinas->no_nd);
                                            }
                                        }
                                    } catch (Exception $e) {
                                        Log::error('Error in afterStateUpdated: '.$e->getMessage());
                                    }
                                })
                                ->required()
                                ->columnSpan(2),

                            Hidden::make('vendor_id'),
                        ]),

                    Grid::make(4)
                        ->schema([
                            TextInput::make('bank_name')
                                ->label('Bank')
                                ->required()
                                ->live()
                                ->columnSpan(1),

                            TextInput::make('account_holder')
                                ->label('Nama Rekening')
                                ->required()
                                ->live()
                                ->columnSpan(1),

                            TextInput::make('bank_account')
                                ->label('Nomor Rekening')
                                ->required()
                                ->live()
                                ->columnSpan(1),

                            DatePicker::make('tanggal_transfer')
                                ->label('Tanggal Transfer')
                                ->helperText(new HtmlString('<span style="color: #ef4444;">Sesuaikan tanggal transfer</span>'))
                                ->default(now())
                                ->required()
                                ->columnSpan(1),
                        ]),

                    Grid::make(3)
                        ->schema([
                            Select::make('payment_method_id')
                                ->relationship('paymentMethod', 'name')
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->is_cash ? 'Kas/Tunai' : ($record->bank_name ? "{$record->bank_name} - {$record->no_rekening}" : $record->name))
                                ->label('Sumber pembayaran')
                                ->searchable()
                                ->helperText(new HtmlString('<span style="color: #ef4444;">Sesuaikan rekening transfer</span>'))
                                ->preload()
                                ->required()
                                ->columnSpan(1),

                            DatePicker::make('date_expense')
                                ->label('Tanggal Pengeluaran')
                                ->date()
                                ->required()
                                ->native(false)
                                ->displayFormat('d M Y')
                                ->default(now())
                                ->columnSpan(1),

                            Select::make('kategori_transaksi')
                                ->options([
                                    'uang_keluar' => 'Uang Keluar',
                                ])
                                ->default('uang_keluar')
                                ->label('Tipe Transaksi')
                                ->required()
                                ->disabled()
                                ->columnSpan(1),
                        ]),

                    Grid::make(1)
                        ->schema([
                            TextInput::make('no_nd')
                                ->label('Nomor Nota Dinas')
                                ->required()
                                ->live()
                                ->helperText('Akan otomatis terisi setelah memilih detail nota dinas'),

                            Textarea::make('note')
                                ->label('Catatan Tambahan / Keperluan')
                                ->required()
                                ->rows(3)
                                ->live()
                                ->helperText('Akan otomatis terisi dari detail nota dinas, dapat diedit'),

                            FileUpload::make('image')
                                ->label('Bukti Pembayaran')
                                ->image()
                                ->imageEditor()
                                ->directory('pengeluaran-lain/'.date('Y/m'))
                                ->visibility('private')
                                ->downloadable()
                                ->openable()
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                                ->maxSize(1280)
                                ->helperText('Max 1MB. JPG, PNG, or PDF format.')
                                ->required(),
                        ]),
                ]),
        ])->columns(1);
    }
}
