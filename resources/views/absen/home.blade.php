@extends('absen.layout')

@section('title', 'Absensi')

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
        <h1>{{ $greeting }}</h1>
        <p>Stay safe by following health protocols</p>
        @if (session('success'))
            <p style="color:#047857;margin-top:8px;">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p style="color:#be123c;margin-top:8px;">{{ session('error') }}</p>
        @endif
        <p style="margin-top:8px;font-size:12px;color:#6b7280;">Masuk {{ $jamMasuk }} · Pulang {{ $jamPulang }}</p>
    </div>

    <div class="absen-grid">
        @if ($canMasuk)
            <a class="absen-card" href="{{ route('absen.clock', 'masuk') }}">
                <svg viewBox="0 0 48 48"><rect x="10" y="8" width="20" height="28" rx="3"/><path d="M14 14h12M14 19h12M14 24h8"/><path d="M30 20h8v16a3 3 0 0 1-3 3H18"/><circle cx="34" cy="32" r="5"/><path d="M34 30v3h2"/></svg>
                <span>Clock In</span>
            </a>
        @else
            <button type="button" class="absen-card" @click="toast = 'Clock In sudah tercatat atau belum tersedia'; setTimeout(() => toast = '', 2200)">
                <svg viewBox="0 0 48 48"><rect x="10" y="8" width="20" height="28" rx="3"/><path d="M14 14h12M14 19h12M14 24h8"/><path d="M30 20h8v16a3 3 0 0 1-3 3H18"/><circle cx="34" cy="32" r="5"/><path d="M34 30v3h2"/></svg>
                <span>Clock In</span>
            </button>
        @endif

        @if ($canPulang)
            <a class="absen-card" href="{{ route('absen.clock', 'pulang') }}">
                <svg viewBox="0 0 48 48"><rect x="10" y="8" width="20" height="28" rx="3"/><path d="M14 14h12M14 19h12M14 24h8"/><path d="M18 39h16a3 3 0 0 0 3-3V20"/><circle cx="34" cy="32" r="5"/><path d="M34 34v-3h2"/></svg>
                <span>Clock Out</span>
            </a>
        @else
            <button type="button" class="absen-card" @click="toast = '{{ $canMasuk ? 'Clock Out setelah Clock In' : 'Clock Out sudah tercatat atau belum tersedia' }}'; setTimeout(() => toast = '', 2200)">
                <svg viewBox="0 0 48 48"><rect x="10" y="8" width="20" height="28" rx="3"/><path d="M14 14h12M14 19h12M14 24h8"/><path d="M18 39h16a3 3 0 0 0 3-3V20"/><circle cx="34" cy="32" r="5"/><path d="M34 34v-3h2"/></svg>
                <span>Clock Out</span>
            </button>
        @endif

        <button type="button" class="absen-card" @click="toast = 'Fitur Break segera hadir'; setTimeout(() => toast = '', 2200)">
            <svg viewBox="0 0 48 48"><path d="M16 20c4 6 12 6 16 0"/><path d="M18 20V14h4v8M26 20V14h4v8"/><path d="M12 32h24v4H16a4 4 0 0 1-4-4z"/></svg>
            <span>Break</span>
        </button>

        <button type="button" class="absen-card" @click="toast = 'Fitur After break segera hadir'; setTimeout(() => toast = '', 2200)">
            <svg viewBox="0 0 48 48"><rect x="8" y="22" width="32" height="12" rx="2"/><path d="M12 22V18h6v4M22 22V16h12v6"/><path d="M28 10c3 0 6 2 6 5"/></svg>
            <span>After break</span>
        </button>

        <button type="button" class="absen-card" @click="toast = 'Fitur Overtime In segera hadir'; setTimeout(() => toast = '', 2200)">
            <svg viewBox="0 0 48 48"><circle cx="24" cy="22" r="12"/><path d="M24 16v6l4 2"/><circle cx="33" cy="34" r="6"/><path d="M31.5 34h3l-1.5-2.5"/></svg>
            <span>Overtime In</span>
        </button>

        <button type="button" class="absen-card" @click="toast = 'Fitur Overtime Out segera hadir'; setTimeout(() => toast = '', 2200)">
            <svg viewBox="0 0 48 48"><circle cx="24" cy="22" r="12"/><path d="M24 16v6l4 2"/><circle cx="33" cy="34" r="6"/><rect x="31" y="32.2" width="4" height="3.6" rx="0.6"/></svg>
            <span>Overtime Out</span>
        </button>

        <button type="button" class="absen-card" @click="toast = 'Fitur Visit In segera hadir'; setTimeout(() => toast = '', 2200)">
            <svg viewBox="0 0 48 48"><path d="M24 8c-6.6 0-12 5.1-12 11.4 0 8.4 12 20.6 12 20.6s12-12.2 12-20.6C36 13.1 30.6 8 24 8z"/><circle cx="24" cy="19" r="4.5"/></svg>
            <span>Visit In</span>
        </button>

        <button type="button" class="absen-card" @click="toast = 'Fitur Visit Out segera hadir'; setTimeout(() => toast = '', 2200)">
            <svg viewBox="0 0 48 48"><circle cx="18" cy="14" r="5"/><path d="M10 32c1.2-6 5-9 8-9s4.4 1.4 6 4"/><path d="M24 26l7 3 8-8"/><path d="M28 34h12v6H31"/></svg>
            <span>Visit Out</span>
        </button>

        <button type="button" class="absen-card" @click="toast = 'Fitur Reimbursement segera hadir'; setTimeout(() => toast = '', 2200)">
            <svg viewBox="0 0 48 48"><circle cx="24" cy="24" r="13"/><path d="M24 16v16M20 19.5c1.2-1.3 8-2.2 8 1.8s-8 1.7-8 4.8 6.8 3.2 8 1.8"/></svg>
            <span>Reimbursement</span>
        </button>

        <a class="absen-card" href="{{ route('profile.absensi') }}">
            <svg viewBox="0 0 48 48"><path d="M10 34c4-8 8-12 14-12s10 4 14 12"/><path d="M10 34h28"/><circle cx="33" cy="16" r="7"/><path d="M33 13v4h3"/></svg>
            <span>Timesheet</span>
        </a>

        <button type="button" class="absen-card" @click="toast = 'Fitur Hadir Sales segera hadir'; setTimeout(() => toast = '', 2200)">
            <svg viewBox="0 0 48 48"><rect x="14" y="16" width="20" height="16" rx="2"/><path d="M18 16V13h12v3M14 24h20"/><path d="M24 8v4m0 24v4"/></svg>
            <span>Hadir Sales</span>
        </button>
    </div>
@endsection
