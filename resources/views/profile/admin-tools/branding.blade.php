@extends('profile.layout')

@section('profile-page-title', 'Logo & Branding')
@section('profile-page-subtitle', 'Daftar logo perusahaan/partner (khusus super admin)')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                    <th class="py-3 pr-4">Perusahaan</th>
                    <th class="py-3 pr-4">Kategori</th>
                    <th class="py-3 pr-4">Aktif</th>
                    <th class="py-3 pr-4">Urutan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($logos as $logo)
                    <tr class="text-gray-800">
                        <td class="py-3 pr-4 font-medium">{{ $logo->company_name }}</td>
                        <td class="py-3 pr-4">{{ $logo->category ?? '-' }}</td>
                        <td class="py-3 pr-4">
                            <span class="px-2 py-1 rounded-full text-xs {{ $logo->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $logo->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="py-3 pr-4 text-xs text-gray-600">{{ (int) $logo->display_order }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $logos->links() }}
    </div>
</div>
@endsection

