<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Models\Expense;
use App\Models\NotaDinas;
use App\Models\NotaDinasDetail;
use App\Models\PaymentMethod;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Pengeluaran';

    public function form(Schema $schema): Schema
    {
        $orderId = (int) ($this->getOwnerRecord()?->id ?? 0);

        return $schema
            ->components([
                Section::make('Nota Dinas & Metode')
                    ->schema([
                        Grid::make(6)
                            ->schema([
                                Select::make('nota_dinas_id')
                                    ->label('Nota Dinas')
                                    ->options(function () use ($orderId) {
                                        if (! $orderId) {
                                            return [];
                                        }

                                        return NotaDinas::whereHas('details', function ($query) use ($orderId) {
                                            $query->where('order_id', $orderId);
                                        })->pluck('no_nd', 'id')->toArray();
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (! $state) {
                                            $set('nota_dinas_detail_id', null);
                                            $set('vendor_id', null);
                                            $set('note', null);
                                            $set('amount', null);
                                            $set('account_holder', null);
                                            $set('bank_name', null);
                                            $set('bank_account', null);
                                            $set('no_nd', null);

                                            return;
                                        }

                                        $notaDinas = NotaDinas::find($state);
                                        if ($notaDinas) {
                                            $set('no_nd', $notaDinas->no_nd);
                                        }
                                    })
                                    ->columnSpan(3),
                                Select::make('payment_method_id')
                                    ->label('Metode Pembayaran')
                                    ->relationship('paymentMethod', 'name')
                                    ->getOptionLabelFromRecordUsing(function ($record): string {
                                        if (! $record) {
                                            return '-';
                                        }

                                        return $record->is_cash ? 'Kas/Tunai' : ($record->bank_name ? "{$record->bank_name} - {$record->no_rekening}" : $record->name);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(3),
                                Select::make('nota_dinas_detail_id')
                                    ->label('Detail Nota Dinas')
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search, callable $get, ?Expense $record) use ($orderId): array {
                                        $notaDinasId = $get('nota_dinas_id');
                                        if (! $notaDinasId || ! $orderId) {
                                            return [];
                                        }

                                        $currentId = $record?->id;
                                        $currentDetailId = $record?->nota_dinas_detail_id;

                                        $usedIds = Expense::where('order_id', $orderId)
                                            ->whereNotNull('nota_dinas_detail_id')
                                            ->when($currentId, fn (Builder $q) => $q->where('id', '!=', $currentId))
                                            ->pluck('nota_dinas_detail_id')
                                            ->all();

                                        $query = NotaDinasDetail::query()
                                            ->with('vendor')
                                            ->where('nota_dinas_id', $notaDinasId)
                                            ->where('jenis_pengeluaran', 'wedding')
                                            ->whereHas('vendor')
                                            ->when(count($usedIds) > 0, fn (Builder $q) => $q->whereNotIn('id', $usedIds))
                                            ->when($search !== '', function (Builder $q) use ($search) {
                                                $q->where(function (Builder $q) use ($search) {
                                                    $q->where('keperluan', 'like', "%{$search}%")
                                                        ->orWhere('payment_stage', 'like', "%{$search}%")
                                                        ->orWhereHas('vendor', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
                                                });
                                            })
                                            ->limit(50);

                                        $results = $query->get();

                                        if ($currentDetailId && ! $results->contains('id', $currentDetailId)) {
                                            $current = NotaDinasDetail::with('vendor')->find($currentDetailId);
                                            if ($current && $current->vendor) {
                                                $results->prepend($current);
                                            }
                                        }

                                        return $results->mapWithKeys(function ($detail) {
                                            $vendorName = $detail->vendor->name ?? 'N/A';
                                            $keperluan = $detail->keperluan ?? 'N/A';
                                            $paymentStage = $detail->payment_stage ? " | {$detail->payment_stage}" : '';
                                            $jumlah = number_format((int) $detail->jumlah_transfer, 0, ',', '.');

                                            return [$detail->id => "{$vendorName} | {$keperluan}{$paymentStage} | Rp {$jumlah}"];
                                        })->toArray();
                                    })
                                    ->getOptionLabelUsing(function ($value): ?string {
                                        if (! $value) {
                                            return null;
                                        }

                                        $detail = NotaDinasDetail::with('vendor')->find($value);
                                        if (! $detail || ! $detail->vendor) {
                                            return null;
                                        }

                                        $vendorName = $detail->vendor->name ?? 'N/A';
                                        $keperluan = $detail->keperluan ?? 'N/A';
                                        $paymentStage = $detail->payment_stage ? " | {$detail->payment_stage}" : '';
                                        $jumlah = number_format((int) $detail->jumlah_transfer, 0, ',', '.');

                                        return "{$vendorName} | {$keperluan}{$paymentStage} | Rp {$jumlah}";
                                    })
                                    ->reactive()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (! $state) {
                                            $set('vendor_id', null);
                                            $set('account_holder', null);
                                            $set('bank_name', null);
                                            $set('bank_account', null);
                                            $set('amount', null);
                                            $set('note', null);

                                            return;
                                        }

                                        $detail = NotaDinasDetail::with('vendor')->find($state);
                                        if ($detail && $detail->vendor) {
                                            $set('vendor_id', $detail->vendor_id);
                                            $set('account_holder', $detail->account_holder ?? $detail->vendor->account_holder);
                                            $set('bank_name', $detail->bank_name ?? $detail->vendor->bank_name);
                                            $set('bank_account', $detail->bank_account ?? $detail->vendor->bank_account);
                                            $set('amount', $detail->jumlah_transfer ?? 0);
                                            $set('note', $detail->keperluan ?? null);
                                        }
                                    })
                                    ->required()
                                    ->columnSpan(6),
                            ]),
                    ]),

                Hidden::make('no_nd')
                    ->dehydrated()
                    ->label('No. Nota Dinas'),
                Hidden::make('vendor_id'),

                Section::make('Detail Transfer')
                    ->schema([
                        Grid::make(6)
                            ->schema([
                                TextInput::make('bank_name')
                                    ->label('Bank')
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('account_holder')
                                    ->label('Nama Rekening')
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('bank_account')
                                    ->label('Nomor Rekening')
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('amount')
                                    ->label('Jumlah Transfer')
                                    ->prefix('Rp. ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->dehydrateStateUsing(fn ($state) => (int) str_replace([',', '.'], '', (string) $state))
                                    ->required()
                                    ->columnSpan(2),
                                DatePicker::make('date_expense')
                                    ->label('Tanggal Pengeluaran')
                                    ->default(now())
                                    ->required()
                                    ->columnSpan(2),
                                Select::make('kategori_transaksi')
                                    ->label('Tipe Transaksi')
                                    ->options([
                                        'uang_keluar' => 'Uang Keluar',
                                    ])
                                    ->default('uang_keluar')
                                    ->required()
                                    ->columnSpan(2),
                            ]),
                    ]),

                Section::make('Keterangan')
                    ->schema([
                        Textarea::make('note')
                            ->label('Catatan / Keperluan')
                            ->required()
                            ->rows(3),
                    ]),

                Section::make('Invoice / Bukti')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Invoice/Bukti')
                            ->directory('expense_wedding/'.date('Y/m'))
                            ->disk('public')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                            ->maxSize(5120),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('note')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['vendor', 'paymentMethod']))
            ->defaultSort('date_expense', 'desc')
            ->columns([
                TextColumn::make('date_expense')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable(),
                TextColumn::make('note')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('paymentMethod.name')
                    ->label('Metode')
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Pengeluaran')
                    ->modalWidth('7xl')
                    ->mutateDataUsing(function (array $data) {
                        if (! isset($data['payment_method_id']) || ! $data['payment_method_id']) {
                            return $data;
                        }

                        $pm = PaymentMethod::find($data['payment_method_id']);
                        if ($pm) {
                            $data['payment_stage'] = $pm->is_cash ? 'cash' : 'transfer';
                        }

                        if (blank($data['note'] ?? null) && filled($data['nota_dinas_detail_id'] ?? null)) {
                            $detail = NotaDinasDetail::find($data['nota_dinas_detail_id']);
                            $data['note'] = $detail?->keperluan ?? 'Expense';
                        }

                        return $data;
                    })
                    ->after(function () {
                        Notification::make()
                            ->success()
                            ->title('Pengeluaran ditambahkan')
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('7xl')
                    ->visible(function (Expense $record): bool {
                        Gate::authorize('update', $record);

                        return true;
                    }),
                DeleteAction::make()
                    ->visible(function (Expense $record): bool {
                        Gate::authorize('delete', $record);

                        return true;
                    }),
            ]);
    }
}
