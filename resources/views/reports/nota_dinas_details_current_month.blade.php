<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Transaksi Bulan Ini - {{ ($monthName ?? \Carbon\Carbon::now()->translatedFormat('F')).' '.($year ?? \Carbon\Carbon::now()->year) }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Noto Sans', 'sans-serif'] },
                }
            }
        }
    </script>
</head>
<body class="font-sans bg-gray-100 text-gray-900 antialiased">
    @php
        $from = \Carbon\Carbon::create(($year ?? \Carbon\Carbon::now()->year), ($month ?? \Carbon\Carbon::now()->month), 1)->toDateString();
        $until = \Carbon\Carbon::create(($year ?? \Carbon\Carbon::now()->year), ($month ?? \Carbon\Carbon::now()->month), 1)->endOfMonth()->toDateString();
        $totalItems = $details->count();
        $paidCount = $details->where('status_invoice', 'sudah_dibayar')->count();
        $waitingCount = $details->where('status_invoice', 'menunggu')->count();
        $unpaidCount = $details->where('status_invoice', 'belum_dibayar')->count();
    @endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18M7 6h10a2 2 0 012 2v8a2 2 0 01-2 2H7a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                    </svg>
                </span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Total Transaksi Bulan Ini</h1>
                    <div class="text-sm text-gray-600">Periode: {{ ($monthName ?? \Carbon\Carbon::now()->translatedFormat('F')).' '.($year ?? \Carbon\Carbon::now()->year) }}</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('filament.admin.resources.nota-dinas-details.index', ['tableFilters' => ['created_date_range' => ['from' => $from, 'until' => $until]], 'resetTableFilters' => true]) }}"
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white shadow hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
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
                                    <a href="{{ route('nota-dinas.preview-web', ['notaDinas' => $d->notaDinas->id]) }}" class="hover:underline">{{ $d->notaDinas->no_nd ?? '-' }}</a>
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
    <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10 text-xs text-gray-500">
        Dibuat: {{ now()->format('d-m-Y H:i') }} · {{ $companyName ?? config('app.name') }}
    </footer>
</body>
</html>
