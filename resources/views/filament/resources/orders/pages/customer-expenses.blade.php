<x-filament::page>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <h2 class="text-xl font-semibold">Pengeluaran Pelanggan - {{ $monthLabel }}</h2>
            <div class="flex flex-col md:flex-row md:items-end gap-4">
                <form method="GET" action="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('customer-expenses') }}" class="flex items-end gap-3">
                    <div class="flex items-end gap-3">
                        <div>
                            <label for="month" class="text-xs text-gray-500">Bulan</label>
                            <select id="month" name="month" class="fi-input w-28">
                                <option value="all" @selected($selectedMonth === 'all')>All</option>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @selected($m === (int) \Illuminate\Support\Str::after($selectedMonth, '-'))>
                                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label for="year" class="text-xs text-gray-500">Tahun</label>
                            <select id="year" name="year" class="fi-input w-24">
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" @selected($y === $selectedYear)>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="status" class="text-xs text-gray-500">Status</label>
                            <select id="status" name="status" class="fi-input w-36">
                                @foreach ($statusOptions as $opt)
                                    <option value="{{ $opt['value'] }}" @selected($opt['value'] === $status)>{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="fi-btn fi-btn-primary">Terapkan</button>
                    <a href="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('customer-expenses') }}" class="fi-btn">Reset</a>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-danger-50 border border-danger-200 rounded p-3 min-w-[180px]">
                <div class="text-xs text-danger-700">Jumlah Transaksi</div>
                <div class="text-lg font-semibold text-danger-900">{{ number_format($totals['count'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-warning-50 border border-warning-200 rounded p-3 min-w-[180px]">
                <div class="text-xs text-warning-700">Nominal</div>
                <div class="text-lg font-semibold text-warning-900">Rp. {{ number_format($totals['amount'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="overflow-x-auto bg-white shadow-sm border border-gray-200 rounded">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tgl Pengeluaran</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Metode</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Nominal</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($expenses as $exp)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                <x-filament::badge color="{{ $exp->order?->status?->getColor() }}">
                                    {{ $exp->order?->status?->getLabel() }}
                                </x-filament::badge>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                {{ $exp->order?->prospect?->name_event ?? $exp->order?->name }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">
                                {{ \Illuminate\Support\Carbon::parse($exp->date_expense)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                {{ $exp->paymentMethod?->is_cash ? 'Kas/Tunai' : ($exp->paymentMethod?->bank_name ?: $exp->paymentMethod?->name) }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 text-right">
                                Rp. {{ number_format($exp->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                {{ $exp->note ?? $exp->kategori_transaksi }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                Belum ada pengeluaran untuk {{ $monthLabel }} dengan status {{ $status }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>
