@extends('profile.layout')

@section('profile-page-title', 'Admin Tools')
@section('profile-page-subtitle', 'Menu khusus super admin untuk monitoring dan administrasi')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('profile.admin-tools.users') }}" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="text-sm font-semibold text-gray-900">Manajemen Pengguna</div>
            <div class="mt-1 text-xs text-gray-600">{{ number_format($usersCount) }} pengguna</div>
        </a>

        <a href="{{ route('profile.admin-tools.roles') }}" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="text-sm font-semibold text-gray-900">Role & Permission</div>
            <div class="mt-1 text-xs text-gray-600">{{ number_format($rolesCount) }} role</div>
        </a>

        <a href="{{ route('profile.admin-tools.company') }}" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="text-sm font-semibold text-gray-900">Pengaturan Perusahaan</div>
            <div class="mt-1 text-xs text-gray-600">{{ number_format($companiesCount) }} data</div>
        </a>

        <a href="{{ route('profile.admin-tools.branding') }}" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="text-sm font-semibold text-gray-900">Logo & Branding</div>
            <div class="mt-1 text-xs text-gray-600">{{ number_format($logosCount) }} logo</div>
        </a>

        <a href="{{ route('profile.admin-tools.sops') }}" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="text-sm font-semibold text-gray-900">SOP</div>
            <div class="mt-1 text-xs text-gray-600">{{ number_format($sopsCount) }} SOP</div>
        </a>

        <a href="{{ route('profile.admin-tools.projects') }}" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="text-sm font-semibold text-gray-900">Proyek Wedding</div>
            <div class="mt-1 text-xs text-gray-600">{{ number_format($projectsCount) }} proyek</div>
        </a>

        <a href="{{ route('profile.admin-tools.documentations') }}" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="text-sm font-semibold text-gray-900">Dokumentasi</div>
            <div class="mt-1 text-xs text-gray-600">{{ number_format($documentationsCount) }} artikel</div>
        </a>

        <a href="{{ route('profile.admin-tools.document-categories') }}" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="text-sm font-semibold text-gray-900">Kategori Dokumen</div>
            <div class="mt-1 text-xs text-gray-600">{{ number_format($documentCategoriesCount) }} kategori</div>
        </a>

        <a href="{{ route('profile.admin-tools.help-center') }}" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="text-sm font-semibold text-gray-900">Pusat Bantuan</div>
            <div class="mt-1 text-xs text-gray-600">Panduan penggunaan</div>
        </a>
    </div>
</div>
@endsection

