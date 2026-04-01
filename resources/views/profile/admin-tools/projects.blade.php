@extends('profile.layout')

@section('profile-page-title', 'Proyek Wedding')
@section('profile-page-subtitle', 'Daftar order/proyek wedding (khusus super admin)')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
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
                    <th class="py-3 pr-4">Nomor</th>
                    <th class="py-3 pr-4">Client</th>
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
                        <td class="py-3 pr-4 font-medium">
                            <a href="{{ route('profile.admin-tools.projects.show', $order) }}" class="text-blue-700 hover:underline">
                                {{ $order->name }}
                            </a>
                        </td>
                        <td class="py-3 pr-4 text-xs text-gray-700">
                            <div>{{ $order->number ?? '-' }}</div>
                            <div class="text-[11px] text-gray-500">{{ $order->no_kontrak ?? '' }}</div>
                        </td>
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ $order->prospect?->name ?? '-' }}</td>
                        <td class="py-3 pr-4 text-xs text-gray-700">
                            <div>{{ $order->employee?->name ?? '-' }}</div>
                            <div class="text-[11px] text-gray-500">{{ $order->user?->name ?? '' }}</div>
                        </td>
                        <td class="py-3 pr-4">
                            @php
                                $profit = (int) $order->laba_kotor;
                            @endphp
                            <span class="text-sm font-semibold {{ $profit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                Rp {{ number_format($profit, 0, ',', '.') }}
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
                                Detail
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
