@extends('layouts.app')

@section('title', 'Produk')

@section('content')
    <div class="min-h-screen bg-white">
        @include('front.header')

        <section class="bg-gradient-to-br from-blue-600 to-purple-700 text-white py-12 md:py-16"
            style="background: linear-gradient(to bottom right, #2563eb, #7e22ce);">
            <div class="max-w-7xl mx-auto px-4 text-left">
                <nav class="mb-3 text-sm">
                    <ol class="flex items-center space-x-2 text-blue-100">
                        <li><a href="{{ route('home') }}" class="hover:text-white">Home</a></li>
                        <li><span class="mx-1">/</span></li>
                        <li class="text-white font-medium">Produk</li>
                    </ol>
                </nav>

                <h1 class="text-3xl md:text-4xl font-bold mb-3">Paket Produk</h1>
                <p class="text-sm md:text-lg text-blue-100 max-auto">
                    Pilih paket produk yang sesuai dengan kebutuhan acara Anda. Semua paket dirancang untuk
                    memudahkan pengelolaan event secara profesional.
                </p>
            </div>
        </section>

        <section class="pt-8 pb-16 md:pt-12 md:pb-24">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">

                <div class="max-w-xl mx-auto mb-10">
                    <form action="{{ route('product') }}" method="GET" class="flex gap-3">
                        <div class="relative flex-1">
                            <input type="text" name="q" id="q" value="{{ $search }}"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm px-4 py-2.5"
                                placeholder="Cari produk">
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-4.35-4.35M16.65 16.65A7.5 7.5 0 1010 17.5a7.5 7.5 0 006.65-10.85z" />
                                </svg>
                            </div>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cari
                        </button>
                    </form>
                </div>

                @if ($products->isEmpty())
                    <div class="max-w-2xl mx-auto text-center text-gray-500">
                        Belum ada produk yang tersedia.
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($products as $product)
                            <a href="{{ route('products.show', $product) }}"
                                class="border border-gray-200 rounded-xl hover:shadow-md transition-shadow duration-200 bg-white flex flex-col group">
                                @if ($product->image)
                                    <div class="aspect-[4/3] w-full overflow-hidden rounded-t-xl bg-gray-100">
                                        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                            class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    </div>
                                @endif

                                <div class="px-3 pb-4 pt-3 flex flex-col flex-1">
                                    <h2
                                        class="text-[11px] font-semibold tracking-wide text-gray-900 leading-snug group-hover:text-red-600">
                                        {{ \Illuminate\Support\Str::title(strtolower($product->name)) }}
                                        @if ($product->pax)
                                            _{{ $product->pax }} pax
                                        @endif
                                    </h2>
                                    <p class="mt-2 text-sm font-bold text-red-600">
                                        Rp {{ number_format($product->price ?? 0, 0, ',', '.') }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        @include('front.footer')
    </div>
@endsection
