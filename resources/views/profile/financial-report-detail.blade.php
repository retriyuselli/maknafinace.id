@extends('profile.layout')

@section('profile-page-title', 'Detail Laporan Keuangan')
@section('profile-page-subtitle', ($typeLabel ?? '-') . ' • ' . ($selectedMonthLabel ?? '-'))

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="text-lg font-semibold text-gray-900">{{ $typeLabel ?? '-' }}</div>
                <div class="text-sm text-gray-500">Periode: {{ $selectedMonthLabel ?? '-' }}</div>
            </div>
            <a href="{{ route('profile.financial-report', ['month' => $selectedMonth ?? now()->format('Y-m')]) }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <div class="rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">Total</div>
            <div class="mt-1 text-xl font-bold {{ ($kind ?? 'income') === 'income' ? 'text-emerald-700' : 'text-rose-700' }}">
                Rp {{ number_format((int) ($total ?? 0), 0, ',', '.') }}
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                <div class="font-semibold text-gray-800">Rincian</div>
            </div>
            <div class="p-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-2 w-28">Tanggal</th>
                            <th class="py-2 w-56">Nama</th>
                            <th class="py-2 text-center">Keterangan</th>
                            <th class="py-2 w-40 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse (($rows ?? []) as $row)
                            <tr>
                                <td class="py-2 text-gray-700">{{ $row['date'] ?? '-' }}</td>
                                <td class="py-2 text-gray-700">{{ $row['prospect'] ?? '-' }}</td>
                                <td class="py-2 text-center text-gray-800">{{ $row['description'] ?? '-' }}</td>
                                <td class="py-2 text-right text-gray-800">
                                    Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-gray-500">Tidak ada data pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
