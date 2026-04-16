@extends('profile.layout')

@section('profile-page-title', 'Laporan Keuangan')
@section('profile-page-subtitle', 'Ringkasan pemasukan dan pengeluaran per bulan')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="text-lg font-semibold text-gray-900">Periode: {{ $selectedMonthLabel ?? '-' }}</div>
                <div class="text-sm text-gray-500">Gunakan filter bulan untuk melihat laporan per periode.</div>
            </div>
            <form method="GET" action="{{ route('profile.financial-report') }}" class="flex items-center gap-3">
                <label for="month" class="text-sm font-medium text-gray-700">Bulan</label>
                <select id="month" name="month"
                    class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                    onchange="this.form.submit()">
                    @foreach (($availableMonths ?? []) as $opt)
                        <option value="{{ $opt['value'] ?? '' }}" {{ ($selectedMonth ?? '') === ($opt['value'] ?? '') ? 'selected' : '' }}>
                            {{ $opt['label'] ?? ($opt['value'] ?? '-') }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-200 p-4">
                <div class="text-sm text-gray-500">Total Pemasukan</div>
                <div class="mt-1 text-xl font-bold text-emerald-600">
                    Rp {{ number_format((int) ($totalIncome ?? 0), 0, ',', '.') }}
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 p-4">
                <div class="text-sm text-gray-500">Total Pengeluaran</div>
                <div class="mt-1 text-xl font-bold text-rose-600">
                    Rp {{ number_format((int) ($totalExpense ?? 0), 0, ',', '.') }}
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 p-4">
                <div class="text-sm text-gray-500">Laba Kotor</div>
                @php $gp = (int) ($grossProfit ?? 0); @endphp
                <div class="mt-1 text-xl font-bold {{ $gp >= 0 ? 'text-blue-700' : 'text-rose-700' }}">
                    Rp {{ number_format($gp, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-emerald-50 border-b border-emerald-100">
                    <div class="font-semibold text-emerald-800">Pemasukan</div>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Keterangan</th>
                                <th class="py-2 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach (($incomeItems ?? []) as $row)
                                <tr>
                                    <td class="py-2 text-gray-800">{{ $row['label'] ?? '-' }}</td>
                                    <td class="py-2 text-right text-gray-800">
                                        Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="font-semibold">
                                <td class="py-3 text-gray-900">Total</td>
                                <td class="py-3 text-right text-emerald-700">
                                    Rp {{ number_format((int) ($totalIncome ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 bg-rose-50 border-b border-rose-100">
                    <div class="font-semibold text-rose-800">Pengeluaran</div>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-2">Keterangan</th>
                                <th class="py-2 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach (($expenseItems ?? []) as $row)
                                <tr>
                                    <td class="py-2 text-gray-800">{{ $row['label'] ?? '-' }}</td>
                                    <td class="py-2 text-right text-gray-800">
                                        Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="font-semibold">
                                <td class="py-3 text-gray-900">Total</td>
                                <td class="py-3 text-right text-rose-700">
                                    Rp {{ number_format((int) ($totalExpense ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
            <div class="text-sm text-gray-600">
                Laba kotor dihitung dari <span class="font-semibold">Total Pemasukan - Total Pengeluaran</span>.
            </div>
        </div>
    </div>
</div>
@endsection

