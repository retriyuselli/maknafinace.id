@extends('profile.layout')

@section('profile-page-title', 'Detail Proyek Wedding')
@section('profile-page-subtitle', $order->name)

@section('profile-content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs text-gray-500">Nomor</div>
                <div class="text-sm font-semibold text-gray-900">{{ $order->number ?? '-' }}</div>
                @if($order->no_kontrak)
                    <div class="mt-2 text-xs text-gray-500">No Kontrak</div>
                    <div class="text-sm font-semibold text-gray-900">{{ $order->no_kontrak }}</div>
                @endif
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-500">Status</div>
                @php
                    $status = $order->status?->value ?? (string) $order->status;
                @endphp
                <div class="inline-flex px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">
                    {{ $status !== '' ? $status : '-' }}
                </div>
            </div>
        </div>

        @php
            $totalPengeluaran = (int) $order->tot_pengeluaran;
            $grandTotal = (int) $order->grand_total;
            $profit = (int) $order->laba_kotor;
        @endphp

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <div class="text-xs text-gray-500">Client</div>
                <div class="font-semibold text-gray-900">{{ $order->prospect?->name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">PIC</div>
                <div class="font-semibold text-gray-900">{{ $order->employee?->name ?? '-' }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $order->user?->name ?? '' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Tanggal Closing</div>
                <div class="font-semibold text-gray-900">{{ $order->closing_date ? $order->closing_date->format('d M Y') : '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Created</div>
                <div class="font-semibold text-gray-900">{{ optional($order->created_at)->format('d M Y H:i') }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Total Pengeluaran</div>
                <div class="font-semibold text-gray-900">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Keuntungan (Laba Kotor)</div>
                <div class="font-semibold {{ $profit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                    Rp {{ number_format($profit, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Item Proyek</h3>
            <a href="{{ route('profile.admin-tools.projects') }}" class="text-sm font-semibold text-blue-700 hover:underline">
                Kembali ke daftar
            </a>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                        <th class="py-3 pr-4">Produk</th>
                        <th class="py-3 pr-4">Qty</th>
                        <th class="py-3 pr-4">Harga</th>
                        <th class="py-3 pr-4 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @php
                        $items = $order->items ?? collect();
                        $itemsSubtotal = 0;
                    @endphp
                    @foreach($items as $item)
                        @php
                            $subtotal = ((int) $item->quantity) * ((int) $item->unit_price);
                            $itemsSubtotal += $subtotal;
                        @endphp
                        <tr class="text-gray-800">
                            <td class="py-3 pr-4 font-medium">{{ $item->product_name ?? $item->product?->name ?? '-' }}</td>
                            <td class="py-3 pr-4">{{ (int) $item->quantity }}</td>
                            <td class="py-3 pr-4">Rp {{ number_format((int) $item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right">Rp {{ number_format((int) $subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t">
                        <td class="py-3 pr-4 font-semibold text-gray-900" colspan="3">Total Item</td>
                        <td class="py-3 pr-4 text-right font-semibold text-gray-900">Rp {{ number_format((int) $itemsSubtotal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                <div class="text-xs text-gray-500">Total Harga</div>
                <div class="text-lg font-bold text-gray-900">Rp {{ number_format((int) $order->total_price, 0, ',', '.') }}</div>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                <div class="text-xs text-gray-500">Grand Total</div>
                <div class="text-lg font-bold text-gray-900">Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
