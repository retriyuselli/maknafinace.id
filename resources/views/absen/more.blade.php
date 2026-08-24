@extends('absen.layout')

@section('title', 'Lainnya')

@section('content')
    <header class="absen-top">
        <a href="{{ route('absen.home') }}" class="absen-brand">
            <img src="{{ route('brand.logo') }}" alt="Makna">
            <div>
                <div class="absen-brand-name">MAKNA</div>
                <div class="absen-brand-sub">WEDDING &amp; EVENT PLANNER</div>
            </div>
        </a>
        <a href="{{ route('profile') }}" class="absen-avatar" aria-label="Profil">
            <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12zm0 2c-4.2 0-8 2.1-8 4.7V21h16v-2.3c0-2.6-3.8-4.7-8-4.7z"/></svg>
        </a>
    </header>

    <div class="absen-hello">
        <h1>More</h1>
        <p>{{ $user->name }}</p>
    </div>

    <div class="absen-grid">
        <a class="absen-card" href="{{ route('profile') }}">
            <svg viewBox="0 0 48 48"><circle cx="24" cy="16" r="7"/><path d="M10 38c1.5-8 6.5-12 14-12s12.5 4 14 12"/></svg>
            <span>Profil</span>
        </a>
        <a class="absen-card" href="{{ route('profile.absensi') }}">
            <svg viewBox="0 0 48 48"><rect x="12" y="8" width="24" height="32" rx="3"/><path d="M18 16h12M18 22h12M18 28h8"/></svg>
            <span>Riwayat Absen</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="contents">
            @csrf
            <button type="submit" class="absen-card">
                <svg viewBox="0 0 48 48"><path d="M20 8H12a4 4 0 0 0-4 4v24a4 4 0 0 0 4 4h8"/><path d="M20 24h20m0 0-6-6m6 6-6 6"/></svg>
                <span>Keluar</span>
            </button>
        </form>
    </div>
@endsection
