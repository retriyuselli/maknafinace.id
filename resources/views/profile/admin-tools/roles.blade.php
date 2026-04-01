@extends('profile.layout')

@section('profile-page-title', 'Role & Permission')
@section('profile-page-subtitle', 'Daftar role beserta jumlah permission (khusus super admin)')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                    <th class="py-3 pr-4">Role</th>
                    <th class="py-3 pr-4">Permissions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($roles as $role)
                    <tr class="text-gray-800">
                        <td class="py-3 pr-4 font-medium">{{ $role->name }}</td>
                        <td class="py-3 pr-4">
                            <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-xs">
                                {{ number_format((int) $role->permissions_count) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

