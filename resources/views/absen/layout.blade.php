<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Absensi') — Makna</title>
    <link rel="icon" type="image/png" href="{{ route('brand.favicon') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js" defer></script>
    <style>
        :root {
            --absen-bg: #f3f4f6;
            --absen-card: #ffffff;
            --absen-ink: #4b5563;
            --absen-muted: #9ca3af;
            --absen-icon: #9aa7e6;
            --absen-bar: #eceff3;
            --absen-active: #dfe3ea;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        html, body, button, input, select, textarea {
            font-family: 'Poppins', system-ui, sans-serif;
        }
        body {
            background: var(--absen-bg);
            color: var(--absen-ink);
        }

        .absen-app {
            max-width: 430px;
            margin: 0 auto;
            min-height: 100dvh;
            padding: 18px 18px calc(92px + env(safe-area-inset-bottom));
            position: relative;
        }

        .absen-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .absen-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: inherit;
        }

        .absen-brand img,
        .absen-mw {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            object-fit: cover;
        }

        .absen-mw {
            display: grid;
            place-items: center;
            background: #111827;
            color: #fff;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: -0.04em;
        }

        .absen-wofins {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1;
            color: #0b1f3a;
            text-transform: lowercase;
        }

        .absen-logo-fallback {
            display: none;
            height: 28px;
            width: auto;
            max-width: 140px;
            object-fit: contain;
        }

        .absen-avatar {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            background: #e5e7eb;
            overflow: hidden;
            display: grid;
            place-items: center;
            text-decoration: none;
        }

        .absen-avatar img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            display: block;
        }

        .absen-hello {
            margin: 28px 0 22px;
        }

        .absen-hello h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            color: #374151;
        }

        .absen-hello p {
            margin: 6px 0 0;
            font-size: 13px;
            color: var(--absen-muted);
        }

        .absen-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .absen-card {
            background: var(--absen-card);
            border-radius: 18px;
            min-height: 118px;
            padding: 18px 12px 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            color: var(--absen-ink);
            border: 0;
            box-shadow: 0 1px 0 rgba(15, 23, 42, 0.02);
            cursor: pointer;
            font: inherit;
            width: 100%;
        }

        .absen-card svg {
            width: 42px;
            height: 42px;
            stroke: var(--absen-icon);
            fill: none;
            stroke-width: 1.6;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .absen-card span {
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
        }

        .absen-nav {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            bottom: calc(12px + env(safe-area-inset-bottom));
            width: min(390px, calc(100% - 24px));
            background: var(--absen-bar);
            border-radius: 18px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            padding: 8px;
        }

        .absen-nav a {
            height: 46px;
            display: grid;
            place-items: center;
            color: #9ca3af;
            text-decoration: none;
            border-radius: 14px;
        }

        .absen-nav a.is-active {
            background: var(--absen-active);
            color: #6b7280;
        }

        [x-cloak], .hidden { display: none !important; }

        .absen-toast {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            bottom: calc(88px + env(safe-area-inset-bottom));
            background: #111827;
            color: #fff;
            font-size: 13px;
            padding: 10px 14px;
            border-radius: 999px;
            z-index: 20;
        }

        .absen-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: grid;
            place-items: center;
            padding: 24px;
            z-index: 40;
        }

        .absen-modal {
            width: min(340px, 100%);
            background: #fff;
            border-radius: 18px;
            padding: 22px 18px 16px;
            text-align: center;
            font-family: 'Poppins', system-ui, sans-serif;
        }

        .absen-modal p {
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
            color: #9f1239;
            font-weight: 500;
        }

        .absen-modal button {
            margin-top: 16px;
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 12px;
            background: #0b1f3a;
            color: #fff;
            font-family: inherit;
            font-weight: 700;
        }
    </style>
    @yield('styles')
</head>
<body>
    @php
        $absenUser = auth()->user();
        $absenAvatar = $absenUser?->avatar_url
            ? \Illuminate\Support\Facades\Storage::url($absenUser->avatar_url)
            : 'https://ui-avatars.com/api/?name='.urlencode($absenUser->name ?? 'User').'&background=9aa7e6&color=fff&size=128';
        $absenAvatarFallback = 'https://ui-avatars.com/api/?name='.urlencode($absenUser->name ?? 'User').'&background=9aa7e6&color=fff&size=128';
    @endphp
    <div class="absen-app" x-data="{
        toast: '',
        alertOpen: {{ ($errors->any() || session('error')) ? 'true' : 'false' }},
        alertText: {{ \Illuminate\Support\Js::from($errors->any() ? $errors->first() : (session('error') ?? '')) }}
    }">
        <header class="absen-top">
            <a href="{{ route('absen.home') }}" class="absen-brand" aria-label="wofins">
                <span class="absen-wofins">wofins</span>
                <img src="{{ route('brand.logo') }}" alt="wofins" class="absen-logo-fallback">
            </a>
            <a href="{{ route('profile') }}" class="absen-avatar" aria-label="Profil">
                <img src="{{ $absenAvatar }}" alt="{{ $absenUser->name ?? 'User' }}"
                    onerror="this.onerror=null;this.src='{{ $absenAvatarFallback }}';">
            </a>
        </header>

        @yield('content')

        <nav class="absen-nav">
            <a href="{{ route('absen.home') }}" class="{{ request()->routeIs('absen.home') ? 'is-active' : '' }}" aria-label="Home">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 10.5 12 4l8 6.5V20a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 20z"/><path d="M9 21.5V12h6v9.5"/></svg>
            </a>
            <a href="{{ route('profile.absensi') }}" aria-label="Riwayat">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M8 3h8a2 2 0 0 1 2 2v16l-6-3-6 3V5a2 2 0 0 1 2-2z"/><path d="M12 8v6m0 0 2.5-2.5M12 14l-2.5-2.5"/></svg>
            </a>
            <a href="{{ route('absen.more') }}" class="{{ request()->routeIs('absen.more') ? 'is-active' : '' }}" aria-label="Lainnya">
                <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><circle cx="6" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="18" cy="12" r="1.6"/></svg>
            </a>
        </nav>

        <div class="absen-toast" x-show="toast" x-cloak x-text="toast" x-transition></div>

        <div class="absen-modal-backdrop" x-show="alertOpen && alertText" x-cloak @click.self="alertOpen = false">
            <div class="absen-modal">
                <p x-text="alertText"></p>
                <button type="button" @click="alertOpen = false">OK</button>
            </div>
        </div>
    </div>
    @stack('scripts')
    <script>
        document.fonts.ready.then(function () {
            var wordmark = document.querySelector('.absen-wofins');
            var logo = document.querySelector('.absen-logo-fallback');
            if (!wordmark || !logo) return;
            var ok = document.fonts.check('800 22px Poppins') || document.fonts.check('800 22px "Poppins"');
            if (!ok) {
                wordmark.style.display = 'none';
                logo.style.display = 'block';
            }
        });
    </script>
</body>
</html>
