@extends('profile.layout')

@section('profile-page-title', 'Dokumentasi')
@section('profile-page-subtitle', 'Daftar artikel dokumentasi (khusus super admin)')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
    <form method="GET" class="flex items-center gap-3 mb-4">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari judul / keyword"
            class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Cari</button>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                    <th class="py-3 pr-4">Judul</th>
                    <th class="py-3 pr-4">Kategori</th>
                    <th class="py-3 pr-4">Publikasi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($docs as $doc)
                    <tr class="text-gray-800">
                        <td class="py-3 pr-4 font-medium">{{ $doc->title }}</td>
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ $doc->category?->name ?? '-' }}</td>
                        <td class="py-3 pr-4">
                            <span class="px-2 py-1 rounded-full text-xs {{ $doc->is_published ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $doc->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $docs->links() }}
    </div>
</div>
@endsection

