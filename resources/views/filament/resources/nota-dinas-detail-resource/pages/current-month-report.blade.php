<x-filament-panels::page>
    @php
        $from = \Carbon\Carbon::create(($year ?? \Carbon\Carbon::now()->year), ($month ?? \Carbon\Carbon::now()->month), 1)->toDateString();
        $until = \Carbon\Carbon::create(($year ?? \Carbon\Carbon::now()->year), ($month ?? \Carbon\Carbon::now()->month), 1)->endOfMonth()->toDateString();
        $totalItems = $details->count();
        $paidCount = $details->where('status_invoice', 'sudah_dibayar')->count();
        $waitingCount = $details->where('status_invoice', 'menunggu')->count();
        $unpaidCount = $details->where('status_invoice', 'belum_dibayar')->count();
    @endphp
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50 text-blue-600">
                    <x-heroicon-o-banknotes class="w-6 h-6" />
                </span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Total Transaksi Bulan Ini</h1>
                    <div class="text-sm text-gray-600">Periode: {{ ($monthName ?? \Carbon\Carbon::now()->translatedFormat('F')).' '.($year ?? \Carbon\Carbon::now()->year) }}</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('filament.admin.resources.nota-dinas-details.index', ['tableFilters' => ['created_date_range' => ['from' => $from, 'until' => $until]], 'resetTableFilters' => true]) }}"
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-primary-600 text-white shadow hover:bg-primary-700">
                    <x-heroicon-o-table-cells class="w-5 h-5" />
                    Lihat di Tabel
                </a>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-sm text-gray-600">Periode</div>
                <div class="mt-1 text-lg font-semibold text-gray-900">{{ ($monthName ?? \Carbon\Carbon::now()->translatedFormat('F')).' '.($year ?? \Carbon\Carbon::now()->year) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-sm text-gray-600">Total jumlah_transfer</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ 'Rp '.number_format((int) ($total ?? 0), 0, ',', '.') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-sm text-gray-600">Total Detail</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format((int) ($totalItems ?? 0), 0, ',', '.') }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="text-sm text-gray-600">Status Invoice</div>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full bg-green-100 text-green-800 px-2 py-1 text-xs font-medium">Sudah dibayar: {{ $paidCount }}</span>
                    <span class="inline-flex items-center rounded-full bg-yellow-100 text-yellow-800 px-2 py-1 text-xs font-medium">Menunggu: {{ $waitingCount }}</span>
                    <span class="inline-flex items-center rounded-full bg-red-100 text-red-800 px-2 py-1 text-xs font-medium">Belum dibayar: {{ $unpaidCount }}</span>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">No. ND</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Keperluan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Event/Order</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Jumlah Transfer</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status Invoice</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($details as $d)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-blue-600 whitespace-nowrap">
                                    @if ($d->notaDinas?->id)
                                        <a href="{{ route('nota-dinas.preview-web', ['notaDinas' => $d->notaDinas->id]) }}" target="_blank" class="hover:underline">{{ $d->notaDinas->no_nd ?? '-' }}</a>
                                    @else
                                        {{ $d->notaDinas->no_nd ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">{{ $d->vendor->name ?? '-' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $d->keperluan ?? '-' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    @if ($d->jenis_pengeluaran === 'wedding')
                                        {{ $d->order->name ?? '-' }}
                                    @else
                                        {{ $d->event ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900 text-right font-semibold whitespace-nowrap">{{ 'Rp '.number_format((int) ($d->jumlah_transfer ?? 0), 0, ',', '.') }}</td>
                                <td class="px-4 py-2 text-xs">
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                        @switch($d->status_invoice)
                                            @case('sudah_dibayar') bg-green-100 text-green-800 @break
                                            @case('menunggu') bg-yellow-100 text-yellow-800 @break
                                            @case('belum_dibayar') bg-red-100 text-red-800 @break
                                            @default bg-gray-100 text-gray-800
                                        @endswitch
                                    ">
                                        {{ str_replace('_',' ', $d->status_invoice ?? '-') }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ optional($d->created_at)->format('d-m-Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-600">Tidak ada data untuk periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-sm font-medium text-gray-700">Total</td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-gray-900 whitespace-nowrap">{{ 'Rp '.number_format((int) ($total ?? 0), 0, ',', '.') }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
