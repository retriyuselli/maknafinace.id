<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h2 class="text-xl font-semibold text-gray-900">{{ $summary['title'] ?? 'Detail Nilai' }}</h2>
            <p class="text-sm text-gray-600 mt-1">Periode: {{ $summary['period_label'] ?? '-' }}</p>

            <div class="mt-4 rounded-lg border border-gray-200 p-4">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="metric" value="{{ $summary['active_metric'] ?? request('metric', 'income') }}">
                    <input type="hidden" name="period" value="{{ $summary['active_period'] ?? request('period', 'current') }}">

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Dari Tanggal</label>
                        <input
                            type="date"
                            name="start_date"
                            value="{{ $summary['start_date'] ?? request('start_date') }}"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
                        >
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Sampai Tanggal</label>
                        <input
                            type="date"
                            name="end_date"
                            value="{{ $summary['end_date'] ?? request('end_date') }}"
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm"
                        >
                    </div>

                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        Terapkan Filter
                    </button>

                    <a
                        href="{{ route('filament.admin.resources.bank-statements.cashflow-detail', ['metric' => ($summary['active_metric'] ?? request('metric', 'income')), 'period' => ($summary['active_period'] ?? request('period', 'current'))]) }}"
                        class="text-sm text-gray-600 hover:text-gray-800"
                    >
                        Reset ke Periode Widget
                    </a>
                </form>

                <div class="mt-3 flex flex-wrap gap-2">
                    <a
                        href="{{ route('filament.admin.resources.bank-statements.cashflow-detail', ['metric' => ($summary['active_metric'] ?? request('metric', 'income')), 'period' => 'current']) }}"
                        class="inline-flex items-center px-3 py-1.5 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 text-xs"
                    >
                        Bulan Berjalan
                    </a>
                    <a
                        href="{{ route('filament.admin.resources.bank-statements.cashflow-detail', ['metric' => ($summary['active_metric'] ?? request('metric', 'income')), 'period' => 'previous']) }}"
                        class="inline-flex items-center px-3 py-1.5 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 text-xs"
                    >
                        Bulan Lalu
                    </a>
                </div>
            </div>

            <div class="mt-4 p-4 rounded-lg bg-blue-50 border border-blue-200">
                <p class="text-sm text-blue-700">Total</p>
                <p class="text-2xl font-bold text-blue-900">
                    Rp {{ number_format((int) ($summary['total'] ?? 0), 0, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Breakdown Sumber Data</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sumber</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                            <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                            <th class="px-4 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($summary['sources'] ?? []) as $source)
                            <tr>
                                <td class="px-4 sm:px-6 py-4 text-sm font-medium text-gray-900">{{ $source['name'] ?? '-' }}</td>
                                <td class="px-4 sm:px-6 py-4 text-sm text-gray-600">{{ $source['description'] ?? '-' }}</td>
                                <td class="px-4 sm:px-6 py-4 text-sm text-right font-semibold text-gray-900">
                                    Rp {{ number_format((int) ($source['value'] ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-sm text-center">
                                    @if(!empty($source['detail_url']))
                                        <a href="{{ $source['detail_url'] }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                                            Lihat Data
                                        </a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 sm:px-6 py-8 text-center text-sm text-gray-500">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
