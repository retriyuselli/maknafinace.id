<x-filament::page>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <h2 class="text-xl font-semibold">Closing Bulan - {{ $monthLabel }}</h2>
            <div class="flex flex-col md:flex-row md:items-end gap-4">
                <form method="GET" action="{{ route('filament.admin.resources.orders.view-closing') }}" class="flex items-end gap-3">
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
                                    <option value="{{ $y }}" @selected($y === (int) \Illuminate\Support\Str::before($selectedMonth, '-'))>{{ $y }}</option>
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
                    <a href="{{ route('filament.admin.resources.orders.view-closing') }}" class="fi-btn">Reset</a>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 w-full">
            <div class="bg-success-50 border border-success-200 rounded-lg px-4 py-3">
                <div class="text-xs text-success-700">Pendapatan</div>
                <div class="text-lg font-semibold text-success-900">{{ number_format($totals['revenue'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-info-50 border border-info-200 rounded-lg px-4 py-3">
                <div class="text-xs text-info-700">Dibayar</div>
                <div class="text-lg font-semibold text-info-900">{{ number_format($totals['paid'], 0, ',', '.') }}</div>
            </div>
            <div class="bg-danger-50 border border-danger-200 rounded-lg px-4 py-3">
                <div class="text-xs text-danger-700">Sisa</div>
                <div class="text-lg font-semibold text-danger-900">{{ number_format($totals['remaining'], 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="overflow-x-auto bg-white shadow-sm border border-gray-200 rounded">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama Acara</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Closing</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Grand Total</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Dibayar</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Sisa</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">
                                <x-filament::badge color="{{ $order->status?->getColor() }}">
                                    {{ $order->status?->getLabel() }}
                                </x-filament::badge>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $order->prospect?->name_event }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ \Illuminate\Support\Carbon::parse($order->closing_date)->format('d M Y') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ number_format($order->grand_total, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ number_format($order->bayar, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ number_format($order->sisa, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada closing untuk {{ $monthLabel }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament::page>
