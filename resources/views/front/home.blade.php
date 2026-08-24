@extends('layouts.app')

@section('title', 'WOFINS — Wedding Organizer Financial Information System')

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
            --font-display: 'Poppins', system-ui, sans-serif;
            --font-body: 'Poppins', system-ui, sans-serif;
        }

        .wf-page {
            font-family: var(--font-body);
            color: var(--wf-ink);
            background: var(--wf-white);
        }

        .wf-page h1,
        .wf-page h2,
        .wf-page h3,
        .wf-display {
            font-family: var(--font-display);
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
            transition: background .2s ease, color .2s ease;
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

        .wf-check {
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 999px;
            background: rgba(201, 162, 39, 0.15);
            color: var(--wf-gold);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .wf-hero {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 80% 60% at 90% 20%, rgba(201, 162, 39, 0.12), transparent 55%),
                linear-gradient(180deg, #fff 0%, var(--wf-cream) 100%);
        }

        .wf-hero-visual {
            position: relative;
            min-height: 360px;
            isolation: isolate;
        }

        .wf-hero-laptop {
            position: relative;
            z-index: 1;
            border-radius: 1rem;
            box-shadow: 0 30px 60px -20px rgba(11, 31, 58, 0.35);
            border: 1px solid var(--wf-line);
            background: #fff;
            overflow: hidden;
            animation: wf-hero-fade 0.8s ease-out both, wf-hero-float 5.5s ease-in-out 0.8s infinite;
        }

        .wf-hero-laptop img {
            display: block;
            width: 100%;
            height: auto;
        }

        /* Satu frame ponsel saja — tanpa border putih + navy yang bikin tampak dobel */
        .wf-hero-phone {
            position: absolute;
            z-index: 2;
            right: 2%;
            bottom: -4%;
            width: min(34%, 168px);
            aspect-ratio: 9 / 19.5;
            border-radius: 1.35rem;
            overflow: hidden;
            background: #0b1220;
            box-shadow:
                0 0 0 2px #0b1220,
                0 18px 36px -14px rgba(11, 31, 58, 0.55);
            animation: wf-hero-fade 0.9s ease-out 0.15s both, wf-hero-float-alt 4.8s ease-in-out 1s infinite;
        }

        .wf-hero-phone img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top center;
        }

        .wf-anim {
            animation: wf-hero-in 0.7s ease-out both;
        }

        .wf-anim-d1 { animation-delay: 0.05s; }
        .wf-anim-d2 { animation-delay: 0.12s; }
        .wf-anim-d3 { animation-delay: 0.2s; }
        .wf-anim-d4 { animation-delay: 0.28s; }
        .wf-anim-d5 { animation-delay: 0.36s; }

        .wf-hero-list li {
            animation: wf-hero-in 0.55s ease-out both;
        }

        .wf-hero-list li:nth-child(1) { animation-delay: 0.28s; }
        .wf-hero-list li:nth-child(2) { animation-delay: 0.36s; }
        .wf-hero-list li:nth-child(3) { animation-delay: 0.44s; }
        .wf-hero-list li:nth-child(4) { animation-delay: 0.52s; }

        @keyframes wf-hero-in {
            from {
                opacity: 0;
                transform: translateY(14px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes wf-hero-fade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes wf-hero-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes wf-hero-float-alt {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        @media (prefers-reduced-motion: reduce) {
            .wf-hero-laptop,
            .wf-hero-phone,
            .wf-anim,
            .wf-hero-list li {
                animation: none !important;
            }
        }

        .wf-card {
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            padding: 1.5rem;
            transition: transform .2s ease, box-shadow .2s ease;
            height: 100%;
        }

        .wf-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px -24px rgba(11, 31, 58, 0.35);
        }

        .wf-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .wf-step {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 999px;
            background: var(--wf-navy);
            color: #fff;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .wf-security {
            background: linear-gradient(135deg, var(--wf-navy) 0%, #14335a 100%);
            color: #fff;
        }

        .wf-cta {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 70% 80% at 85% 50%, rgba(201, 162, 39, 0.22), transparent 55%),
                radial-gradient(ellipse 50% 60% at 10% 80%, rgba(56, 120, 180, 0.2), transparent 50%),
                linear-gradient(135deg, #071526 0%, #0b1f3a 45%, #122a4a 100%);
        }

        .wf-cta-shapes {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .wf-cta-shapes .ring {
            position: absolute;
            border-radius: 999px;
            border: 1px solid rgba(232, 212, 139, 0.22);
        }

        .wf-cta-shapes .blob {
            position: absolute;
            border-radius: 999px;
            filter: blur(2px);
        }

        .wf-cta-shapes .dot {
            position: absolute;
            border-radius: 999px;
            background: rgba(232, 212, 139, 0.55);
        }

        .wf-cta-panel {
            position: relative;
            z-index: 1;
            background: rgba(11, 31, 58, 0.88);
            border: 1px solid rgba(232, 212, 139, 0.28);
            border-radius: 1.5rem;
            max-width: 42rem;
            backdrop-filter: blur(6px);
        }

        @media (max-width: 768px) {
            .wf-hero-phone {
                width: 112px;
                right: 6%;
                bottom: -2%;
            }
        }
    </style>
@endpush

@section('content')
    <div class="wf-page">
        @include('front.partials.wf-nav')

        {{-- Hero --}}
        <section class="wf-hero">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                    <div>
                        <p class="wf-anim wf-anim-d1 text-xs tracking-[0.2em] uppercase text-[var(--wf-gold)] font-bold mb-4">WOFINS · by Makna Finance</p>
                        <h1 class="wf-anim wf-anim-d2 text-4xl sm:text-5xl lg:text-[3.4rem] leading-[1.08] font-bold text-[var(--wf-navy)]">
                            Kelola Wedding Organizer Lebih Rapi dalam
                            <span class="text-[var(--wf-gold)]">Satu Platform</span>
                        </h1>
                        <p class="wf-anim wf-anim-d3 mt-5 text-base sm:text-lg text-[var(--wf-muted)] leading-relaxed max-w-xl">
                            WOFINS membantu Wedding Organizer mengelola proyek, vendor, keuangan, HRIS, hingga operasional harian dalam satu sistem terintegrasi.
                        </p>

                        <ul class="wf-hero-list mt-7 space-y-3 text-sm sm:text-base text-[var(--wf-ink)]">
                            @foreach ([
                                'Kelola proyek wedding dari prospek hingga selesai.',
                                'Pantau arus kas perusahaan secara real-time.',
                                'Rekonsiliasi rekening koran lebih cepat.',
                                'Absensi GPS & payroll dalam satu sistem.',
                            ] as $point)
                                <li class="flex items-start gap-3">
                                    <span class="wf-check mt-0.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span>{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="wf-anim wf-anim-d5 mt-8 flex flex-col sm:flex-row gap-3 sm:items-center">
                            <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex items-center justify-center px-6 py-3.5 text-sm">
                                Jadwalkan Demo Gratis
                            </a>
                            <a href="{{ route('fitur') }}" class="inline-flex items-center justify-center gap-2 px-2 py-3 text-sm font-bold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">
                                Lihat Fitur Lengkap
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>

                    <div class="wf-hero-visual">
                        <div class="wf-hero-laptop">
                            <img src="{{ asset('images/laporan/laporan1.png') }}" alt="Dashboard keuangan WOFINS">
                        </div>
                        <div class="wf-hero-phone" aria-hidden="true">
                            <img src="{{ asset('images/aset_tetap/mobile.png') }}" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section id="fitur" class="py-16 lg:py-22 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-12">
                    <h2 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)]">Fitur Lengkap untuk Operasional Wedding Organizer</h2>
                    <p class="mt-3 text-[var(--wf-muted)]">Dari proyek dan keuangan hingga HRIS — semuanya terhubung dalam satu alur kerja.</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @php
                        $features = [
                            ['icon' => 'fa-ring', 'color' => 'bg-amber-50 text-amber-700', 'title' => 'Proyek Wedding', 'desc' => 'Kelola prospek, order, produk paket, simulasi, dan vendor dari satu tempat.'],
                            ['icon' => 'fa-chart-line', 'color' => 'bg-sky-50 text-sky-700', 'title' => 'Keuangan', 'desc' => 'Pantau pendapatan klien, pengeluaran proyek, dan laporan laba rugi secara real-time.'],
                            ['icon' => 'fa-building-columns', 'color' => 'bg-emerald-50 text-emerald-700', 'title' => 'Rekonsiliasi Rekening Koran', 'desc' => 'Cocokkan transaksi bank dengan sistem lebih cepat dan akurat.'],
                            ['icon' => 'fa-file-lines', 'color' => 'bg-violet-50 text-violet-700', 'title' => 'Nota Dinas Digital', 'desc' => 'Ajukan, setujui, dan arsipkan nota dinas beserta lampiran PDF.'],
                            ['icon' => 'fa-user-check', 'color' => 'bg-rose-50 text-rose-700', 'title' => 'HRIS', 'desc' => 'Absensi GPS geofence, foto kamera, jadwal kerja, koreksi, dan lembur.'],
                            ['icon' => 'fa-wallet', 'color' => 'bg-indigo-50 text-indigo-700', 'title' => 'Payroll & Portal Karyawan', 'desc' => 'Kelola gaji, cuti, dan akses portal untuk karyawan tanpa masuk admin penuh.'],
                            ['icon' => 'fa-folder-open', 'color' => 'bg-teal-50 text-teal-700', 'title' => 'Dokumen & SOP', 'desc' => 'Simpan dokumen resmi, SOP, dan knowledge base perusahaan.'],
                            ['icon' => 'fa-shield-halved', 'color' => 'bg-slate-100 text-slate-700', 'title' => 'Hak Akses Berdasarkan Jabatan', 'desc' => 'Role & permission untuk owner, finance, HRD, AM, dan staff.'],
                        ];
                    @endphp

                    @foreach ($features as $feature)
                        <article class="wf-card">
                            <div class="wf-icon {{ $feature['color'] }}">
                                <i class="fa-solid {{ $feature['icon'] }}"></i>
                            </div>
                            <h3 class="text-xl font-bold text-[var(--wf-navy)] mb-2">{{ $feature['title'] }}</h3>
                            <p class="text-sm text-[var(--wf-muted)] leading-relaxed">{{ $feature['desc'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- How it works --}}
        <section id="cara-kerja" class="py-16 lg:py-20 bg-[var(--wf-cream)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] mb-8">Mulai Menggunakan WOFINS dalam Empat Langkah</h2>
                        <div class="space-y-6">
                            @foreach ([
                                ['Buat perusahaan & pengguna', 'Setup profil perusahaan, role, dan akun tim Anda.'],
                                ['Input proyek & keuangan', 'Masukkan order, vendor, pendapatan, dan pengeluaran.'],
                                ['Aktifkan absensi & cuti', 'Atur lokasi kantor, jadwal, dan kuota cuti karyawan.'],
                                ['Pantau laporan real-time', 'Ambil keputusan dari dashboard, cash flow, dan rekonsiliasi.'],
                            ] as $i => $step)
                                <div class="flex gap-4">
                                    <span class="wf-step">{{ $i + 1 }}</span>
                                    <div>
                                        <h3 class="font-bold text-[var(--wf-navy)]">{{ $step[0] }}</h3>
                                        <p class="text-sm text-[var(--wf-muted)] mt-1">{{ $step[1] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="rounded-2xl overflow-hidden border border-[var(--wf-line)] shadow-xl bg-white">
                        <img src="{{ asset('images/excited-asian-colleagues-looking-laptop-screen-together-office.png') }}" alt="Tim menggunakan WOFINS" class="w-full h-full object-cover min-h-[280px]">
                    </div>
                </div>
            </div>
        </section>

        {{-- Why + roles --}}
        <section id="keunggulan" class="py-16 lg:py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-10 lg:gap-16">
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] mb-8">Mengapa Memilih WOFINS?</h2>
                        <ul class="space-y-5">
                            @foreach ([
                                ['Dibangun khusus untuk WO', 'Bukan ERP generik — alur kerjanya cocok dengan operasional wedding organizer.'],
                                ['Data terpusat', 'Proyek, keuangan, HR, dan dokumen tidak lagi tercecer di spreadsheet.'],
                                ['Kurangi kerja manual', 'Approval, absensi, dan rekonsiliasi lebih cepat.'],
                                ['Monitoring real-time', 'Owner melihat kas, proyek, dan kehadiran tanpa menunggu laporan akhir bulan.'],
                                ['Siap berkembang', 'Skalakan dari tim kecil hingga multi-role dengan permission yang jelas.'],
                            ] as $item)
                                <li class="flex gap-3">
                                    <span class="wf-check mt-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <div>
                                        <p class="font-bold text-[var(--wf-navy)]">{{ $item[0] }}</p>
                                        <p class="text-sm text-[var(--wf-muted)] mt-0.5">{{ $item[1] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-6 sm:p-8">
                        <h2 class="text-3xl font-bold text-[var(--wf-navy)] mb-6">Digunakan oleh Seluruh Tim</h2>
                        <div class="space-y-4">
                            @foreach ([
                                ['Owner', 'Pantau kinerja perusahaan, kas, dan kepatuhan operasional.'],
                                ['Account Manager', 'Kelola prospek, proyek, target, dan simulasi paket.'],
                                ['Finance', 'Kelola transaksi, piutang, dan rekonsiliasi rekening koran.'],
                                ['Event Manager & Staff', 'Jalankan operasional harian dan absensi di lokasi.'],
                                ['HRD', 'Kelola absensi office, cuti, dan payroll.'],
                            ] as $role)
                                <div class="flex gap-3 items-start bg-white/80 rounded-xl p-4 border border-white">
                                    <div class="w-10 h-10 rounded-full bg-[var(--wf-navy)] text-white flex items-center justify-center text-sm font-bold shrink-0">
                                        {{ strtoupper(substr($role[0], 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-[var(--wf-navy)]">{{ $role[0] }}</p>
                                        <p class="text-sm text-[var(--wf-muted)]">{{ $role[1] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Security + testimonial --}}
        <section id="testimoni" class="wf-security py-14 lg:py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
                    <blockquote>
                        <p class="text-xl sm:text-2xl leading-relaxed font-medium text-white/95">
                            “Dengan WOFINS, proyek dan keuangan kami jauh lebih rapi. Absensi dan payroll juga tidak lagi dikelola terpisah-pisah.”
                        </p>
                        <footer class="mt-6 flex items-center gap-3">
                            <img src="{{ asset('images/placeholder_avatar.png') }}" alt="Nila Anggraini" class="w-12 h-12 rounded-full object-cover border-2 border-[var(--wf-gold)]">
                            <div>
                                <p class="font-bold text-white">Nila Anggraini</p>
                                <p class="text-sm text-white/70">Owner Wedding Organizer</p>
                            </div>
                        </footer>
                    </blockquote>

                    <div>
                        <h3 class="text-2xl sm:text-3xl font-bold text-white mb-6">Data Bisnis Lebih Aman</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            @foreach ([
                                ['fa-user-lock', 'Role-based access'],
                                ['fa-clock-rotate-left', 'Activity history'],
                                ['fa-check-double', 'Approval workflows'],
                                ['fa-database', 'Centralized backup'],
                                ['fa-clipboard-list', 'Audit trail'],
                            ] as $sec)
                                <div class="rounded-xl bg-white/10 border border-white/15 p-4 text-center">
                                    <i class="fa-solid {{ $sec[0] }} text-[var(--wf-gold-soft)] text-xl mb-2"></i>
                                    <p class="text-xs font-semibold text-white/90 leading-snug">{{ $sec[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="py-16 bg-white">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] text-center mb-10">FAQ</h2>
                <div class="space-y-3" x-data="{ open: 0 }">
                    @foreach ([
                        ['Apakah WOFINS khusus untuk wedding organizer?', 'Ya. Alur proyek, vendor, nota dinas, dan keuangan dirancang untuk operasional WO / EO, bukan ERP generik.'],
                        ['Apakah ada portal untuk karyawan?', 'Ada. Karyawan bisa absensi (GPS + foto), ajukan cuti/koreksi/lembur, dan melihat kompensasi lewat /profile.'],
                        ['Bisakah rekonsiliasi rekening koran?', 'Bisa. Unggah rekening koran, cocokkan transaksi, dan unduh hasil rekonsiliasi.'],
                        ['Apakah bisa dibatasi per jabatan?', 'Bisa. Role & permission mengatur akses owner, finance, HRD, account manager, dan staff.'],
                        ['Bagaimana cara mencoba?', 'Jadwalkan demo gratis melalui halaman Kontak, atau masuk jika sudah memiliki akun.'],
                    ] as $i => $faq)
                        <div class="border border-[var(--wf-line)] rounded-xl overflow-hidden">
                            <button type="button" class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left font-bold text-[var(--wf-navy)]" @click="open = open === {{ $i }} ? null : {{ $i }}">
                                <span>{{ $faq[0] }}</span>
                                <svg class="w-4 h-4 shrink-0 transition-transform" :class="open === {{ $i }} && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open === {{ $i }}" x-cloak class="px-5 pb-4 text-sm text-[var(--wf-muted)] leading-relaxed">
                                {{ $faq[1] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Final CTA --}}
        <section class="wf-cta py-16 lg:py-24">
            <div class="wf-cta-shapes" aria-hidden="true">
                <span class="blob" style="width:28rem;height:28rem;right:-6rem;top:-8rem;background:rgba(201,162,39,0.14);"></span>
                <span class="blob" style="width:22rem;height:22rem;right:18%;bottom:-8rem;background:rgba(70,140,200,0.16);"></span>
                <span class="blob" style="width:14rem;height:14rem;left:-4rem;top:20%;background:rgba(232,212,139,0.1);"></span>
                <span class="ring" style="width:20rem;height:20rem;right:8%;top:12%;"></span>
                <span class="ring" style="width:12rem;height:12rem;right:14%;top:22%;border-color:rgba(255,255,255,0.12);"></span>
                <span class="ring" style="width:8rem;height:8rem;left:42%;bottom:10%;border-color:rgba(201,162,39,0.3);"></span>
                <span class="dot" style="width:0.55rem;height:0.55rem;right:22%;top:28%;"></span>
                <span class="dot" style="width:0.4rem;height:0.4rem;right:30%;top:48%;opacity:0.7;"></span>
                <span class="dot" style="width:0.7rem;height:0.7rem;right:12%;bottom:30%;opacity:0.5;"></span>
                <span class="dot" style="width:0.35rem;height:0.35rem;left:48%;top:22%;opacity:0.6;"></span>
                <svg class="absolute right-0 top-0 h-full w-[58%] opacity-40" viewBox="0 0 600 400" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMaxYMid slice">
                    <path d="M420 40c60 30 110 90 120 160s-20 140-80 180" stroke="rgba(232,212,139,0.35)" stroke-width="1.5"/>
                    <path d="M480 20c50 40 90 110 95 180s-25 130-75 170" stroke="rgba(255,255,255,0.12)" stroke-width="1.25"/>
                    <circle cx="520" cy="120" r="48" fill="rgba(201,162,39,0.08)" stroke="rgba(232,212,139,0.25)"/>
                    <circle cx="460" cy="260" r="72" fill="rgba(56,120,180,0.08)" stroke="rgba(140,190,230,0.2)"/>
                    <circle cx="560" cy="300" r="18" fill="rgba(232,212,139,0.2)"/>
                    <rect x="390" y="150" width="56" height="56" rx="14" transform="rotate(18 418 178)" stroke="rgba(255,255,255,0.14)" fill="rgba(255,255,255,0.03)"/>
                </svg>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="wf-cta-panel p-8 sm:p-10 text-white">
                    <h2 class="text-3xl sm:text-4xl font-bold leading-tight">Saatnya Mengelola Wedding Organizer dengan Lebih Profesional</h2>
                    <p class="mt-4 text-white/80 max-w-xl">
                        Satukan proyek, keuangan, absensi, dan dokumen dalam satu platform. Ambil keputusan lebih cepat dengan data yang rapi.
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('kontak') }}" class="wf-btn-gold inline-flex items-center justify-center px-6 py-3.5 text-sm">
                            Jadwalkan Demo Gratis
                        </a>
                        <a href="{{ route('kontak') }}" class="wf-btn-outline-light inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm">
                            Konsultasikan Kebutuhan Anda
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="bg-white border-t border-[var(--wf-line)] py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="wf-display text-xl font-bold text-[var(--wf-navy)]">WOFINS</span>
                    <span class="text-xs text-[var(--wf-muted)] border-l border-[var(--wf-line)] pl-3">by Makna Finance</span>
                </div>
                <p class="text-xs text-[var(--wf-muted)]">
                    © {{ now()->year }} Makna Kreatif Indonesia. All rights reserved.
                </p>
            </div>
        </footer>

        {{-- WhatsApp --}}
        <div class="fixed bottom-6 right-6 z-50">
            <a href="https://wa.me/6281373183794?text={{ urlencode('Halo, saya ingin jadwalkan demo WOFINS.') }}"
                target="_blank" rel="noopener"
                class="group bg-[#25D366] hover:bg-[#1ebe57] text-white p-4 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center"
                aria-label="WhatsApp">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.087"/></svg>
            </a>
        </div>
    </div>
@endsection
