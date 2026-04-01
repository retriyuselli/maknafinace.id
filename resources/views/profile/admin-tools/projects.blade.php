@extends('profile.layout')

@section('profile-page-title', 'Proyek Wedding')
@section('profile-page-subtitle', 'Daftar order/proyek wedding (khusus super admin)')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div class="inline-flex rounded-xl overflow-hidden border border-gray-200">
            <a href="{{ request()->fullUrlWithQuery(['period' => 'all', 'month' => null]) }}"
            class="px-4 py-2 text-xs font-semibold {{ ($period ?? 'all') === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' }}">
                Semua
            </a>
            <a href="{{ request()->fullUrlWithQuery(['period' => 'year', 'month' => null]) }}"
            class="px-4 py-2 text-xs font-semibold border-l border-gray-200 {{ ($period ?? 'all') === 'year' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' }}">
                Tahun berjalan
            </a>
            <a href="{{ request()->fullUrlWithQuery(['period' => 'month', 'month' => null]) }}"
            class="px-4 py-2 text-xs font-semibold border-l border-gray-200 {{ ($period ?? 'all') === 'month' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700' }}">
                Bulan berjalan
            </a>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="period" value="custom">
            @php
                $monthOptions = collect(range(0, 23))
                    ->map(fn ($i) => now()->startOfMonth()->subMonths($i))
                    ->reverse()
                    ->map(fn ($d) => [
                        'value' => $d->format('Y-m'),
                        'label' => $d->locale('id')->translatedFormat('F Y'),
                    ]);
            @endphp
            <select name="month"
                class="h-10 border border-gray-200 rounded-lg px-3 text-xs focus:outline-none focus:ring-2 focus:ring-blue-200">
                <option value="">Pilih bulan</option>
                @foreach($monthOptions as $opt)
                    <option value="{{ $opt['value'] }}" @selected(($month ?? '') === $opt['value'])>
                        {{ $opt['label'] }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="h-10 px-3 rounded-lg bg-blue-600 text-white text-xs font-semibold shrink-0">Terapkan</button>
        </form>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-[11px] text-gray-500">Total Proyek</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format((int) $projectsCount) }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-[11px] text-gray-500">Nilai Proyek</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format((int) $grandTotalSum, 0, ',', '.') }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-[11px] text-gray-500">Pengeluaran</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format((int) $expensesSum, 0, ',', '.') }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-[11px] text-gray-500">Keuntungan</div>
            <div class="text-sm font-bold {{ (int) $profitSum >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                {{ number_format((int) $profitSum, 0, ',', '.') }}
            </div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-[11px] text-gray-500">Rata-rata</div>
            <div class="text-sm font-bold {{ (int) $profitAvg >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                {{ number_format((int) $profitAvg, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <form method="GET" class="flex items-center gap-3 mb-4">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / nomor / no kontrak"
            class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Cari</button>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                    <th class="py-3 pr-4">Nama</th>
                    <th class="py-3 pr-4">PIC</th>
                    <th class="py-3 pr-4">Keuntungan</th>
                    <th class="py-3 pr-4">Status</th>
                    <th class="py-3 pr-4">Created</th>
                    <th class="py-3 pr-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($projects as $order)
                    <tr class="text-gray-800">
                        <td class="py-3 pr-4 text-xs font-medium">
                            <a href="{{ route('profile.admin-tools.projects.show', $order) }}" class="text-blue-700 hover:underline">
                                {{ $order->name }}
                            </a>
                        </td>
                        <td class="py-3 pr-4 text-xs text-gray-700">
                            <div>{{ $order->employee?->name ?? '-' }}</div>
                            <div class="text-[11px] text-gray-500">{{ $order->user?->name ?? '' }}</div>
                        </td>
                        <td class="py-3 pr-4">
                            @php
                                $profit = (int) $order->laba_kotor;
                            @endphp
                            <span class="text-xs font-semibold {{ $profit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ number_format($profit, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="py-3 pr-4">
                            @php
                                $status = $order->status?->value ?? (string) $order->status;
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                {{ $status !== '' ? $status : '-' }}
                            </span>
                        </td>
                        <td class="py-3 pr-4 text-xs text-gray-600">{{ optional($order->created_at)->diffForHumans() }}</td>
                        <td class="py-3 pr-4 text-right">
                            <a href="{{ route('profile.admin-tools.projects.show', $order) }}"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold hover:bg-blue-100 transition">
                                View
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $projects->links() }}
    </div>
</div>
@endsection
