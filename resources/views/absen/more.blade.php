@extends('absen.layout')

@section('title', 'Lainnya')

@section('content')
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
