@extends('layouts.app')

@section('title', 'Masuk — WOFINS')

@push('styles')
@include('front.partials.wf-front-base-styles')
<style>
        .wf-auth {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 80% 50% at 10% 20%, rgba(201, 162, 39, 0.12), transparent 55%),
                radial-gradient(ellipse 60% 40% at 90% 80%, rgba(11, 31, 58, 0.08), transparent 50%),
                linear-gradient(180deg, #fff 0%, var(--wf-cream) 100%);
        }

        .wf-auth > header,
        .wf-auth > .wf-auth-main {
            position: relative;
            z-index: 1;
        }

        .wf-auth-main {
            flex: 1;
            display: flex;
            align-items: center;
            padding-top: 2.5rem;
            padding-bottom: 3.5rem;
        }

        @media (min-width: 640px) {
            .wf-auth-main {
                padding-top: 3.5rem;
                padding-bottom: 4rem;
            }
        }

        .wf-auth-panel {
            position: relative;
            overflow: hidden;
        }

        .wf-auth-panel .wf-deco__blob--a {
            opacity: 0.55;
            background: radial-gradient(circle at 30% 30%, rgba(201, 162, 39, 0.5), transparent 70%);
        }

        .wf-auth-panel .wf-deco__ring--a,
        .wf-auth-panel .wf-deco__ring--b {
            border-color: rgba(255, 255, 255, 0.18);
        }

        .wf-auth-panel .wf-deco__sq--a {
            border-color: rgba(201, 162, 39, 0.45);
        }

        .wf-auth-panel .wf-deco__dot--a {
            background: var(--wf-gold-soft);
            opacity: 0.7;
        }

        .wf-auth-input {
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

        .wf-auth-input:focus {
            border-color: rgba(201, 162, 39, 0.7);
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.15);
        }

        .wf-btn-google {
            border: 1.5px solid var(--wf-line);
            color: var(--wf-ink);
            background: #fff;
            border-radius: 999px;
            font-weight: 700;
            transition: background .2s ease, border-color .2s ease, transform .2s ease;
        }

        .wf-btn-google:hover {
            background: var(--wf-cream);
            border-color: #d0cbc0;
            transform: translateY(-1px);
        }

        .wf-auth-divider {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            color: var(--wf-muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .wf-auth-divider::before,
        .wf-auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--wf-line);
        }
    </style>
@endpush

@section('content')
    <div class="wf-auth">
        @include('front.partials.wf-deco-shapes')

        <header class="shrink-0 border-b border-[var(--wf-line)]/70 bg-white/70 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-2xl font-bold text-[var(--wf-navy)] tracking-wide">
                    WOFINS
                </a>
            </div>
        </header>

        <div class="wf-auth-main">
        <div class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 rounded-3xl overflow-hidden border border-[var(--wf-line)] bg-white shadow-[0_24px_60px_-28px_rgba(11,31,58,0.35)]">
                <div class="wf-auth-panel relative min-h-[220px] lg:min-h-[560px] text-white"
                     style="background: linear-gradient(145deg, #071526 0%, #0b1f3a 55%, #14335a 100%);">
                    @include('front.partials.wf-deco-shapes')
                    <div class="absolute inset-0 opacity-25">
                        <img src="{{ route('brand.login-image') }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async">
                    </div>
                    <div class="relative z-10 h-full flex flex-col justify-end p-8 sm:p-10">
                        <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)]">WOFINS</p>
                        <h2 class="mt-3 text-2xl sm:text-3xl font-bold leading-tight">
                            Kelola keuangan wedding organizer dengan lebih rapi
                        </h2>
                        <p class="mt-3 text-sm text-white/75 max-w-sm">
                            Proyek, rekonsiliasi, payroll, dan laporan — dalam satu sistem.
                        </p>
                    </div>
                </div>

                <div class="p-6 sm:p-10 flex items-center">
                    <div class="w-full max-w-md mx-auto">
                        <h1 class="text-3xl font-bold text-[var(--wf-navy)]">Masuk</h1>
                        <p class="mt-1 text-sm text-[var(--wf-muted)]">Masuk ke akun WOFINS Anda.</p>

                        @if (session('status'))
                            <div class="mt-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200">
                                <p class="text-sm text-emerald-700">{{ session('status') }}</p>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="mt-4 p-3 rounded-xl bg-red-50 border border-red-200">
                                <p class="text-sm text-red-700">{{ session('error') }}</p>
                            </div>
                        @endif
                        @if (session('warning'))
                            <div class="mt-4 p-3 rounded-xl bg-amber-50 border border-amber-200">
                                <p class="text-sm text-amber-800">{{ session('warning') }}</p>
                            </div>
                        @endif

                        <form class="mt-8 space-y-5" action="{{ route('front.login.submit') }}" method="POST">
                            @csrf
                            <div>
                                <label for="email-address" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Email</label>
                                <input id="email-address" name="email" type="email" autocomplete="username" autofocus required
                                    class="wf-auth-input"
                                    placeholder="nama@email.com" value="{{ old('email') }}">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-data="{ show: false }">
                                <label for="password" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Password</label>
                                <div class="relative">
                                    <input id="password" name="password" :type="show ? 'text' : 'password'"
                                        autocomplete="current-password" required
                                        class="wf-auth-input pr-11"
                                        placeholder="Masukkan password">
                                    <button type="button" @click="show = !show"
                                        class="absolute inset-y-0 right-3 flex items-center text-[var(--wf-muted)] hover:text-[var(--wf-navy)]">
                                        <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <label class="inline-flex items-center gap-2 text-sm text-[var(--wf-muted)] cursor-pointer">
                                    <input type="checkbox" name="remember" value="1"
                                        class="rounded border-[var(--wf-line)] text-[var(--wf-navy)] focus:ring-[var(--wf-gold)]">
                                    Ingat saya
                                </label>
                                <a href="{{ route('front.password.request') }}"
                                    class="text-sm font-semibold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">
                                    Lupa password?
                                </a>
                            </div>

                            <button type="submit" class="wf-btn-navy w-full inline-flex items-center justify-center px-5 py-3.5 text-sm">
                                Masuk
                            </button>

                            <p class="text-center text-sm text-[var(--wf-muted)]">
                                Belum punya akun?
                                <a href="{{ route('front.register') }}" class="font-bold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">Daftar sekarang</a>
                            </p>
                            <p class="text-center text-xs text-[var(--wf-muted)]">
                                Butuh demo?
                                <a href="{{ route('kontak') }}" class="font-semibold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">Hubungi kami</a>
                                ·
                                <a href="mailto:support@wofins.id" class="font-semibold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">support@wofins.id</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection
