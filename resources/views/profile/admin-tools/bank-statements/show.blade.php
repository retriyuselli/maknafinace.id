@extends('profile.layout')

@section('profile-page-title', 'Detail Bank Statement')
@section('profile-page-subtitle', 'Monitoring data statement, transaksi hasil parsing, dan item rekonsiliasi')

@section('profile-content')
@php
    $st = $bankStatement;
@endphp

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('profile.admin-tools.bank-statements') }}" class="text-sm font-semibold text-blue-700 hover:underline">
            Kembali
        </a>
        <a href="{{ route('profile.admin-tools.bank-statements.reconciliation') }}" class="text-sm font-semibold text-blue-700 hover:underline">
            Monitor Rekonsiliasi
        </a>
        <a href="{{ route('profile.admin-tools.bank-statements.failed') }}" class="text-sm font-semibold text-blue-700 hover:underline">
            Failed
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <div class="text-xs text-gray-500">Rekening</div>
                <div class="font-semibold text-gray-900">{{ $st->paymentMethod?->name ?? '-' }}</div>
                <div class="text-xs text-gray-500 mt-1">#{{ $st->id }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Periode</div>
                <div class="font-semibold text-gray-900">
                    {{ $st->period_start ? $st->period_start->format('d M Y') : '-' }} - {{ $st->period_end ? $st->period_end->format('d M Y') : '-' }}
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Status Parsing</div>
                <div class="inline-flex mt-1 px-3 py-1 rounded-full text-xs font-semibold {{ $st->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' }}">
                    {{ $st->status ?? '-' }}
                </div>
                <div class="text-xs text-gray-500 mt-2">Processed At</div>
                <div class="font-semibold text-gray-900">{{ $st->processed_at ? $st->processed_at->format('d M Y H:i') : '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Status Rekonsiliasi</div>
                <div class="inline-flex mt-1 px-3 py-1 rounded-full text-xs font-semibold {{ $st->reconciliation_status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' }}">
                    {{ $st->reconciliation_status ?? '-' }}
                </div>
                <div class="text-xs text-gray-500 mt-2">File Rekonsiliasi</div>
                <div class="font-semibold text-gray-900">{{ $st->reconciliation_original_filename ?? '-' }}</div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="text-[11px] text-gray-500">Total Trx (parse)</div>
                <div class="text-sm font-bold text-gray-900">{{ number_format((int) ($st->no_of_debit + $st->no_of_credit)) }}</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="text-[11px] text-gray-500">Total Debit</div>
                <div class="text-sm font-bold text-gray-900">{{ number_format((float) $st->tot_debit, 0, ',', '.') }}</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="text-[11px] text-gray-500">Total Kredit</div>
                <div class="text-sm font-bold text-gray-900">{{ number_format((float) $st->tot_credit, 0, ',', '.') }}</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="text-[11px] text-gray-500">Saldo Akhir</div>
                <div class="text-sm font-bold text-gray-900">{{ number_format((float) $st->closing_balance, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row sm:items-center gap-3">
            @if($st->file_path)
                <a href="{{ route('bank-statements.download', $st) }}"
                    class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-50 text-blue-700 text-sm font-semibold hover:bg-blue-100 transition">
                    Download Statement
                </a>
            @endif
            @if($st->reconciliation_file)
                <a href="{{ route('bank-statements.reconciliation.download', $st) }}"
                    class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-semibold hover:bg-emerald-100 transition">
                    Download Rekonsiliasi
                </a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <h3 class="text-lg font-semibold text-gray-900">Transaksi Hasil Parsing (50 Terbaru)</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                        <th class="py-3 pr-4">Tanggal</th>
                        <th class="py-3 pr-4">Deskripsi</th>
                        <th class="py-3 pr-4 text-right">Debit</th>
                        <th class="py-3 pr-4 text-right">Kredit</th>
                        <th class="py-3 pr-4 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($transactions as $tx)
                        <tr class="text-xs text-gray-800">
                            <td class="py-3 pr-4 text-gray-700">{{ $tx->transaction_date ? $tx->transaction_date->format('d M Y') : '-' }}</td>
                            <td class="py-3 pr-4 text-gray-700">{{ $tx->description ?? '-' }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((float) $tx->debit_amount, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((float) $tx->credit_amount, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((float) $tx->balance, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-6 text-sm text-gray-600" colspan="5">Belum ada transaksi hasil parsing.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <h3 class="text-lg font-semibold text-gray-900">Item Rekonsiliasi (50 Terbaru)</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                        <th class="py-3 pr-4">Tanggal</th>
                        <th class="py-3 pr-4">Deskripsi</th>
                        <th class="py-3 pr-4 text-right">Debit</th>
                        <th class="py-3 pr-4 text-right">Kredit</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($reconciliationItems as $it)
                        <tr class="text-xs text-gray-800">
                            <td class="py-3 pr-4 text-gray-700">{{ $it->date ? $it->date->format('d M Y') : '-' }}</td>
                            <td class="py-3 pr-4 text-gray-700">{{ $it->description ?? '-' }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((float) $it->debit, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((float) $it->credit, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-6 text-sm text-gray-600" colspan="4">Belum ada item rekonsiliasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
