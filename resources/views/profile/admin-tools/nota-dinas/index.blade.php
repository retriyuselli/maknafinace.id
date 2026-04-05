@extends('profile.layout')

@section('profile-page-title', 'Nota Dinas')
@section('profile-page-subtitle', 'Monitoring Nota Dinas dan status pembayarannya (khusus super admin)')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6 space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Total Nota Dinas</div>
            <div class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($notaDinasCount) }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Total Detail</div>
            <div class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($detailsCount) }}</div>
            <div class="mt-1 text-xs text-gray-600">{{ number_format($detailsPaidCount) }} sudah dibayar</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Total Jumlah Transfer (Detail)</div>
            <div class="mt-1 text-lg font-semibold text-gray-900">Rp {{ number_format((float) $detailsSum, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach($statusSummary as $s => $c)
            <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs">
                {{ $s ?? '-' }}: {{ number_format((int) $c) }}
            </span>
        @endforeach
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari no_nd / hal / catatan" class="text-xs border border-gray-200 w-full rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">

        <select name="status" class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
            <option value="">Semua Status</option>
            @foreach(['draft','diajukan','disetujui','dibayar','ditolak'] as $opt)
                <option value="{{ $opt }}" @selected($status === $opt)>{{ ucfirst($opt) }}</option>
            @endforeach
        </select>

        <input type="month" name="month" value="{{ $month }}"
            class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">

        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Terapkan</button>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                    <th class="py-3 pr-4">Tanggal</th>
                    <th class="py-3 pr-4">No ND</th>
                    <th class="py-3 pr-4">Hal</th>
                    <th class="py-3 pr-4">Status</th>
                    <th class="py-3 pr-4">Detail</th>
                    <th class="py-3 pr-4">Total Detail</th>
                    <th class="py-3 pr-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($notaDinas as $nd)
                    <tr class="text-gray-800">
                        <td class="py-3 pr-4 text-xs text-gray-600">{{ optional($nd->tanggal)->format('d-m-Y') ?? '-' }}</td>
                        <td class="py-3 pr-4 text-xs">{{ $nd->no_nd }}</td>
                        <td class="py-3 pr-4 text-xs">{{ $nd->hal }}</td>
                        <td class="py-3 pr-4">
                            <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-xs">{{ $nd->status ?? '-' }}</span>
                        </td>
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ number_format((int) ($nd->details_paid_count ?? 0)) }}</td>
                        <td class="py-3 pr-4">
                            <div class="text-xs text-gray-700">{{ number_format((int) ($nd->details_count ?? 0)) }} item</div>
                            <div class="text-xs text-gray-500">{{ number_format((float) ($nd->details_sum ?? 0), 0, ',', '.') }}</div>
                        </td>
                        <td class="py-3 pr-4">
                            <a href="{{ route('profile.admin-tools.nota-dinas.show', $nd) }}"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800 transition">
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-sm text-gray-500">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $notaDinas->links() }}
    </div>
</div>
@endsection
