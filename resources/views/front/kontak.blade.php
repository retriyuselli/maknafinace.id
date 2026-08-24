@extends('layouts.app')

@section('title', 'Kontak — WOFINS')

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
        }

        .wf-btn-gold {
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
            border-radius: 999px;
            font-weight: 800;
        }

        .wf-contact-card {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            padding: 1.35rem;
            height: 100%;
        }

        .wf-contact-card .ornament {
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            background: rgba(201, 162, 39, 0.12);
        }

        .wf-input {
            width: 100%;
            border: 1px solid var(--wf-line);
            border-radius: 0.85rem;
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
            background: #fff;
            color: var(--wf-ink);
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .wf-input:focus {
            border-color: rgba(201, 162, 39, 0.7);
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.15);
        }

        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('content')
@php
    $paket = request('paket');
    $paketLabel = match ($paket) {
        'starter' => 'Paket Starter',
        'professional' => 'Paket Professional',
        'business' => 'Paket Business',
        'enterprise' => 'Paket Enterprise',
        default => null,
    };
@endphp

    <div class="wf-page">
        @include('front.partials.wf-nav')

        <section class="pt-12 pb-8 bg-gradient-to-b from-white to-[var(--wf-cream)]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">Kontak</p>
                <h1 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-bold text-[var(--wf-navy)] leading-tight">
                    Mari Diskusikan Kebutuhan Wedding Organizer Anda
                </h1>
                <p class="mt-4 text-[var(--wf-muted)] max-w-2xl mx-auto">
                    Jadwalkan demo gratis atau konsultasikan paket yang paling sesuai. Tim WOFINS siap membantu.
                </p>
            </div>
        </section>

        <section class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
                    @foreach ([
                        ['fa-brands fa-whatsapp', 'WhatsApp', '+62 813-7318-3794', 'https://wa.me/6281373183794'],
                        ['fa-solid fa-envelope', 'Email', 'support@wofins.id', 'mailto:support@wofins.id'],
                        ['fa-solid fa-globe', 'Website', 'wofins.id', 'https://wofins.id'],
                        ['fa-solid fa-location-dot', 'Lokasi', 'Palembang, Indonesia', null],
                    ] as $item)
                        <div class="wf-contact-card">
                            <span class="ornament w-16 h-16 -right-4 -top-4" aria-hidden="true"></span>
                            <div class="relative z-10">
                                <div class="w-10 h-10 rounded-xl bg-[rgba(201,162,39,0.12)] text-[var(--wf-gold)] inline-flex items-center justify-center mb-3">
                                    <i class="{{ $item[0] }}"></i>
                                </div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[var(--wf-muted)]">{{ $item[1] }}</p>
                                @if ($item[3])
                                    <a href="{{ $item[3] }}" class="mt-1 block font-bold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">{{ $item[2] }}</a>
                                @else
                                    <p class="mt-1 font-bold text-[var(--wf-navy)]">{{ $item[2] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid lg:grid-cols-5 gap-8 items-start">
                    <div class="lg:col-span-3 rounded-2xl border border-[var(--wf-line)] bg-white p-6 sm:p-8"
                         x-data="wfContactForm(@js($paketLabel))">
                        <h2 class="text-2xl font-bold text-[var(--wf-navy)]">Kirim pesan / jadwalkan demo</h2>
                        <p class="mt-2 text-sm text-[var(--wf-muted)]">
                            Isi formulir — pesan akan dibuka di WhatsApp agar kami bisa merespons lebih cepat.
                        </p>

                        @if ($paketLabel)
                            <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-[rgba(201,162,39,0.12)] px-3 py-1.5 text-xs font-bold text-[#9a7a12]">
                                <i class="fa-solid fa-tag"></i>
                                Tertarik: {{ $paketLabel }}
                            </div>
                        @endif

                        <form class="mt-6 space-y-4" @submit.prevent="submit()">
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Nama lengkap *</label>
                                    <input type="text" class="wf-input" x-model="form.name" placeholder="Nama Anda" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Nama WO / perusahaan</label>
                                    <input type="text" class="wf-input" x-model="form.company" placeholder="Nama wedding organizer">
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Email *</label>
                                    <input type="email" class="wf-input" x-model="form.email" placeholder="email@domain.com" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">WhatsApp *</label>
                                    <input type="tel" class="wf-input" x-model="form.phone" placeholder="08xxxxxxxxxx" required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Kebutuhan *</label>
                                <select class="wf-input" x-model="form.need" required>
                                    <option value="">Pilih kebutuhan</option>
                                    <option value="Demo gratis">Jadwalkan demo gratis</option>
                                    <option value="Konsultasi paket">Konsultasi paket harga</option>
                                    <option value="Pertanyaan fitur">Pertanyaan fitur</option>
                                    <option value="Onboarding">Onboarding / migrasi</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Pesan *</label>
                                <textarea class="wf-input" rows="5" x-model="form.message" placeholder="Ceritakan singkat kebutuhan WO Anda..." required></textarea>
                            </div>

                            <p x-show="error" x-cloak class="text-sm text-red-600" x-text="error"></p>

                            <button type="submit" class="wf-btn-navy w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm">
                                <i class="fa-brands fa-whatsapp text-lg"></i>
                                Kirim via WhatsApp
                            </button>
                        </form>
                    </div>

                    <div class="lg:col-span-2 space-y-4">
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-6">
                            <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-3">Jam respons</h3>
                            <ul class="space-y-2 text-sm text-[var(--wf-muted)]">
                                <li class="flex gap-2"><i class="fa-solid fa-clock mt-0.5 text-[var(--wf-gold)]"></i> Senin–Jumat · 09:00–17:00 WIB</li>
                                <li class="flex gap-2"><i class="fa-solid fa-bolt mt-0.5 text-[var(--wf-gold)]"></i> Demo biasanya bisa dijadwalkan dalam 1–2 hari kerja</li>
                            </ul>
                        </div>

                        <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-6">
                            <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-3">Yang bisa kami bantu</h3>
                            <ul class="space-y-2.5 text-sm text-[var(--wf-ink)]">
                                @foreach ([
                                    'Demo walkthrough modul WOFINS',
                                    'Rekomendasi paket sesuai ukuran tim',
                                    'Diskusi onboarding & migrasi data',
                                    'Pertanyaan fitur absensi, keuangan, rekonsiliasi',
                                ] as $help)
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 w-5 h-5 rounded-full bg-[rgba(201,162,39,0.15)] text-[var(--wf-gold)] inline-flex items-center justify-center text-[0.6rem] shrink-0">
                                            <i class="fa-solid fa-check"></i>
                                        </span>
                                        {{ $help }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="rounded-2xl overflow-hidden relative text-white p-6"
                             style="background: linear-gradient(135deg, #071526 0%, #0b1f3a 60%, #14335a 100%);">
                            <span class="absolute w-24 h-24 rounded-full -right-6 -top-8 bg-[rgba(201,162,39,0.16)]" aria-hidden="true"></span>
                            <div class="relative z-10">
                                <h3 class="text-lg font-bold">Lebih cepat via WhatsApp</h3>
                                <p class="mt-2 text-sm text-white/75">Chat langsung dengan tim sales WOFINS.</p>
                                <a href="https://wa.me/6281373183794?text={{ urlencode('Halo, saya ingin jadwalkan demo WOFINS.') }}"
                                   class="wf-btn-gold inline-flex items-center justify-center gap-2 mt-5 px-5 py-3 text-sm">
                                    <i class="fa-brands fa-whatsapp"></i>
                                    Chat sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @include('front.partials.wf-footer')
    </div>

    <script>
        function wfContactForm(paketLabel) {
            return {
                error: '',
                form: {
                    name: '',
                    company: '',
                    email: '',
                    phone: '',
                    need: paketLabel ? 'Konsultasi paket' : '',
                    message: paketLabel ? `Saya tertarik dengan ${paketLabel} dan ingin konsultasi lebih lanjut.` : '',
                },
                submit() {
                    this.error = '';
                    const f = this.form;
                    if (!f.name || !f.email || !f.phone || !f.need || !f.message) {
                        this.error = 'Mohon lengkapi semua field yang wajib diisi.';
                        return;
                    }

                    let text = `Halo, saya ingin menghubungi tim WOFINS.\n\n`;
                    text += `Nama: ${f.name}\n`;
                    if (f.company) text += `WO/Perusahaan: ${f.company}\n`;
                    text += `Email: ${f.email}\n`;
                    text += `WhatsApp: ${f.phone}\n`;
                    text += `Kebutuhan: ${f.need}\n`;
                    if (paketLabel) text += `Paket: ${paketLabel}\n`;
                    text += `\nPesan:\n${f.message}`;

                    window.open(`https://wa.me/6281373183794?text=${encodeURIComponent(text)}`, '_blank');
                }
            }
        }
    </script>
@endsection
