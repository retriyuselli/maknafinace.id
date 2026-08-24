@extends('layouts.app')

@section('title', 'Masuk — WOFINS')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --wf-navy: #0b1f3a;
            --wf-navy-deep: #071526;
            --wf-gold: #c9a227;
            --wf-cream: #f7f4ee;
            --wf-ink: #1a2332;
            --wf-muted: #5c6675;
            --wf-line: #e6e2d9;
        }

        .wf-auth {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(ellipse 80% 50% at 10% 20%, rgba(201, 162, 39, 0.12), transparent 55%),
                radial-gradient(ellipse 60% 40% at 90% 80%, rgba(11, 31, 58, 0.08), transparent 50%),
                linear-gradient(180deg, #fff 0%, var(--wf-cream) 100%);
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

        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('content')
    <div class="wf-auth">
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
                <div class="relative min-h-[220px] lg:min-h-[560px] text-white overflow-hidden"
                     style="background: linear-gradient(145deg, #071526 0%, #0b1f3a 55%, #14335a 100%);">
                    <span class="absolute w-40 h-40 rounded-full -right-10 -top-12 bg-[rgba(201,162,39,0.18)]" aria-hidden="true"></span>
                    <span class="absolute w-28 h-28 rounded-full left-8 bottom-16 bg-[rgba(201,162,39,0.1)]" aria-hidden="true"></span>
                    <div class="absolute inset-0 opacity-25">
                        <img src="{{ route('brand.login-image') }}" alt="" class="w-full h-full object-cover">
                    </div>
                    <div class="relative z-10 h-full flex flex-col justify-end p-8 sm:p-10">
                        <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)]">WOFINS</p>
                        <h2 class="mt-3 text-2xl sm:text-3xl font-bold leading-tight">
                            Kelola keuangan wedding organizer dengan lebih rapi
                        </h2>
                        <p class="mt-3 text-sm text-white/75 max-w-sm">
                            Proyek, rekonsiliasi, absensi, dan laporan — dalam satu sistem.
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

                        <div class="mt-8 space-y-4">
                            <a href="{{ route('auth.google') }}"
                               class="wf-btn-google w-full inline-flex items-center justify-center gap-3 px-5 py-3.5 text-sm">
                                <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                                    <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.654 32.657 29.227 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                                    <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 16.108 18.961 13 24 13c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                                    <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                                    <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 01-4.084 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
                                </svg>
                                Masuk dengan Google
                            </a>
                            <div class="wf-auth-divider">atau email</div>
                        </div>

                        <form class="mt-4 space-y-5" action="{{ route('front.login.submit') }}" method="POST">
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
