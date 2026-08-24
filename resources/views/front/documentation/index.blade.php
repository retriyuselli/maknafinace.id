@extends('layouts.app')

@section('title', 'Dokumentasi — WOFINS')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --wf-navy: #0b1f3a;
            --wf-navy-deep: #071526;
            --wf-gold: #c9a227;
            --wf-gold-soft: #e8d48b;
            --wf-cream: #f7f4ee;
            --wf-ink: #1a2332;
            --wf-muted: #5c6675;
            --wf-line: #e6e2d9;
        }

        .wf-page {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            background: #fff;
        }

        .wf-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--wf-line);
        }

        .wf-btn-navy {
            background: var(--wf-navy);
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
        }

        .wf-btn-ghost {
            border: 1.5px solid var(--wf-navy);
            color: var(--wf-navy);
            border-radius: 999px;
            font-weight: 700;
            background: #fff;
        }

        .wf-doc-card {
            position: relative;
            overflow: hidden;
            display: block;
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            padding: 1.5rem;
            height: 100%;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .wf-doc-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px -24px rgba(11, 31, 58, 0.35);
        }

        .wf-doc-card .ornament {
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            background: rgba(201, 162, 39, 0.14);
        }

        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('content')
    <div class="wf-page">
        @include('front.partials.wf-nav')

        <section class="pt-12 pb-6 bg-gradient-to-b from-white to-[var(--wf-cream)]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">Dokumentasi</p>
                <h1 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] leading-tight">
                    Pusat Panduan WOFINS
                </h1>
                <p class="mt-4 text-[var(--wf-muted)] max-w-2xl mx-auto">
                    Temukan panduan lengkap, referensi fitur, dan tutorial penggunaan sistem WOFINS.
                </p>
            </div>
        </section>

        <section class="py-12 pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @php
                    $visible = $categories->filter(fn ($c) => $c->documentations->count() > 0);
                @endphp

                @if ($visible->isEmpty())
                    <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] px-6 py-12 text-center">
                        <p class="font-semibold text-[var(--wf-navy)]">Belum ada dokumentasi yang dipublikasikan.</p>
                        <p class="mt-2 text-sm text-[var(--wf-muted)]">Silakan kembali lagi nanti atau hubungi tim kami.</p>
                        <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex mt-6 px-5 py-3 text-sm">Hubungi Kami</a>
                    </div>
                @else
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach ($visible as $category)
                            @php $first = $category->documentations->first(); @endphp
                            <a href="{{ route('docs.show', $first->slug) }}" class="wf-doc-card group">
                                <span class="ornament w-20 h-20 -right-6 -top-6" aria-hidden="true"></span>
                                <div class="relative z-10">
                                    <div class="w-11 h-11 rounded-xl bg-[rgba(201,162,39,0.12)] text-[var(--wf-gold)] inline-flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-book-open"></i>
                                    </div>
                                    <h2 class="text-lg font-bold text-[var(--wf-navy)] group-hover:text-[var(--wf-gold)] transition-colors">
                                        {{ $category->name }}
                                    </h2>
                                    <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                                        {{ $category->documentations->count() }} artikel · Pelajari panduan {{ strtolower($category->name) }}.
                                    </p>
                                    <span class="inline-flex items-center gap-1.5 mt-4 text-sm font-bold text-[var(--wf-navy)]">
                                        Buka panduan
                                        <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-0.5"></i>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        @include('front.partials.wf-footer')
    </div>
@endsection
