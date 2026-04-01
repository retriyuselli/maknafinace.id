@extends('layouts.app')

@section('title', 'Profile - ' . Auth::user()->name)

@section('content')
@include('front.header')

@php
    $profileUser = Auth::user();
@endphp

<div class="min-h-screen bg-gray-50 py-8" x-data="{ sidebarOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">@yield('profile-page-title', 'Dashboard Profil')</h1>
            <p class="text-gray-600 mt-2">@yield('profile-page-subtitle', 'Kelola informasi akun dan data HR Anda')</p>
        </div>

        <div class="lg:hidden mb-6">
            <button type="button"
                class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-xl px-4 py-3 shadow-sm"
                @click="sidebarOpen = !sidebarOpen">
                <span class="text-sm font-semibold text-gray-800">Menu Profil</span>
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <aside class="lg:w-64 shrink-0" :class="sidebarOpen ? 'block' : 'hidden lg:block'">
                @include('profile.partials.sidebar', ['profileUser' => $profileUser])
            </aside>

            <main class="flex-1 space-y-6">
                @yield('profile-content')
            </main>
        </div>
    </div>
</div>

@include('profile.sections.scripts')
@include('front.footer')
@endsection

