@extends('profile.layout')

@section('profile-page-title', 'Jadwal & Riwayat')
@section('profile-page-subtitle', 'Acara mendatang dan riwayat cuti Anda')

@section('profile-content')
@include('profile.sections.upcoming-events')
@endsection

