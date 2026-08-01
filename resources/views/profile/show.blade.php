@extends('profile.layout')

@section('profile-page-title', 'Dashboard Profil')
@section('profile-page-subtitle', 'Kelola informasi akun dan data HR Anda')

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    @include('profile.sections.header', ['user' => $user])
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @include('profile.sections.personal-info', ['user' => $user])
            @include('profile.sections.employment-info', ['user' => $user])
        </div>
    </div>
</div>
@endsection
