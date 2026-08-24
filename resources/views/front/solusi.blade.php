@extends('layouts.app')

@section('title', $solution['title'].' — WOFINS')

@push('styles')
@include('front.partials.wf-front-base-styles')
@endpush

@section('content')
    <div class="wf-page">
        @include('front.partials.wf-nav')

        <section class="wf-hero pt-14 pb-12 bg-gradient-to-b from-white to-[var(--wf-cream)]">
            @include('front.partials.wf-deco-shapes')
            <div class="wf-hero-inner max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">{{ $solution['eyebrow'] }}</p>
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[rgba(201,162,39,0.14)] text-[var(--wf-gold)] text-xl mb-4">
                    <i class="fa-solid {{ $solution['icon'] }}"></i>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] leading-tight">
                    {{ $solution['headline'] }}
                </h1>
                <p class="mt-4 text-[var(--wf-muted)] text-base sm:text-lg leading-relaxed">
                    {{ $solution['summary'] }}
                </p>
            </div>
        </section>

        <section class="wf-section-deco py-14 bg-white">
            <div class="wf-deco" aria-hidden="true">
                <span class="wf-deco__blob wf-deco__blob--a" style="top: -6rem; right: -5rem; opacity: 0.35;"></span>
                <span class="wf-deco__ring wf-deco__ring--a"></span>
                <span class="wf-deco__dot wf-deco__dot--b"></span>
                <span class="wf-deco__sq wf-deco__sq--a"></span>
                <span class="wf-deco__tri"></span>
            </div>
            <div class="wf-section-inner max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-5">
                    @foreach ($solution['points'] as $point)
                        <div class="wf-info-card">
                            <h2 class="text-lg font-bold text-[var(--wf-navy)]">{{ $point['title'] }}</h2>
                            <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">{{ $point['desc'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-12">
                    <p class="text-sm font-bold uppercase tracking-wider text-[var(--wf-muted)] mb-4 text-center">Solusi lain</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        @foreach ($allSolutions as $item)
                            <a
                                href="{{ route('solusi.show', $item['slug']) }}"
                                class="px-4 py-2 rounded-full text-sm font-semibold border transition
                                    {{ $slug === $item['slug']
                                        ? 'bg-[var(--wf-navy)] text-white border-[var(--wf-navy)]'
                                        : 'bg-white text-[var(--wf-navy)] border-[var(--wf-line)] hover:border-[var(--wf-gold)]' }}"
                            >
                                {{ $item['title'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="wf-cta-panel mt-12">
                    @include('front.partials.wf-deco-shapes')
                    <p class="text-white font-semibold text-lg">{{ $solution['cta'] }}</p>
                    <div class="mt-5 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('kontak') }}?paket=professional" class="wf-btn-gold px-6 py-3 text-sm">Hubungi Sales</a>
                        <a href="{{ route('harga') }}" class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold rounded-full border border-white/50 text-white hover:bg-white/10 transition">Lihat Paket</a>
                    </div>
                </div>
            </div>
        </section>

        @include('front.partials.wf-footer')
    </div>
@endsection
