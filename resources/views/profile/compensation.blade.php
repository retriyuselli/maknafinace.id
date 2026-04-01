@extends('profile.layout')

@section('profile-page-title', 'Kompensasi & Cuti')
@section('profile-page-subtitle', 'Ringkasan gaji, saldo cuti, dan statistik cuti')

@section('profile-content')
@include('profile.sections.hr-salary-leave')
@endsection

