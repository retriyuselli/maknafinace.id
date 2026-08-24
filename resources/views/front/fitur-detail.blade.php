@extends('layouts.app')

@section('title', $feature['title'].' — WOFINS')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

        .wf-page h1, .wf-page h2, .wf-page h3 { letter-spacing: -0.02em; }

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

        .wf-btn-navy:hover { background: var(--wf-navy-deep); }

        .wf-btn-ghost {
            border: 1.5px solid var(--wf-navy);
            color: var(--wf-navy);
            border-radius: 999px;
            font-weight: 700;
            background: #fff;
        }

        .wf-btn-ghost:hover { background: var(--wf-cream); }

        .wf-btn-gold {
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
            border-radius: 999px;
            font-weight: 800;
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

        .wf-hero-panel {
            position: relative;
            overflow: hidden;
            border-radius: 1.5rem;
            border: 1px solid var(--wf-line);
            background: var(--wf-cream);
            min-height: 16rem;
        }

        .wf-hero-panel .ornament {
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }

        .wf-highlight {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            padding: 1.35rem;
            height: 100%;
        }

        .wf-highlight .ornament {
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }

        .wf-related {
            position: relative;
            overflow: hidden;
            display: block;
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            padding: 1.25rem;
            transition: transform .2s ease, box-shadow .2s ease;
            height: 100%;
        }

        .wf-related:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 36px -24px rgba(11, 31, 58, 0.35);
        }

        .wf-cta-band {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 60% 80% at 90% 40%, rgba(201, 162, 39, 0.16), transparent 55%),
                var(--wf-cream);
            border-radius: 1.5rem;
            border: 1px solid var(--wf-line);
        }

        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('content')
    <div class="wf-page">
        @include('front.partials.wf-nav')

        <section class="pt-10 pb-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="text-sm text-[var(--wf-muted)] mb-6">
                    <a href="{{ route('home') }}" class="hover:text-[var(--wf-navy)]">Beranda</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('fitur') }}" class="hover:text-[var(--wf-navy)]">Fitur</a>
                    <span class="mx-2">/</span>
                    <span class="text-[var(--wf-navy)] font-semibold">{{ $feature['title'] }}</span>
                </nav>

                <div class="grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
                    <div>
                        <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">{{ $feature['label'] }}</p>
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl {{ $feature['color'] }} mb-4">
                            <i class="fa-solid {{ $feature['icon'] }} text-xl"></i>
                        </div>
                        <h1 class="text-3xl sm:text-4xl lg:text-[2.6rem] font-bold text-[var(--wf-navy)] leading-tight">
                            {{ $feature['title'] }}
                        </h1>
                        <p class="mt-4 text-[var(--wf-muted)] leading-relaxed max-w-xl">
                            {{ $feature['hero'] }}
                        </p>
                        <div class="mt-7 flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex items-center justify-center px-6 py-3.5 text-sm">
                                Jadwalkan Demo Gratis
                            </a>
                            <a href="{{ route('fitur') }}" class="wf-btn-ghost inline-flex items-center justify-center px-6 py-3.5 text-sm">
                                Semua Fitur
                            </a>
                        </div>
                    </div>

                    <div class="wf-hero-panel p-8 flex items-center justify-center" aria-hidden="true">
                        <span class="ornament" style="width:9rem;height:9rem;right:-2rem;top:-2rem;background:{{ $feature['accent'] }};"></span>
                        <span class="ornament" style="width:5rem;height:5rem;left:-1.2rem;bottom:-1rem;background:{{ $feature['accent'] }};"></span>
                        <span class="ornament" style="width:3rem;height:3rem;right:28%;bottom:22%;border:1.5px solid {{ $feature['accent'] }};"></span>
                        <div class="relative z-10 w-24 h-24 rounded-2xl {{ $feature['color'] }} flex items-center justify-center shadow-lg">
                            <i class="fa-solid {{ $feature['icon'] }} text-4xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-5 gap-8 items-start">
                    <div class="lg:col-span-2 rounded-2xl border border-[var(--wf-line)] bg-white p-6 sm:p-7">
                        <h2 class="text-xl font-bold text-[var(--wf-navy)] mb-4">Yang Anda dapatkan</h2>
                        <ul class="space-y-3">
                            @foreach ($feature['items'] as $item)
                                <li class="flex items-start gap-2.5 text-sm text-[var(--wf-ink)]">
                                    <span class="wf-check mt-0.5"><i class="fa-solid fa-check"></i></span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-6 pt-5 border-t border-[var(--wf-line)]">
                            <p class="text-xs font-bold uppercase tracking-wider text-[var(--wf-muted)] mb-2">Cocok untuk</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($feature['audience'] as $role)
                                    <span class="text-xs font-semibold px-3 py-1.5 rounded-full bg-[var(--wf-cream)] text-[var(--wf-navy)]">{{ $role }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-3 grid sm:grid-cols-2 gap-4">
                        @foreach ($feature['highlights'] as $item)
                            <div class="wf-highlight">
                                <span class="ornament" style="width:4.5rem;height:4.5rem;right:-1.2rem;top:-1.2rem;background:{{ $feature['accent'] }};" aria-hidden="true"></span>
                                <div class="relative z-10">
                                    <div class="w-10 h-10 rounded-xl {{ $feature['color'] }} inline-flex items-center justify-center mb-3">
                                        <i class="fa-solid {{ $item[0] }}"></i>
                                    </div>
                                    <h3 class="font-bold text-[var(--wf-navy)]">{{ $item[1] }}</h3>
                                    <p class="mt-1.5 text-sm text-[var(--wf-muted)] leading-relaxed">{{ $item[2] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        @if (count($related))
            <section class="pb-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-end justify-between gap-4 mb-6">
                        <h2 class="text-2xl font-bold text-[var(--wf-navy)]">Fitur terkait</h2>
                        <a href="{{ route('fitur') }}" class="text-sm font-bold text-[var(--wf-gold)] hover:brightness-90">Lihat semua</a>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4">
                        @foreach ($related as $item)
                            <a href="{{ route('fitur.show', $item['slug']) }}" class="wf-related">
                                <span class="absolute w-16 h-16 rounded-full right-[-1rem] top-[-1rem] pointer-events-none" style="background:{{ $item['accent'] }};" aria-hidden="true"></span>
                                <div class="relative z-10">
                                    <div class="w-10 h-10 rounded-xl {{ $item['color'] }} inline-flex items-center justify-center mb-3">
                                        <i class="fa-solid {{ $item['icon'] }}"></i>
                                    </div>
                                    <h3 class="font-bold text-[var(--wf-navy)]">{{ $item['title'] }}</h3>
                                    <p class="mt-1 text-sm text-[var(--wf-muted)] line-clamp-2">{{ $item['desc'] }}</p>
                                    <span class="inline-flex items-center gap-1.5 mt-3 text-sm font-bold text-[var(--wf-navy)]">
                                        Selengkapnya <i class="fa-solid fa-arrow-right text-xs"></i>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="pb-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="wf-cta-band px-6 py-10 sm:px-10 text-center">
                    <h2 class="text-2xl sm:text-3xl font-bold text-[var(--wf-navy)] leading-tight max-w-2xl mx-auto">
                        Ingin melihat {{ $feature['title'] }} langsung di WOFINS?
                    </h2>
                    <p class="mt-3 text-sm text-[var(--wf-muted)] max-w-xl mx-auto">
                        Jadwalkan demo gratis. Tim kami bantu tunjukkan alur yang relevan untuk bisnis wedding organizer Anda.
                    </p>
                    <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex items-center justify-center px-6 py-3.5 text-sm">
                            Jadwalkan Demo Gratis
                        </a>
                        <a href="{{ route('harga') }}" class="wf-btn-ghost inline-flex items-center justify-center px-6 py-3.5 text-sm">
                            Lihat Harga
                        </a>
                    </div>
                </div>
            </div>
        </section>

        @include('front.partials.wf-footer')

        <a href="https://wa.me/6281373183794?text={{ urlencode('Halo, saya ingin tahu lebih lanjut tentang fitur '.$feature['title'].' di WOFINS.') }}"
           class="fixed bottom-5 right-5 z-40 w-14 h-14 rounded-full bg-[#25D366] text-white shadow-lg flex items-center justify-center hover:scale-105 transition"
           aria-label="WhatsApp">
            <i class="fa-brands fa-whatsapp text-2xl"></i>
        </a>
    </div>
@endsection
