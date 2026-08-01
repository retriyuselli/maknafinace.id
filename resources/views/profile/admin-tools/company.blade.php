@extends('profile.layout')

@section('profile-page-title', 'Pengaturan Perusahaan')
@section('profile-page-subtitle', 'Ringkasan data perusahaan (khusus super admin)')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
    @if(! $company)
        <div class="text-sm text-gray-600">Belum ada data perusahaan.</div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <div class="text-xs text-gray-500">Nama Perusahaan</div>
                <div class="font-semibold text-gray-900">{{ $company->company_name }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Owner</div>
                <div class="font-semibold text-gray-900">{{ $company->owner_name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Email</div>
                <div class="font-semibold text-gray-900">{{ $company->email ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Telepon</div>
                <div class="font-semibold text-gray-900">{{ $company->phone ?? '-' }}</div>
            </div>
            <div class="md:col-span-2">
                <div class="text-xs text-gray-500">Alamat</div>
                <div class="font-semibold text-gray-900">{{ $company->address ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Website</div>
                <div class="font-semibold text-gray-900">{{ $company->website ?? '-' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500">Logo</div>
                @if ($company->logo_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo_url))
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_url) }}"
                        alt="Logo" class="h-10 w-auto mt-1 rounded border border-gray-200 bg-white">
                @else
                    <div class="font-semibold text-gray-900">-</div>
                @endif
            </div>
            <div>
                <div class="text-xs text-gray-500">Favicon</div>
                @if ($company->favicon_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->favicon_url))
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->favicon_url) }}"
                        alt="Favicon" class="h-8 w-8 mt-1 rounded border border-gray-200 bg-white">
                @else
                    <div class="font-semibold text-gray-900">-</div>
                @endif
            </div>
            <div class="md:col-span-2">
                <div class="text-xs text-gray-500">Image Login</div>
                @if ($company->image_login && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->image_login))
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->image_login) }}"
                        alt="Image Login" class="h-12 w-auto mt-1 rounded border border-gray-200 bg-white">
                @else
                    <div class="font-semibold text-gray-900">-</div>
                @endif
            </div>
            <div>
                <div class="text-xs text-gray-500">Updated</div>
                <div class="font-semibold text-gray-900">{{ optional($company->updated_at)->diffForHumans() }}</div>
            </div>
        </div>
    @endif
</div>
@endsection
