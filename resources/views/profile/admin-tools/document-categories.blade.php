@extends('profile.layout')

@section('profile-page-title', 'Kategori Dokumen')
@section('profile-page-subtitle', 'Daftar kategori dokumen (khusus super admin)')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                    <th class="py-3 pr-4">Nama</th>
                    <th class="py-3 pr-4">Kode</th>
                    <th class="py-3 pr-4">Tipe</th>
                    <th class="py-3 pr-4">Parent</th>
                    <th class="py-3 pr-4">Approval</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($categories as $cat)
                    <tr class="text-gray-800">
                        <td class="py-3 pr-4 font-medium">{{ $cat->name }}</td>
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ $cat->code ?? '-' }}</td>
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ $cat->type ?? '-' }}</td>
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ $cat->parent?->name ?? '-' }}</td>
                        <td class="py-3 pr-4">
                            <span class="px-2 py-1 rounded-full text-xs {{ $cat->is_approval_required ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $cat->is_approval_required ? 'Ya' : 'Tidak' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>
@endsection

