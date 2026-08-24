@extends('layouts.app')

@section('title', 'Fitur — WOFINS')

@push('styles')
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
            --wf-white: #ffffff;
        }

        .wf-page {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            background: var(--wf-white);
        }

        .wf-page h1,
        .wf-page h2,
        .wf-page h3 {
            letter-spacing: -0.02em;
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
            transition: background .2s ease, transform .2s ease;
        }

        .wf-btn-navy:hover {
            background: var(--wf-navy-deep);
            transform: translateY(-1px);
        }

        .wf-btn-ghost {
            border: 1.5px solid var(--wf-navy);
            color: var(--wf-navy);
            border-radius: 999px;
            font-weight: 700;
            background: #fff;
            transition: background .2s ease;
        }

        .wf-btn-ghost:hover {
            background: var(--wf-cream);
        }

        .wf-btn-gold {
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
            border-radius: 999px;
            font-weight: 800;
            transition: filter .2s ease, transform .2s ease;
        }

        .wf-btn-gold:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }

        .wf-feature-card {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.5rem;
            padding: 1.5rem;
            height: 100%;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .wf-feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px -24px rgba(11, 31, 58, 0.35);
        }

        .wf-feature-card > .content {
            position: relative;
            z-index: 1;
        }

        .wf-feature-card .ornament {
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            z-index: 0;
        }

        .wf-feature-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.85rem;
            font-size: 1.1rem;
        }

        .wf-check {
            width: 1.15rem;
            height: 1.15rem;
            border-radius: 999px;
            background: rgba(201, 162, 39, 0.15);
            color: var(--wf-gold);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.6rem;
        }

        .wf-integrate {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #071526 0%, #0b1f3a 55%, #14335a 100%);
            border-radius: 1.5rem;
            color: #fff;
        }

        .wf-hub-scene {
            position: relative;
            width: 100%;
            max-width: 22rem;
            aspect-ratio: 1;
            margin-inline: auto;
        }

        .wf-hub-ring {
            position: absolute;
            inset: 12%;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            animation: wf-hub-spin 36s linear infinite;
        }

        .wf-hub-ring.is-inner {
            inset: 28%;
            border-color: rgba(232, 212, 139, 0.25);
            animation-duration: 28s;
            animation-direction: reverse;
        }

        .wf-hub {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 5.5rem;
            height: 5.5rem;
            border-radius: 999px;
            background: rgba(201, 162, 39, 0.18);
            border: 1px solid rgba(232, 212, 139, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: var(--wf-gold-soft);
            z-index: 3;
            animation: wf-hub-pulse 3.2s ease-in-out infinite;
            box-shadow: 0 0 0 0 rgba(201, 162, 39, 0.25);
        }

        .wf-hub-orbit {
            position: absolute;
            inset: 0;
            animation: wf-hub-spin 48s linear infinite;
            z-index: 2;
        }

        .wf-hub-node-wrap {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 0;
            height: 0;
            transform: rotate(var(--a)) translate(7.2rem);
        }

        .wf-hub-node {
            width: 3.25rem;
            height: 3.25rem;
            margin: -1.625rem 0 0 -1.625rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.95rem;
            animation: wf-hub-spin 48s linear infinite reverse;
            transition: background .2s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .wf-hub-node i {
            display: inline-block;
            animation: wf-hub-float 3.6s ease-in-out infinite;
            animation-delay: var(--d, 0s);
        }

        .wf-hub-node:hover {
            background: rgba(201, 162, 39, 0.22);
            border-color: rgba(232, 212, 139, 0.55);
            box-shadow: 0 0 18px rgba(201, 162, 39, 0.25);
        }

        @keyframes wf-hub-spin {
            to { transform: rotate(360deg); }
        }

        @keyframes wf-hub-pulse {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
                box-shadow: 0 0 0 0 rgba(201, 162, 39, 0.28);
            }
            50% {
                transform: translate(-50%, -50%) scale(1.05);
                box-shadow: 0 0 0 14px rgba(201, 162, 39, 0);
            }
        }

        @keyframes wf-hub-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        @media (prefers-reduced-motion: reduce) {
            .wf-hub-ring,
            .wf-hub,
            .wf-hub-orbit,
            .wf-hub-node,
            .wf-hub-node i {
                animation: none !important;
            }
        }

        .wf-benefit {
            text-align: center;
        }

        .wf-benefit-icon {
            width: 3.25rem;
            height: 3.25rem;
            border-radius: 999px;
            background: rgba(201, 162, 39, 0.12);
            color: var(--wf-gold);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }

        .wf-cta-band {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 60% 80% at 90% 40%, rgba(201, 162, 39, 0.16), transparent 55%),
                radial-gradient(ellipse 40% 60% at 10% 80%, rgba(56, 120, 180, 0.12), transparent 50%),
                var(--wf-cream);
            border-radius: 1.5rem;
            border: 1px solid var(--wf-line);
        }

        /* Fitur hero — bento mosaic (beda dari konstelasi home & kartu Empat Langkah) */
        .wf-fitur-mosaic {
            position: relative;
            min-height: 340px;
            border-radius: 1.5rem;
            overflow: hidden;
            border: 1px solid var(--wf-line);
            background: linear-gradient(160deg, #0b1f3a 0%, #14335a 48%, #0b1f3a 100%);
            box-shadow: 0 24px 50px -28px rgba(11, 31, 58, 0.45);
            padding: 1.25rem;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            grid-template-rows: 1fr 0.85fr 1fr;
            gap: 0.75rem;
        }

        .wf-fitur-mosaic::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 80% 20%, rgba(201, 162, 39, 0.22), transparent 40%),
                radial-gradient(circle at 10% 90%, rgba(255, 255, 255, 0.06), transparent 45%);
            pointer-events: none;
        }

        .wf-mosaic-tile {
            position: relative;
            z-index: 1;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(6px);
            overflow: hidden;
            padding: 0.9rem 1rem;
            animation: wf-tile-rise 0.7s ease-out both;
        }

        .wf-mosaic-tile.t1 { grid-column: 1; grid-row: 1 / span 2; animation-delay: 0.05s; }
        .wf-mosaic-tile.t2 {
            grid-column: 2; grid-row: 1; animation-delay: 0.15s;
            background: rgba(201, 162, 39, 0.18);
            border-color: rgba(201, 162, 39, 0.35);
        }
        .wf-mosaic-tile.t3 { grid-column: 2; grid-row: 2; animation-delay: 0.25s; }
        .wf-mosaic-tile.t4 {
            grid-column: 1 / span 2; grid-row: 3; animation-delay: 0.35s;
            display: flex; align-items: center; gap: 1rem;
        }

        .wf-mosaic-tile .m-label {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.55);
        }

        .wf-mosaic-tile .m-title {
            margin-top: 0.35rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
        }

        .wf-mosaic-tile .m-value {
            margin-top: 0.75rem;
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--wf-gold-soft);
            letter-spacing: -0.02em;
            animation: wf-value-tick 4s ease-in-out infinite;
        }

        .wf-mosaic-scan {
            position: absolute;
            left: 0; right: 0;
            height: 28%;
            top: -30%;
            background: linear-gradient(180deg, transparent, rgba(255, 255, 255, 0.12), transparent);
            animation: wf-scan-sweep 4.5s ease-in-out infinite;
            pointer-events: none;
        }

        .wf-mosaic-stack {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            margin-top: 0.85rem;
        }

        .wf-mosaic-stack span {
            display: block;
            height: 8px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.18);
            transform-origin: left center;
            animation: wf-stack-expand 3.2s ease-in-out infinite;
        }

        .wf-mosaic-stack span:nth-child(1) { width: 92%; animation-delay: 0s; }
        .wf-mosaic-stack span:nth-child(2) { width: 74%; animation-delay: 0.2s; background: rgba(201, 162, 39, 0.55); }
        .wf-mosaic-stack span:nth-child(3) { width: 58%; animation-delay: 0.4s; }
        .wf-mosaic-stack span:nth-child(4) { width: 80%; animation-delay: 0.6s; }

        .wf-mosaic-pie {
            position: relative;
            width: 64px;
            height: 64px;
            margin-top: 0.65rem;
            border-radius: 50%;
            background: conic-gradient(
                var(--wf-gold) 0 28%,
                rgba(255, 255, 255, 0.35) 28% 55%,
                rgba(255, 255, 255, 0.15) 55% 100%
            );
            animation: wf-pie-spin 10s linear infinite;
            box-shadow: inset 0 0 0 10px rgba(11, 31, 58, 0.55);
        }

        .wf-mosaic-pie::after {
            content: '';
            position: absolute;
            inset: 14px;
            border-radius: 50%;
            background: #0f2744;
        }

        .wf-mosaic-calendar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
            margin-top: 0.65rem;
        }

        .wf-mosaic-calendar i {
            aspect-ratio: 1;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.12);
            animation: wf-cal-blink 2.4s ease-in-out infinite;
        }

        .wf-mosaic-calendar i:nth-child(3),
        .wf-mosaic-calendar i:nth-child(6),
        .wf-mosaic-calendar i:nth-child(9) {
            background: rgba(201, 162, 39, 0.65);
        }

        .wf-mosaic-calendar i:nth-child(1) { animation-delay: 0s; }
        .wf-mosaic-calendar i:nth-child(2) { animation-delay: 0.1s; }
        .wf-mosaic-calendar i:nth-child(3) { animation-delay: 0.2s; }
        .wf-mosaic-calendar i:nth-child(4) { animation-delay: 0.3s; }
        .wf-mosaic-calendar i:nth-child(5) { animation-delay: 0.15s; }
        .wf-mosaic-calendar i:nth-child(6) { animation-delay: 0.25s; }
        .wf-mosaic-calendar i:nth-child(7) { animation-delay: 0.35s; }
        .wf-mosaic-calendar i:nth-child(8) { animation-delay: 0.45s; }

        .wf-mosaic-rail {
            flex: 1;
            height: 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            overflow: hidden;
            position: relative;
        }

        .wf-mosaic-rail b {
            position: absolute;
            inset: 0 auto 0 0;
            width: 38%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--wf-gold), #fff);
            animation: wf-rail-run 3s ease-in-out infinite;
        }

        .wf-mosaic-badge {
            flex-shrink: 0;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.7rem;
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            animation: wf-badge-bob 3.5s ease-in-out infinite;
        }

        @keyframes wf-tile-rise {
            from { opacity: 0; transform: translateY(18px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes wf-scan-sweep {
            0%, 100% { top: -30%; }
            50% { top: 90%; }
        }

        @keyframes wf-stack-expand {
            0%, 100% { transform: scaleX(0.55); opacity: 0.5; }
            50% { transform: scaleX(1); opacity: 1; }
        }

        @keyframes wf-pie-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes wf-cal-blink {
            0%, 100% { opacity: 0.45; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.08); }
        }

        @keyframes wf-rail-run {
            0% { left: 0; width: 28%; }
            50% { left: 42%; width: 40%; }
            100% { left: 0; width: 28%; }
        }

        @keyframes wf-badge-bob {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        @keyframes wf-value-tick {
            0%, 100% { opacity: 0.75; }
            50% { opacity: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .wf-hub-ring,
            .wf-hub,
            .wf-hub-orbit,
            .wf-hub-node,
            .wf-hub-node i,
            .wf-fitur-mosaic *,
            .wf-mosaic-tile {
                animation: none !important;
            }
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
@php
    $modules = array_values(config('wofins_features', []));

    $benefits = [
        ['fa-gauge-high', 'Efisiensi operasional meningkat'],
        ['fa-chart-pie', 'Data akurat untuk keputusan'],
        ['fa-clock', 'Hemat waktu & kerja manual'],
        ['fa-tower-broadcast', 'Monitoring real-time'],
        ['fa-lock', 'Keamanan data terjamin'],
        ['fa-arrow-trend-up', 'Mendukung pertumbuhan bisnis'],
    ];

    $hubNodes = [
        ['fa-ring', 'Proyek'],
        ['fa-coins', 'Keuangan'],
        ['fa-id-card', 'Payroll'],
        ['fa-folder', 'Dokumen'],
        ['fa-file-signature', 'Nota Dinas'],
        ['fa-building-columns', 'Rekonsiliasi'],
    ];
@endphp

    <div class="wf-page">
        @include('front.partials.wf-nav')

        {{-- Hero --}}
        <section class="pt-12 pb-10 lg:pt-16 lg:pb-14">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
                    <div>
                        <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">Fitur WOFINS</p>
                        <h1 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-bold text-[var(--wf-navy)] leading-tight">
                            Semua yang Anda Butuhkan untuk Mengelola Wedding Organizer
                        </h1>
                        <p class="mt-4 text-[var(--wf-muted)] text-base leading-relaxed max-w-xl">
                            WOFINS menghadirkan fitur terintegrasi untuk mengelola proyek, keuangan, tim, dan operasional harian dalam satu platform.
                        </p>
                    </div>
                    <div class="wf-fitur-mosaic" aria-hidden="true">
                        <div class="wf-mosaic-tile t1">
                            <div class="wf-mosaic-scan"></div>
                            <p class="m-label">Overview</p>
                            <p class="m-title">Ringkasan Operasional</p>
                            <p class="m-value">Rp 1,2M</p>
                            <div class="wf-mosaic-stack">
                                <span></span><span></span><span></span><span></span>
                            </div>
                        </div>

                        <div class="wf-mosaic-tile t2">
                            <p class="m-label">Finance</p>
                            <p class="m-title">Distribusi</p>
                            <div class="wf-mosaic-pie"></div>
                        </div>

                        <div class="wf-mosaic-tile t3">
                            <p class="m-label">Schedule</p>
                            <p class="m-title">Event Week</p>
                            <div class="wf-mosaic-calendar">
                                <i></i><i></i><i></i><i></i>
                                <i></i><i></i><i></i><i></i>
                            </div>
                        </div>

                        <div class="wf-mosaic-tile t4">
                            <div class="wf-mosaic-badge"><i class="fa-solid fa-bolt"></i></div>
                            <div style="flex:1;min-width:0">
                                <p class="m-label">Sync realtime</p>
                                <p class="m-title" style="margin-top:0.15rem">Proyek · Kas · Payroll</p>
                                <div class="wf-mosaic-rail" style="margin-top:0.55rem"><b></b></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Feature modules --}}
        <section class="pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach ($modules as $module)
                        <a href="{{ route('fitur.show', $module['slug']) }}" class="wf-feature-card block group">
                            <span class="ornament" style="width:6.5rem;height:6.5rem;right:-1.6rem;top:-1.8rem;background:{{ $module['accent'] }};" aria-hidden="true"></span>
                            <span class="ornament" style="width:3rem;height:3rem;right:1.4rem;top:4.2rem;border:1.5px solid {{ $module['accent'] }};background:transparent;" aria-hidden="true"></span>
                            <span class="ornament" style="width:4rem;height:4rem;left:-1.4rem;bottom:-1.2rem;background:{{ $module['accent'] }};opacity:0.7;" aria-hidden="true"></span>

                            <div class="content">
                                <div class="wf-feature-icon {{ $module['color'] }}">
                                    <i class="fa-solid {{ $module['icon'] }}"></i>
                                </div>
                                <h2 class="text-xl font-bold text-[var(--wf-navy)] group-hover:text-[var(--wf-gold)] transition-colors">{{ $module['title'] }}</h2>
                                <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">{{ $module['desc'] }}</p>
                                <ul class="mt-4 space-y-2">
                                    @foreach ($module['items'] as $item)
                                        <li class="flex items-start gap-2.5 text-sm text-[var(--wf-ink)]">
                                            <span class="wf-check mt-0.5"><i class="fa-solid fa-check"></i></span>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <span class="inline-flex items-center gap-1.5 mt-5 text-sm font-bold text-[var(--wf-navy)]">
                                    Selengkapnya
                                    <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-0.5"></i>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Integration --}}
        <section class="pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="wf-integrate p-8 sm:p-10 lg:p-12">
                    <div class="grid lg:grid-cols-2 gap-10 items-center relative z-10">
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-bold leading-tight">Semua Fitur Terintegrasi dalam Satu Platform</h2>
                            <p class="mt-3 text-white/75 text-sm leading-relaxed max-w-md">
                                Proyek, keuangan, payroll, dokumen, dan approval saling terhubung — tanpa spreadsheet terpisah atau data dobel.
                            </p>
                            <a href="{{ route('kontak') }}" class="wf-btn-gold inline-flex items-center justify-center px-6 py-3.5 text-sm mt-7">
                                Jadwalkan Demo Gratis
                            </a>
                        </div>

                        <div class="wf-hub-scene" aria-hidden="true">
                            <div class="wf-hub-ring"></div>
                            <div class="wf-hub-ring is-inner"></div>
                            <div class="wf-hub">WOFINS</div>
                            <div class="wf-hub-orbit">
                                @foreach ($hubNodes as $i => $node)
                                    @php $angle = ($i / count($hubNodes)) * 360 - 90; @endphp
                                    <div class="wf-hub-node-wrap" style="--a: {{ $angle }}deg;">
                                        <div class="wf-hub-node" style="--d: {{ $i * 0.35 }}s;" title="{{ $node[1] }}">
                                            <i class="fa-solid {{ $node[0] }}"></i>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Benefits --}}
        <section class="pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-[var(--wf-navy)] text-center mb-10">Manfaat WOFINS untuk Bisnis Anda</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                    @foreach ($benefits as $benefit)
                        <div class="wf-benefit">
                            <div class="wf-benefit-icon">
                                <i class="fa-solid {{ $benefit[0] }}"></i>
                            </div>
                            <p class="text-sm font-semibold text-[var(--wf-navy)] leading-snug">{{ $benefit[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="wf-cta-band px-6 py-10 sm:px-10 sm:py-12 text-center">
                    <div class="absolute right-8 top-6 w-24 h-24 rounded-full border border-[rgba(201,162,39,0.25)] pointer-events-none" aria-hidden="true"></div>
                    <div class="absolute left-10 bottom-6 w-16 h-16 rounded-full bg-[rgba(56,120,180,0.1)] pointer-events-none" aria-hidden="true"></div>
                    <h2 class="relative text-2xl sm:text-3xl font-bold text-[var(--wf-navy)] leading-tight max-w-2xl mx-auto">
                        Siap mengelola Wedding Organizer lebih profesional?
                    </h2>
                    <div class="relative mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex items-center justify-center px-6 py-3.5 text-sm">
                            Jadwalkan Demo Gratis
                        </a>
                        <a href="{{ route('kontak') }}" class="wf-btn-ghost inline-flex items-center justify-center px-6 py-3.5 text-sm">
                            Konsultasi Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </section>

        @include('front.partials.wf-footer')

        <a href="https://wa.me/6281373183794?text={{ urlencode('Halo, saya ingin tahu lebih lanjut tentang fitur WOFINS.') }}"
           class="fixed bottom-5 right-5 z-40 w-14 h-14 rounded-full bg-[#25D366] text-white shadow-lg flex items-center justify-center hover:scale-105 transition"
           aria-label="WhatsApp">
            <i class="fa-brands fa-whatsapp text-2xl"></i>
        </a>
    </div>
@endsection
