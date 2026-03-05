<x-filament::page>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <h2 class="text-xl font-semibold">Pembayaran Pelanggan - {{ $monthLabel }}</h2>
            <div class="flex flex-col md:flex-row md:items-end gap-4">
                <form method="GET" action="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('customer-payments') }}" class="flex items-end gap-3">
                    <div class="flex items-end gap-3">
                        <div>
                            <label for="month" class="text-xs text-gray-500">Bulan</label>
                            <select id="month" name="month" class="fi-input w-28">
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
                                @for ($y = now()->year + 1; $y >= now()->year - 5; $y--)
                                    <option value="{{ $y }}" @selected($y === (int) \Illuminate\Support\Str::before($selectedMonth, '-'))>{{ $y }}</option>
                                @endfor
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
                    <a href="{{ \App\Filament\Resources\Orders\OrderResource::getUrl('customer-payments') }}" class="fi-btn">Reset</a>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="bg-success-50 border border-success-200 rounded p-3 min-w-[180px]">
                <div class="text-xs text-success-700">Jumlah Transaksi</div>
                <div class="text-lg font-semibold text-success-900">{{ number_format($totals['count'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-info-50 border border-info-200 rounded p-3 min-w-[180px]">
                <div class="text-xs text-info-700">Nominal</div>
                <div class="text-lg font-semibold text-info-900">Rp. {{ number_format($totals['amount'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="overflow-x-auto bg-white shadow-sm border border-gray-200 rounded">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tgl Bayar</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Metode</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Nominal</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($payments as $pay)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                <x-filament::badge color="{{ $pay->order?->status?->getColor() }}">
                                    {{ $pay->order?->status?->getLabel() }}
                                </x-filament::badge>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                {{ $pay->order?->prospect?->name_event ?? $pay->order?->name }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-500">
                                {{ \Illuminate\Support\Carbon::parse($pay->tgl_bayar)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                {{ $pay->paymentMethod?->is_cash ? 'Kas/Tunai' : ($pay->paymentMethod?->bank_name ?: $pay->paymentMethod?->name) }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 text-right">
                                Rp. {{ number_format($pay->nominal, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                {{ $pay->keterangan }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                Belum ada pembayaran untuk {{ $monthLabel }} dengan status {{ $status }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>
