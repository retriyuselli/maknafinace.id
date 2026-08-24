@extends('layouts.app')

@section('title', 'Keamanan — WOFINS')

@push('styles')
@include('front.partials.wf-front-base-styles')
@endpush

@section('content')
    <div class="wf-page">
        @include('front.partials.wf-nav')

        <section class="wf-hero pt-14 pb-12 bg-gradient-to-b from-white to-[var(--wf-cream)]">
            @include('front.partials.wf-deco-shapes')
            <div class="wf-hero-inner max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">Keamanan</p>
                <h1 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] leading-tight">
                    Data bisnis Anda dilindungi di setiap lapisan akses.
                </h1>
                <p class="mt-4 text-[var(--wf-muted)] text-base sm:text-lg leading-relaxed">
                    WOFINS dirancang untuk Wedding Organizer yang membutuhkan kontrol akses, jejak aktivitas, dan isolasi data per tim.
                </p>
            </div>
        </section>

        <section class="wf-section-deco py-14 bg-white">
            <div class="wf-deco" aria-hidden="true">
                <span class="wf-deco__ring wf-deco__ring--b" style="top: 8%; right: 4%; bottom: auto;"></span>
                <span class="wf-deco__dot wf-deco__dot--a"></span>
                <span class="wf-deco__sq wf-deco__sq--b"></span>
            </div>
            <div class="wf-section-inner max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-2 gap-6">
                    @foreach ([
                        ['fa-user-shield', 'Akses berbasis peran', 'Hak akses mengikuti jabatan (role) sehingga tiap anggota tim hanya membuka menu yang relevan.'],
                        ['fa-people-arrows', 'Isolasi data per tim', 'Non–super admin hanya melihat data milik timnya — proyek, vendor, keuangan, dan dokumen terkait.'],
                        ['fa-clock-rotate-left', 'Audit trail', 'Perubahan penting tercatat dalam riwayat aktivitas untuk penelusuran dan kepatuhan internal.'],
                        ['fa-file-signature', 'Approval digital', 'Nota dinas, cuti, dan dokumen dapat melalui alur persetujuan sesuai peran pengguna.'],
                        ['fa-database', 'Backup terpusat', 'Data aplikasi dikelola di infrastruktur berlangganan dengan praktik backup sesuai lingkungan hosting.'],
                        ['fa-lock', 'Sesi & akun aman', 'Login terproteksi; pemilik paket mengelola kursi pengguna sesuai kuota langganan.'],
                    ] as [$icon, $title, $desc])
                        <div class="wf-info-card">
                            <span class="wf-info-icon"><i class="fa-solid {{ $icon }}"></i></span>
                            <h2 class="text-lg font-bold text-[var(--wf-navy)] mt-4">{{ $title }}</h2>
                            <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">{{ $desc }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="wf-cta-panel mt-12">
                    @include('front.partials.wf-deco-shapes')
                    <p class="text-white/90 text-base sm:text-lg font-medium max-w-2xl mx-auto">
                        Butuh penjelasan keamanan untuk tim atau klien Anda? Tim kami siap membantu saat demo.
                    </p>
                    <a href="{{ route('kontak') }}" class="wf-btn-gold inline-flex mt-5 px-6 py-3 text-sm">Hubungi Kami</a>
                </div>
            </div>
        </section>

        @include('front.partials.wf-footer')
    </div>
@endsection
