@extends('profile.layout')

@section('profile-page-title', 'Produk Proyek Wedding')
@section('profile-page-subtitle', $order->name)

@section('profile-content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="text-xs text-gray-500">Nomor</div>
                <div class="text-sm font-semibold text-gray-900">{{ $order->number ?? '-' }}</div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('profile.admin-tools.projects.show', $order) }}" class="text-sm font-semibold text-blue-700 hover:underline">
                    Kembali ke proyek
                </a>
                <a href="{{ route('profile.admin-tools.projects') }}" class="text-sm font-semibold text-blue-700 hover:underline">
                    Kembali ke daftar
                </a>
            </div>
        </div>
    </div>

    @if(($products ?? collect())->isEmpty())
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
            <div class="text-sm text-gray-600">Produk belum ditemukan untuk proyek ini.</div>
        </div>
    @else
        @foreach($products as $product)
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs text-gray-500">Nama Produk</div>
                        <div class="text-lg font-semibold text-gray-900">{{ \Illuminate\Support\Str::title($product->name) }}</div>
                        <div class="mt-1 text-xs text-gray-500">Kategori</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $product->category?->name ?? '-' }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Harga Final</div>
                        <div class="text-lg font-bold text-gray-900">Rp {{ number_format((int) $product->price, 0, ',', '.') }}</div>
                        <div class="mt-2 text-xs text-gray-500">Total Publish</div>
                        <div class="text-sm font-semibold text-gray-900">Rp {{ number_format((int) $product->product_price, 0, ',', '.') }}</div>
                    </div>
                </div>

                @php
                    $discounts = $product->pengurangans ?? collect();
                    $additions = $product->penambahanHarga ?? collect();
                    $vendors = $product->items ?? collect();
                    $vendorsVendorTotal = (int) $vendors->sum('total_price');
                    $additionsVendorTotal = (int) $additions->sum('harga_vendor');
                    $vendorCostTotal = $vendorsVendorTotal + $additionsVendorTotal;
                    $finalPriceTotal = (int) ($product->price ?? 0);
                    $profitTotal = $finalPriceTotal - $vendorCostTotal;
                @endphp

                <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <div class="text-xs text-gray-500">Pengurangan</div>
                        <div class="text-lg font-bold text-gray-900">Rp {{ number_format((int) $product->pengurangan, 0, ',', '.') }}</div>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <div class="text-xs text-gray-500">Penambahan (Publish)</div>
                        <div class="text-lg font-bold text-gray-900">Rp {{ number_format((int) $product->penambahan_publish, 0, ',', '.') }}</div>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <div class="text-xs text-gray-500">Keuntungan</div>
                        <div class="text-lg font-bold {{ $profitTotal >= 0 ? 'text-emerald-700' : 'text-red-700' }}">Rp {{ number_format((int) $profitTotal, 0, ',', '.') }}</div>
                        <div class="mt-1 text-xs text-gray-500">Total Vendor</div>
                        <div class="font-semibold text-gray-900">Rp {{ number_format((int) $vendorCostTotal, 0, ',', '.') }}</div>
                        <div class="mt-1 text-xs text-gray-500">Total Publish (Final)</div>
                        <div class="font-semibold text-gray-900">Rp {{ number_format((int) $finalPriceTotal, 0, ',', '.') }}</div>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <div class="text-xs text-gray-500">Pax</div>
                        <div class="text-lg font-bold text-gray-900">{{ (int) ($product->pax ?? 0) }}</div>
                        <div class="mt-1 text-xs text-gray-500">Pax Akad</div>
                        <div class="font-semibold text-gray-900">{{ (int) ($product->pax_akad ?? 0) }}</div>
                    </div>
                </div>

                @if (!empty($product->free_pengurangan))
                    <div class="mt-6">
                        <div class="text-sm font-semibold text-gray-900 mb-2">Free</div>
                        <div class="prose max-w-none text-sm">
                            {!! $product->free_pengurangan !!}
                        </div>
                    </div>
                @endif

                @if($vendors->isNotEmpty())
                    <div class="mt-6">
                        <div class="text-sm font-semibold text-gray-900 mb-2">Vendor</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                                        <th class="py-3 pr-4">Vendor</th>
                                        <th class="py-3 pr-4">Qty</th>
                                        <th class="py-3 pr-4 text-right">Harga Publish</th>
                                        <th class="py-3 pr-4 text-right">Harga Vendor</th>
                                        <th class="py-3 pr-4 text-right">Keuntungan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @php
                                        $vendorsProfitTotal = 0;
                                    @endphp
                                    @foreach($vendors as $item)
                                        @php
                                            $qty = (int) ($item->quantity ?? 1);
                                            $profit = (((int) ($item->harga_publish ?? 0)) - ((int) ($item->harga_vendor ?? 0))) * $qty;
                                            $vendorsProfitTotal += $profit;
                                        @endphp
                                        <tr class="text-gray-800">
                                            <td class="py-3 pr-4 font-medium">{{ \Illuminate\Support\Str::title($item->vendor?->name ?? '-') }}</td>
                                            <td class="py-3 pr-4">{{ $qty }}</td>
                                            <td class="py-3 pr-4 text-right whitespace-nowrap">Rp {{ number_format((int) ($item->harga_publish ?? 0), 0, ',', '.') }}</td>
                                            <td class="py-3 pr-4 text-right whitespace-nowrap">Rp {{ number_format((int) ($item->harga_vendor ?? 0), 0, ',', '.') }}</td>
                                            <td class="py-3 pr-4 text-right whitespace-nowrap">Rp {{ number_format((int) $profit, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="border-t">
                                        <td class="py-3 pr-4 font-semibold text-gray-900" colspan="4">Total Keuntungan</td>
                                        <td class="py-3 pr-4 text-right font-semibold text-gray-900 whitespace-nowrap">Rp {{ number_format((int) $vendorsProfitTotal, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif

                @if($discounts->isNotEmpty())
                    <div class="mt-6">
                        <div class="text-sm font-semibold text-gray-900 mb-2">Pengurangan Item</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                                        <th class="py-3 pr-4">Nama</th>
                                        <th class="py-3 pr-4 text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($discounts as $disc)
                                        <tr class="text-gray-800">
                                            <td class="py-3 pr-4 font-medium">{{ \Illuminate\Support\Str::title($disc->description ?? '-') }}</td>
                                            <td class="py-3 pr-4 text-right whitespace-nowrap">Rp {{ number_format((int) ($disc->amount ?? 0), 0, ',', '.') }}</td>
                                        </tr>
                                        @if (!empty($disc->notes))
                                            <tr class="text-gray-700">
                                                <td class="pb-3 pr-4" colspan="2">{!! $disc->notes !!}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($additions->isNotEmpty())
                    <div class="mt-6">
                        <div class="text-sm font-semibold text-gray-900 mb-2">Penambahan Item</div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                                        <th class="py-3 pr-4">Vendor</th>
                                        <th class="py-3 pr-4 text-right">Harga Publish</th>
                                        <th class="py-3 pr-4 text-right">Harga Vendor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($additions as $add)
                                        <tr class="text-gray-800">
                                            <td class="py-3 pr-4 font-medium">{{ \Illuminate\Support\Str::title($add->vendor?->name ?? '-') }}</td>
                                            <td class="py-3 pr-4 text-right whitespace-nowrap">Rp {{ number_format((int) ($add->harga_publish ?? 0), 0, ',', '.') }}</td>
                                            <td class="py-3 pr-4 text-right whitespace-nowrap">Rp {{ number_format((int) ($add->harga_vendor ?? 0), 0, ',', '.') }}</td>
                                        </tr>
                                        @if (!empty($add->description))
                                            <tr class="text-gray-700">
                                                <td class="pb-3 pr-4" colspan="3">{!! $add->description !!}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    @endif
</div>
@endsection
