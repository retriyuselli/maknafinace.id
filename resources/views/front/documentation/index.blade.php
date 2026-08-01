@extends('layouts.app')

@section('title', 'Pusat Bantuan - Makna Finance')

@section('content')
    <div class="min-h-screen bg-gray-50">
        <!-- Navigation Header -->
        @include('front.header')

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            
            <!-- Landing Page Docs: Quick Links -->
            <div class="space-y-12">
                <div class="text-center max-w-3xl mx-auto py-8">
                    <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                        Dokumentasi Sistem
                    </h2>
                    <p class="mt-4 text-lg text-gray-600">
                        Temukan panduan lengkap, referensi fitur, dan tutorial penggunaan sistem Makna Finance di sini.
                    </p>
                </div>

                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-6 px-1">Quick Links</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($categories as $category)
                            @if($category->documentations->count() > 0)
                                <a href="{{ route('docs.show', $category->documentations->first()->slug) }}" 
                                    class="group relative flex flex-col bg-[#fffbf0] border border-[#f5e6c8] rounded-xl p-6 transition-all duration-300 hover:shadow-lg hover:border-orange-300 hover:-translate-y-1">
                                    
                                    <div class="mb-4">
                                        <!-- Icon Placeholder -->
                                        <div class="h-8 w-8 text-orange-500">
                                            @if($category->slug == 'manajemen-order')
                                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                                            @elseif($category->slug == 'manajemen-penjualan')
                                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 5.472m0 0a9.09 9.09 0 00-3.246 1.596 8.38 8.38 0 01-.029-.181" /></svg>
                                            @elseif($category->slug == 'keuangan-akuntansi')
                                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @elseif($category->slug == 'aset-tetap')
                                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                                            @else
                                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                            @endif
                                        </div>
                                    </div>

                                    <h4 class="text-base font-bold text-gray-900 mb-2 group-hover:text-orange-600 transition-colors">
                                        {{ $category->name }}
                                    </h4>
                                    
                                    <p class="text-sm text-gray-600 line-clamp-2">
                                        {{ $category->documentations->count() }} artikel tersedia. Pelajari panduan lengkap mengenai {{ strtolower($category->name) }} di sini.
                                    </p>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            
        </div>

        @include('front.footer')
    </div>
@endsection
