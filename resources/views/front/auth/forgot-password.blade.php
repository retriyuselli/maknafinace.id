@extends('layouts.app')

@section('title', 'Lupa Password - WOFINS')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-blue-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md mx-auto bg-white rounded-2xl shadow-xl p-8">

            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Lupa Password?</h1>
                <p class="mt-2 text-sm text-gray-500">
                    Masukkan email Anda dan kami akan mengirimkan link untuk reset password.
                </p>
            </div>

            @if (session('status'))
                <div class="mb-6 p-4 rounded-lg bg-green-50 border border-green-200">
                    <p class="text-sm text-green-700">{{ session('status') }}</p>
                </div>
            @endif

            <form class="space-y-6" action="{{ route('front.password.email') }}" method="POST">
                @csrf
                <div>
                    <label for="email" class="text-sm font-medium text-gray-900">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Masukkan email Anda"
                        value="{{ old('email') }}">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full px-4 py-2 rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                    Kirim Link Reset Password
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-600">
                Ingat password Anda?
                <a href="{{ route('front.login') }}" class="font-medium text-blue-600 hover:text-blue-500">
                    Kembali ke Login
                </a>
            </p>

        </div>
    </div>
@endsection
