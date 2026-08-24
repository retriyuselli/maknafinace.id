@extends('layouts.app')

@section('title', 'Harga Paket — WOFINS')

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

        .wf-btn-outline-light {
            border: 1.5px solid rgba(255, 255, 255, 0.85);
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
            transition: background .2s ease;
        }

        .wf-btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .wf-price-card {
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.5rem;
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .wf-price-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px -24px rgba(11, 31, 58, 0.35);
        }

        .wf-price-card.is-popular {
            border: 2px solid var(--wf-gold);
            box-shadow: 0 20px 44px -20px rgba(201, 162, 39, 0.45);
        }

        .wf-popular-badge {
            position: absolute;
            top: -0.85rem;
            left: 50%;
            transform: translateX(-50%);
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            padding: 0.35rem 0.9rem;
            border-radius: 999px;
            white-space: nowrap;
        }

        .wf-save-pill {
            display: inline-flex;
            align-items: center;
            background: rgba(201, 162, 39, 0.14);
            color: #9a7a12;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
        }

        .wf-check {
            width: 1.2rem;
            height: 1.2rem;
            border-radius: 999px;
            background: rgba(201, 162, 39, 0.15);
            color: var(--wf-gold);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.65rem;
        }

        .wf-check.navy {
            background: rgba(11, 31, 58, 0.08);
            color: var(--wf-navy);
        }

        .wf-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            background: #fff;
        }

        .wf-compare th,
        .wf-compare td {
            padding: 0.95rem 1.1rem;
            text-align: left;
            border-bottom: 1px solid var(--wf-line);
            white-space: nowrap;
        }

        .wf-compare th {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--wf-navy);
            background: #faf9f6;
        }

        .wf-compare th.col-pro {
            color: #9a7a12;
            background: rgba(201, 162, 39, 0.12);
        }

        .wf-compare td:first-child {
            font-weight: 600;
            color: var(--wf-ink);
            white-space: normal;
            min-width: 180px;
        }

        .wf-compare tr:last-child td {
            border-bottom: 0;
        }

        .wf-faq-item {
            border: 1px solid var(--wf-line);
            border-radius: 0.9rem;
            background: #fff;
            overflow: hidden;
        }

        .wf-cta-box {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #071526 0%, #0b1f3a 55%, #14335a 100%);
            border-radius: 1.5rem;
            color: #fff;
        }

        .wf-cta-shapes {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .wf-footer {
            background: var(--wf-navy);
            color: rgba(255, 255, 255, 0.78);
        }

        .wf-footer a:hover {
            color: #fff;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
@php
    $plans = [
        [
            'key' => 'starter',
            'name' => 'Starter',
            'desc' => 'Cocok untuk WO yang baru mulai merapikan operasional.',
            'price' => '199',
            'unit' => 'RB',
            'popular' => false,
            'cta' => 'Pilih Paket Starter',
            'cta_class' => 'wf-btn-ghost',
            'check' => 'navy',
            'features' => [
                'Hingga 3 pengguna',
                'Manajemen proyek wedding',
                'Keuangan dasar',
                'Nota dinas digital',
                'Dokumen & SOP',
                'Support email',
            ],
        ],
        [
            'key' => 'professional',
            'name' => 'Professional',
            'desc' => 'Paling cocok untuk WO yang ingin kendali penuh sehari-hari.',
            'price' => '399',
            'unit' => 'RB',
            'popular' => true,
            'cta' => 'Pilih Paket Professional',
            'cta_class' => 'wf-btn-gold',
            'check' => '',
            'features' => [
                'Hingga 10 pengguna',
                'Semua fitur Starter',
                'Rekonsiliasi rekening koran',
                'HRIS & absensi GPS',
                'Payroll dasar',
                'Hak akses per jabatan',
                'Support prioritas',
            ],
        ],
        [
            'key' => 'business',
            'name' => 'Business',
            'desc' => 'Untuk WO dengan banyak proyek dan tim lintas fungsi.',
            'price' => '699',
            'unit' => 'RB',
            'popular' => false,
            'cta' => 'Pilih Paket Business',
            'cta_class' => 'wf-btn-ghost',
            'check' => 'navy',
            'features' => [
                'Hingga 25 pengguna',
                'Semua fitur Professional',
                'Payroll & portal karyawan',
                'Laporan lanjutan',
                'Multi-approval workflow',
                'Onboarding & training tim',
                'Support WhatsApp',
            ],
        ],
        [
            'key' => 'enterprise',
            'name' => 'Enterprise',
            'desc' => 'Solusi kustom untuk grup WO / skala nasional.',
            'price' => null,
            'unit' => null,
            'popular' => false,
            'cta' => 'Hubungi Sales Kami',
            'cta_class' => 'wf-btn-ghost',
            'check' => 'navy',
            'features' => [
                'Pengguna tidak terbatas',
                'Semua fitur Business',
                'Custom workflow & integrasi',
                'Dedicated account manager',
                'SLA & keamanan lanjutan',
                'Pelatihan onsite / online',
                'Prioritas pengembangan fitur',
            ],
        ],
    ];

    $compareRows = [
        ['Jumlah pengguna', 'Hingga 3', 'Hingga 10', 'Hingga 25', 'Custom'],
        ['Manajemen proyek', 'Dasar', 'Lengkap', 'Lengkap', 'Lengkap'],
        ['Keuangan & laporan', 'Dasar', 'Lengkap', 'Lengkap', 'Lengkap'],
        ['Rekonsiliasi rekening', false, true, true, true],
        ['Nota dinas digital', true, true, true, true],
        ['HRIS & absensi GPS', false, true, true, true],
        ['Payroll', false, 'Dasar', 'Lengkap', 'Lengkap'],
        ['Hak akses jabatan', 'Terbatas', true, true, true],
        ['Approval workflow', false, 'Dasar', 'Multi-level', 'Custom'],
        ['Support', 'Email', 'Prioritas', 'WhatsApp', 'Dedicated'],
    ];

    $faqs = [
        ['Apakah ada biaya instalasi?', 'Tidak. Semua paket WOFINS tanpa biaya instalasi. Anda hanya membayar biaya berlangganan sesuai paket yang dipilih.'],
        ['Bisakah saya upgrade paket nanti?', 'Bisa. Anda dapat upgrade kapan saja; selisih biaya akan disesuaikan dengan sisa masa aktif langganan.'],
        ['Apakah data saya aman?', 'Ya. Akses berbasis peran, riwayat aktivitas, approval, backup terpusat, dan audit trail membantu menjaga keamanan data bisnis Anda.'],
        ['Apakah ada masa uji coba?', 'Kami sediakan demo gratis dan konsultasi kebutuhan agar Anda bisa menilai kesesuaian WOFINS sebelum berlangganan.'],
        ['Bagaimana proses onboarding?', 'Setelah paket dipilih, tim kami membantu setup perusahaan, pengguna, dan alur kerja inti agar tim Anda siap memakai sistem.'],
    ];
@endphp

    <div class="wf-page" x-data="{ openFaq: 0 }">
        @include('front.partials.wf-nav')

        {{-- Intro --}}
        <section class="pt-14 pb-10 bg-gradient-to-b from-white to-[var(--wf-cream)]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">Harga</p>
                <h1 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-bold text-[var(--wf-navy)] leading-tight">
                    Pilih Paket yang Sesuai dengan Kebutuhan Wedding Organizer Anda
                </h1>
                <p class="mt-4 text-[var(--wf-muted)] max-w-2xl mx-auto">
                    Paket WOFINS dirancang fleksibel — dari WO yang baru merapikan operasional hingga tim skala enterprise.
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm font-semibold text-[var(--wf-navy)]">
                    @foreach (['Tanpa biaya instalasi', 'Update & support gratis', 'Aman & terpercaya'] as $benefit)
                        <span class="inline-flex items-center gap-2">
                            <span class="wf-check"><i class="fa-solid fa-check"></i></span>
                            {{ $benefit }}
                        </span>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Pricing cards --}}
        <section class="pb-14 pt-2">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 pt-3">
                    @foreach ($plans as $plan)
                        <div class="wf-price-card {{ $plan['popular'] ? 'is-popular' : '' }}">
                            @if ($plan['popular'])
                                <span class="wf-popular-badge">PALING POPULER</span>
                            @endif

                            <div class="mb-5 {{ $plan['popular'] ? 'pt-2' : '' }}">
                                <h3 class="text-xl font-bold text-[var(--wf-navy)]">{{ $plan['name'] }}</h3>
                                <p class="mt-1 text-sm text-[var(--wf-muted)]">{{ $plan['desc'] }}</p>
                            </div>

                            <div class="mb-5">
                                @if ($plan['price'])
                                    <div class="flex items-end gap-1">
                                        <span class="text-sm font-semibold text-[var(--wf-muted)] mb-1">Rp</span>
                                        <span class="text-4xl font-extrabold text-[var(--wf-navy)] leading-none">{{ $plan['price'] }}</span>
                                        <span class="text-lg font-bold text-[var(--wf-navy)] mb-0.5">{{ $plan['unit'] }}</span>
                                        <span class="text-sm text-[var(--wf-muted)] mb-1">/ bulan</span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-[var(--wf-muted)]">
                                        <span>Dibayar tahunan</span>
                                        <span class="wf-save-pill">Hemat 2 bulan</span>
                                    </div>
                                @else
                                    <p class="text-3xl font-extrabold text-[var(--wf-navy)] leading-tight">Hubungi Kami</p>
                                    <p class="mt-2 text-xs text-[var(--wf-muted)]">Harga & scope disesuaikan kebutuhan</p>
                                @endif
                            </div>

                            <ul class="space-y-2.5 mb-8 flex-1">
                                @foreach ($plan['features'] as $feature)
                                    <li class="flex items-start gap-2.5 text-sm text-[var(--wf-ink)]">
                                        <span class="wf-check {{ $plan['check'] }}"><i class="fa-solid fa-check"></i></span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <a href="{{ route('kontak', ['paket' => $plan['key']]) }}"
                               class="{{ $plan['cta_class'] }} w-full inline-flex items-center justify-center px-4 py-3 text-sm text-center">
                                {{ $plan['cta'] }}
                            </a>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 rounded-2xl bg-[#eef1f5] border border-[var(--wf-line)] px-5 py-4 flex flex-col sm:flex-row items-center justify-center gap-3 text-sm text-[var(--wf-navy)]">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-white text-[var(--wf-gold)] shadow-sm">
                        <i class="fa-solid fa-shield-halved"></i>
                    </span>
                    <p class="text-center sm:text-left font-medium">
                        Data bisnis Anda dilindungi dengan akses berbasis peran, backup terpusat, dan audit trail.
                    </p>
                </div>
            </div>
        </section>

        {{-- Comparison --}}
        <section class="py-14 bg-[var(--wf-cream)]/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-[var(--wf-navy)] text-center mb-8">Perbandingan Fitur Paket</h2>
                <div class="wf-table-wrap">
                    <table class="wf-compare w-full text-sm">
                        <thead>
                            <tr>
                                <th>Fitur Utama</th>
                                <th>Starter</th>
                                <th class="col-pro">Professional</th>
                                <th>Business</th>
                                <th>Enterprise</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($compareRows as $row)
                                <tr>
                                    @foreach ($row as $i => $cell)
                                        <td>
                                            @if ($i === 0)
                                                {{ $cell }}
                                            @elseif ($cell === true)
                                                <i class="fa-solid fa-check text-emerald-500"></i>
                                            @elseif ($cell === false)
                                                <span class="text-[var(--wf-muted)]">—</span>
                                            @else
                                                <span class="{{ $i === 2 ? 'font-semibold text-[#9a7a12]' : 'text-[var(--wf-muted)]' }}">{{ $cell }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        {{-- FAQ + CTA --}}
        <section class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-8 items-start">
                    <div>
                        <h2 class="text-3xl font-bold text-[var(--wf-navy)] mb-6">FAQ</h2>
                        <div class="space-y-3">
                            @foreach ($faqs as $i => $faq)
                                <div class="wf-faq-item">
                                    <button type="button"
                                            class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left font-bold text-[var(--wf-navy)]"
                                            @click="openFaq = openFaq === {{ $i }} ? null : {{ $i }}">
                                        <span>{{ $faq[0] }}</span>
                                        <i class="fa-solid fa-chevron-down text-xs text-[var(--wf-muted)] transition-transform"
                                           :class="openFaq === {{ $i }} && 'rotate-180'"></i>
                                    </button>
                                    <div x-show="openFaq === {{ $i }}" x-cloak class="px-5 pb-4 text-sm text-[var(--wf-muted)] leading-relaxed">
                                        {{ $faq[1] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <a href="{{ route('home') }}#faq" class="inline-flex items-center gap-2 mt-5 text-sm font-bold text-[var(--wf-gold)] hover:brightness-90">
                            Lihat semua pertanyaan
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>

                    <div class="wf-cta-box p-8 sm:p-10 min-h-[22rem]">
                        <div class="wf-cta-shapes" aria-hidden="true">
                            <span class="absolute -right-10 -top-10 w-56 h-56 rounded-full bg-[rgba(201,162,39,0.16)]"></span>
                            <span class="absolute right-10 bottom-0 w-40 h-40 rounded-full bg-[rgba(70,140,200,0.18)] blur-[1px]"></span>
                            <span class="absolute right-[30%] top-[35%] w-24 h-24 rounded-full border border-[rgba(232,212,139,0.35)]"></span>
                            <span class="absolute right-[18%] top-[22%] w-3 h-3 rounded-full bg-[rgba(232,212,139,0.55)]"></span>
                            <span class="absolute right-[40%] bottom-[22%] w-2 h-2 rounded-full bg-white/40"></span>
                            <svg class="absolute right-0 top-0 h-full w-1/2 opacity-50" viewBox="0 0 280 320" fill="none" preserveAspectRatio="xMaxYMid slice">
                                <path d="M180 40c40 24 72 70 78 120s-16 96-56 124" stroke="rgba(232,212,139,0.4)" stroke-width="1.5"/>
                                <circle cx="220" cy="100" r="36" fill="rgba(201,162,39,0.1)" stroke="rgba(232,212,139,0.3)"/>
                                <circle cx="200" cy="220" r="52" fill="rgba(56,120,180,0.1)" stroke="rgba(140,190,230,0.25)"/>
                                <rect x="150" y="130" width="44" height="44" rx="12" transform="rotate(16 172 152)" stroke="rgba(255,255,255,0.18)" fill="rgba(255,255,255,0.04)"/>
                            </svg>
                        </div>

                        <div class="relative z-10 max-w-md">
                            <div class="w-12 h-12 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center text-[var(--wf-gold-soft)] mb-5">
                                <i class="fa-regular fa-calendar text-xl"></i>
                            </div>
                            <h3 class="text-2xl sm:text-3xl font-bold leading-tight">
                                Butuh Paket yang Sesuai dengan Bisnis Anda?
                            </h3>
                            <p class="mt-3 text-white/75 text-sm leading-relaxed">
                                Jadwalkan demo gratis. Kami bantu petakan kebutuhan WO Anda dan rekomendasikan paket yang paling pas.
                            </p>
                            <div class="mt-7 flex flex-col sm:flex-row gap-3">
                                <a href="{{ route('kontak') }}" class="wf-btn-gold inline-flex items-center justify-center px-5 py-3 text-sm">
                                    Jadwalkan Demo Gratis
                                </a>
                                <a href="{{ route('kontak') }}" class="wf-btn-outline-light inline-flex items-center justify-center px-5 py-3 text-sm">
                                    Konsultasi Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @include('front.partials.wf-footer')

        <a href="https://wa.me/6281373183794?text={{ urlencode('Halo, saya ingin tanya paket harga WOFINS.') }}"
           class="fixed bottom-5 right-5 z-40 w-14 h-14 rounded-full bg-[#25D366] text-white shadow-lg flex items-center justify-center hover:scale-105 transition"
           aria-label="WhatsApp">
            <i class="fa-brands fa-whatsapp text-2xl"></i>
        </a>
    </div>
@endsection
