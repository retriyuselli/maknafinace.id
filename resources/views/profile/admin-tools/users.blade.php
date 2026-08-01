@extends('profile.layout')

@section('profile-page-title', 'Manajemen Pengguna')
@section('profile-page-subtitle', 'Daftar pengguna sistem (khusus super admin)')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
    <form method="GET" class="flex items-center gap-3 mb-4">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / email"
            class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Cari</button>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                    <th class="py-3 pr-4">Nama</th>
                    <th class="py-3 pr-4">Email</th>
                    <th class="py-3 pr-4">Role</th>
                    <th class="py-3 pr-4">Updated</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($users as $u)
                    <tr class="text-gray-800">
                        <td class="py-3 pr-4 font-medium">{{ $u->name }}</td>
                        <td class="py-3 pr-4">{{ $u->email }}</td>
                        <td class="py-3 pr-4">
                            @php
                                $roleNames = $u->getRoleNames();
                            @endphp
                            @if($roleNames->isEmpty())
                                <span class="text-xs text-gray-500">-</span>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @foreach($roleNames as $rn)
                                        <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-xs">{{ $rn }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-xs text-gray-600">{{ optional($u->updated_at)->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection

