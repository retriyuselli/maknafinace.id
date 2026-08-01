@extends('profile.layout')

@section('profile-page-title', 'Bank Statement (Failed)')
@section('profile-page-subtitle', 'Statement atau rekonsiliasi yang gagal diproses')

@section('profile-content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('profile.admin-tools.bank-statements') }}" class="text-sm font-semibold text-blue-700 hover:underline">
            Kembali
        </a>
        <a href="{{ route('profile.admin-tools.bank-statements.reconciliation') }}" class="text-sm font-semibold text-blue-700 hover:underline">
            Monitor Rekonsiliasi
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                        <th class="py-3 pr-4">Rekening</th>
                        <th class="py-3 pr-4">Periode</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 pr-4">Rekonsiliasi</th>
                        <th class="py-3 pr-4 text-right">Trx</th>
                        <th class="py-3 pr-4 text-right">Item</th>
                        <th class="py-3 pr-4 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($statements as $st)
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
                            </td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-1 rounded-full text-xs {{ $st->reconciliation_status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $st->reconciliation_status ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((int) $st->transactions_count) }}</td>
                            <td class="py-3 pr-4 text-right text-gray-700">{{ number_format((int) $st->reconciliation_items_count) }}</td>
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

        <div class="mt-4">
            {{ $statements->links() }}
        </div>
    </div>
</div>
@endsection

