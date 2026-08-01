@extends('profile.layout')

@section('profile-page-title', 'Pusat Bantuan')
@section('profile-page-subtitle', 'Akses cepat ke panduan dan dokumentasi')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
    <div class="space-y-3 text-sm">
        <a href="{{ route('profile.admin-tools.documentations') }}" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="font-semibold text-gray-900">Dokumentasi Internal</div>
            <div class="mt-1 text-xs text-gray-600">Kelola artikel panduan dan catatan operasional</div>
        </a>

        <a href="/docs" class="block border border-gray-200 rounded-xl p-5 hover:bg-gray-50 transition">
            <div class="font-semibold text-gray-900">Docs Publik</div>
            <div class="mt-1 text-xs text-gray-600">Halaman dokumentasi publik aplikasi</div>
        </a>
    </div>
</div>
@endsection

