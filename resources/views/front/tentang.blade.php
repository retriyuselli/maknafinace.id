@extends('layouts.app')

@section('title', 'Tentang Kami — WOFINS')

@push('styles')
@include('front.partials.wf-front-base-styles')
@endpush

@section('content')
    <div class="wf-page">
        @include('front.partials.wf-nav')

        <section class="wf-hero pt-14 pb-12 bg-gradient-to-b from-white to-[var(--wf-cream)]">
            @include('front.partials.wf-deco-shapes')
            <div class="wf-hero-inner max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">Tentang Kami</p>
                <h1 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] leading-tight">
                    WOFINS
                </h1>
                <p class="mt-3 text-lg font-semibold text-[var(--wf-ink)]">
                    Wedding Organizer Financial Information System
                </p>
                <p class="mt-4 text-[var(--wf-muted)] text-base sm:text-lg leading-relaxed">
                    Dibangun oleh praktisi wedding organizer untuk merapikan operasional, keuangan, dan SDM dalam satu platform.
                </p>
            </div>
        </section>

        <section class="wf-section-deco py-14 bg-white">
            <div class="wf-deco" aria-hidden="true">
                <span class="wf-deco__blob wf-deco__blob--b" style="top: auto; bottom: 10%; left: auto; right: -3rem;"></span>
                <span class="wf-deco__ring wf-deco__ring--a"></span>
                <span class="wf-deco__tri"></span>
                <span class="wf-deco__sq wf-deco__sq--a"></span>
            </div>
            <div class="wf-section-inner max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
                <div class="max-w-3xl">
                    <h2 class="text-2xl font-bold text-[var(--wf-navy)]">Siapa di balik WOFINS</h2>
                    <p class="mt-3 text-[var(--wf-muted)] leading-relaxed">
                        WOFINS dikembangkan oleh <strong class="text-[var(--wf-ink)]">Makna Kreatif Indonesia</strong>
                        bersama pengalaman operasional dari
                        <strong class="text-[var(--wf-ink)]">Makna Wedding & Event Planner</strong>.
                        Fokus kami sederhana: mengurangi spreadsheet, mempercepat approval, dan memberi owner visibilitas bisnis yang jelas.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-5">
                    @foreach ([
                        ['fa-diagram-project', 'Satu alur kerja', 'Dari prospek, simulasi, proyek, hingga kas dan HR — saling terhubung.'],
                        ['fa-layer-group', 'Paket bertingkat', 'Starter, Professional, dan Business menyesuaikan skala tim dan kebutuhan modul.'],
                        ['fa-handshake', 'Dampingan go-live', 'Onboarding dan training tersedia sesuai paket yang dipilih.'],
                    ] as [$icon, $title, $desc])
                        <div class="wf-info-card">
                            <span class="wf-info-icon"><i class="fa-solid {{ $icon }}"></i></span>
                            <h3 class="text-base font-bold text-[var(--wf-navy)] mt-4">{{ $title }}</h3>
                            <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">{{ $desc }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)]/60 px-6 py-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
                    <div class="wf-deco" aria-hidden="true">
                        <span class="wf-deco__dot wf-deco__dot--a" style="top: 1.25rem; right: 1.5rem;"></span>
                        <span class="wf-deco__sq wf-deco__sq--b" style="bottom: 1rem; left: 1rem;"></span>
                    </div>
                    <div class="relative z-[1]">
                        <p class="font-bold text-[var(--wf-navy)] text-lg">Makna Wedding & Event Planner</p>
                        <p class="mt-1 text-sm text-[var(--wf-muted)]">Palembang, Indonesia · support@wofins.id</p>
                    </div>
                    <div class="relative z-[1] flex flex-wrap gap-3">
                        <a href="{{ route('kontak') }}" class="wf-btn-navy px-5 py-2.5 text-sm">Kontak Kami</a>
                        <a href="{{ route('harga') }}" class="wf-btn-ghost px-5 py-2.5 text-sm">Lihat Harga</a>
                    </div>
                </div>
            </div>
        </section>

        @include('front.partials.wf-footer')
    </div>
@endsection
