@extends('profile.layout')

@section('profile-page-title', 'Bank Statement')
@section('profile-page-subtitle', 'Monitoring rekening koran dan status rekonsiliasi (read-only)')

@section('profile-content')
<div class="space-y-6">
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
            <div class="text-[11px] text-gray-500">Total</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format($totalCount) }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
            <div class="text-[11px] text-gray-500">Pending</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format($pendingCount) }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
            <div class="text-[11px] text-gray-500">Processing</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format($processingCount) }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
            <div class="text-[11px] text-gray-500">Parsed</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format($parsedCount) }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
            <div class="text-[11px] text-gray-500">Failed</div>
            <div class="text-sm font-bold text-red-700">{{ number_format($failedCount) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
            <div class="text-[11px] text-gray-500">Rekonsiliasi Uploaded</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format($reconUploadedCount) }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
            <div class="text-[11px] text-gray-500">Rekonsiliasi Processing</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format($reconProcessingCount) }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
            <div class="text-[11px] text-gray-500">Rekonsiliasi Completed</div>
            <div class="text-sm font-bold text-gray-900">{{ number_format($reconCompletedCount) }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
            <div class="text-[11px] text-gray-500">Rekonsiliasi Failed</div>
            <div class="text-sm font-bold text-red-700">{{ number_format($reconFailedCount) }}</div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <a href="{{ route('profile.admin-tools.bank-statements.failed') }}"
            class="inline-flex items-center px-4 py-2 rounded-xl bg-red-50 text-red-700 text-sm font-semibold hover:bg-red-100 transition">
            Lihat yang Failed
        </a>
        <a href="{{ route('profile.admin-tools.bank-statements.reconciliation') }}"
            class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-semibold hover:bg-emerald-100 transition">
            Lihat Rekonsiliasi
        </a>
        <a href="{{ route('profile.admin-tools.bank-statements.guide') }}"
            class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-50 text-blue-700 text-sm font-semibold hover:bg-blue-100 transition">
            Panduan
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <h3 class="text-lg font-semibold text-gray-900">Ringkasan 12 Bulan Terakhir</h3>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                        <th class="py-3 pr-4">Bulan</th>
                        <th class="py-3 pr-4 text-right">Statement</th>
                        <th class="py-3 pr-4 text-right">Debit</th>
                        <th class="py-3 pr-4 text-right">Kredit</th>
                        <th class="py-3 pr-4 text-right">Failed</th>
                        <th class="py-3 pr-4 text-right">Recon Failed</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($monthlySummary as $row)
                        @php
                            $monthLabel = $row->ym;
                            try {
                                $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $row->ym)->locale('id')->translatedFormat('F Y');
                            } catch (\Throwable $e) {
                                $monthLabel = $row->ym;
                            }
                        @endphp
                        <tr class="text-xs text-gray-800">
                            <td class="py-3 pr-4 font-semibold text-gray-900">{{ $monthLabel }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((int) $row->statements_count) }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((float) $row->tot_debit_sum, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((float) $row->tot_credit_sum, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right {{ (int) $row->failed_count > 0 ? 'text-red-700 font-semibold' : 'text-gray-700' }}">
                                {{ number_format((int) $row->failed_count) }}
                            </td>
                            <td class="py-3 pr-4 text-right {{ (int) $row->recon_failed_count > 0 ? 'text-red-700 font-semibold' : 'text-gray-700' }}">
                                {{ number_format((int) $row->recon_failed_count) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-6 text-sm text-gray-600" colspan="6">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Statement Terbaru</h3>
            <a href="{{ route('profile.admin-tools.bank-statements.reconciliation') }}" class="text-sm font-semibold text-blue-700 hover:underline">
                Monitor Rekonsiliasi
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                        <th class="py-3 pr-4">Rekening</th>
                        <th class="py-3 pr-4">Periode</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 pr-4 text-right">Debit</th>
                        <th class="py-3 pr-4 text-right">Kredit</th>
                        <th class="py-3 pr-4 text-right">Saldo Akhir</th>
                        <th class="py-3 pr-4">Rekonsiliasi</th>
                        <th class="py-3 pr-4 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($latestStatements as $st)
                        <tr class="text-xs text-gray-800">
                            <td class="py-3 pr-4">
                                <div class="font-semibold text-gray-900">{{ $st->paymentMethod?->name ?? '-' }}</div>
                                <div class="text-[11px] text-gray-500">#{{ $st->id }}</div>
                            </td>
                            <td class="py-3 pr-4 text-gray-700">
                                <div>{{ $st->period_start ? $st->period_start->format('d M Y') : '-' }}</div>
                                <div class="text-[11px] text-gray-500">{{ $st->period_end ? $st->period_end->format('d M Y') : '-' }}</div>
                            </td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-1 rounded-full text-xs {{ $st->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $st->status ?? '-' }}
                                </span>
                                <div class="text-[11px] text-gray-500 mt-1">
                                    trx: {{ number_format((int) $st->transactions_count) }}
                                </div>
                            </td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((float) $st->tot_debit, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((float) $st->tot_credit, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((float) $st->closing_balance, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-1 rounded-full text-xs {{ $st->reconciliation_status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $st->reconciliation_status ?? '-' }}
                                </span>
                                <div class="text-[11px] text-gray-500 mt-1">
                                    item: {{ number_format((int) $st->reconciliation_items_count) }}
                                </div>
                            </td>
                            <td class="py-3 pr-4 text-right">
                                <a href="{{ route('profile.admin-tools.bank-statements.show', $st) }}"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-semibold hover:bg-blue-100 transition">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
