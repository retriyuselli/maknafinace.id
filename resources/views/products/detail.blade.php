@extends('layouts.app')

@section('title', 'Detail Produk - ' . $product->name)

@section('content')
    <div class="min-h-screen bg-gray-50">
        @include('front.header')

        <section class="bg-gradient-to-br from-blue-600 to-purple-700 text-white py-12 md:py-16"
            style="background: linear-gradient(to bottom right, #2563eb, #7e22ce);">
            <div class="max-w-4xl mx-auto px-4 text-left">
                <h1 class="text-3xl md:text-4xl font-bold mb-3">{{ $product->name }}</h1>
                <p class="text-xs md:text-sm text-blue-100 max-auto">
                    Detail paket lengkap termasuk komponen vendor, harga publish, dan harga vendor untuk produk ini.
                </p>
            </div>
        </section>

        <section class="pt-8 pb-16 md:pt-12 md:pb-24">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <a href="{{ route('product') }}"
                        class="inline-flex items-center text-sm text-gray-600 hover:text-blue-600">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Kembali ke daftar produk
                    </a>
                </div>

                <div
                    class="max-w-3xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 px-6 py-6 md:px-8 md:py-8">
                    <div class="flex flex-col">
                        @if ($product->category)
                            <div class="mb-4">
                                <p class="text-xs uppercase tracking-wide text-blue-600">
                                    {{ $product->category->name }}
                                </p>
                            </div>
                        @endif

                        @php
                            $totalPublicPrice = ($product->items ?? collect())->sum(function ($item) {
                                return ($item->harga_publish ?? 0) * ($item->quantity ?? 1);
                            });

                            $totalVendorPrice = ($product->items ?? collect())->sum(function ($item) {
                                return ($item->harga_vendor ?? 0) * ($item->quantity ?? 1);
                            });
                        @endphp

                        <div class="mb-6">
                            <p class="text-gray-500 mb-1">
                                Harga paket
                            </p>
                            <p class="text-2xl md:text-3xl font-bold text-blue-600 mb-3">
                                Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}
                            </p>

                            <div class="grid grid-cols-2 gap-4 text-xs text-gray-500">
                                <div>
                                    <p class="mb-1">Total publish (sebelum diskon/penambahan)</p>
                                    <p class="font-semibold">
                                        Rp {{ number_format($totalPublicPrice, 0, ',', '.') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="mb-1">Total vendor</p>
                                    <p class="font-semibold">
                                        Rp {{ number_format($totalVendorPrice, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if ($product->description)
                            <div class="mb-8">
                                <h2 class="text-base font-semibold text-gray-900 mb-2">
                                    Deskripsi
                                </h2>
                                <p class="text-gray-700 leading-relaxed">
                                    {{ $product->description }}
                                </p>
                            </div>
                        @endif

                        @if ($product->items && $product->items->count())
                            <div class="mt-6">
                                <h2 class="text-base font-semibold text-gray-900 mb-2">
                                    Komponen vendor dalam produk ini
                                </h2>

                                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col"
                                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500">
                                                    Vendor
                                                </th>
                                                <th scope="col"
                                                    class="px-4 py-2 text-right text-xs font-medium text-gray-500">
                                                    Publish
                                                </th>
                                                <th scope="col"
                                                    class="px-4 py-2 text-right text-xs font-medium text-gray-500">
                                                    Vendor
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($product->items as $item)
                                                <tr>
                                                    <td class="px-4 py-2 align-top">
                                                        <p class="text-xs text-gray-800 leading-relaxed">
                                                            {{ $item->vendor ? \Illuminate\Support\Str::title(strtolower($item->vendor->name)) : '-' }}
                                                        </p>
                                                        @if ($item->vendor && $item->vendor->category)
                                                            <p class="text-xs text-gray-500">
                                                                {{ $item->vendor->category->name }}
                                                            </p>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-right text-xs text-gray-800">
                                                        Rp {{ number_format($item->harga_publish ?? 0, 0, ',', '.') }}
                                                    </td>
                                                    <td class="px-4 py-2 text-right text-xs text-gray-800">
                                                        Rp {{ number_format($item->harga_vendor ?? 0, 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @include('front.footer')
    </div>
@endsection
