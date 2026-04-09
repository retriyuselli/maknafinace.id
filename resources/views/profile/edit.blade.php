@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')
    @include('front.header')

    <div class="min-h-screen bg-gray-50 py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Edit Profil</h1>
                <p class="mt-2 text-sm text-gray-600">Perbarui informasi profil dan pengaturan akun Anda.</p>
            </div>

            <!-- Alert Messages -->
            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 border border-green-200">
                    <div class="flex">
                        <div class="shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                    <div class="flex">
                        <div class="shrink-0">
                            <i class="fas fa-times-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Terdapat beberapa kesalahan:</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf
                @method('PATCH')

                <!-- Profile Section -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-6 space-y-6">
                        <h2 class="text-lg font-medium text-gray-900">Informasi Pribadi</h2>
                        
                        <!-- Avatar -->
                        <div class="flex items-center space-x-6">
                            <div class="shrink-0">
                                @if ($user->avatar_url)
                                    <img class="h-24 w-24 object-cover rounded-full border-4 border-white shadow-sm" 
                                         src="{{ Storage::url($user->avatar_url) }}" 
                                         alt="Current profile photo">
                                @else
                                    <div class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 text-3xl">
                                        <i class="fas fa-user"></i>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Foto Profil</label>
                                <div class="mt-1 flex items-center space-x-3">
                                    <input type="file" 
                                           name="avatar" 
                                           id="avatar"
                                           accept="image/*"
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                                <p class="mt-2 text-xs text-gray-500">JPG, GIF or PNG. Max 2MB.</p>
                            </div>
                        </div>

                        <!-- Basic Fields -->
                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <!-- Name -->
                            <div class="sm:col-span-3">
                                <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                           class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="sm:col-span-3">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                           class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="sm:col-span-3">
                                <label for="phone_number" class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                                <div class="mt-1">
                                    <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $user->phone_number) }}"
                                           class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                                </div>
                            </div>

                            <!-- Gender -->
                            <div class="sm:col-span-3">
                                <label for="gender" class="block text-sm font-medium text-gray-700">Jenis Kelamin</label>
                                <div class="mt-1">
                                    <select id="gender" name="gender" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                                        <option value="">Pilih...</option>
                                        <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Date of Birth -->
                            <div class="sm:col-span-3">
                                <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Tanggal Lahir</label>
                                <div class="mt-1">
                                    <input type="date" name="date_of_birth" id="date_of_birth" 
                                           value="{{ old('date_of_birth', $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '') }}"
                                           class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                                </div>
                            </div>

                            <!-- Hire Date (Readonly) -->
                            <div class="sm:col-span-3">
                                <label for="hire_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai Bekerja</label>
                                <div class="mt-1">
                                    <input type="date" name="hire_date" id="hire_date" 
                                           value="{{ old('hire_date', $user->hire_date ? $user->hire_date->format('Y-m-d') : '') }}"
                                           readonly
                                           class="shadow-sm bg-gray-100 block w-full sm:text-sm border-gray-300 rounded-md p-2 border cursor-not-allowed">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Hubungi HR untuk mengubah data ini.</p>
                            </div>

                            <!-- Address -->
                            <div class="sm:col-span-6">
                                <label for="address" class="block text-sm font-medium text-gray-700">Alamat</label>
                                <div class="mt-1">
                                    <textarea id="address" name="address" rows="3" 
                                              class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">{{ old('address', $user->address) }}</textarea>
                                </div>
                            </div>

                            <!-- Emergency Contact -->
                            <div class="sm:col-span-6">
                                <label for="emergency_contact" class="block text-sm font-medium text-gray-700">Kontak Darurat</label>
                                <div class="mt-1">
                                    <input type="text" name="emergency_contact" id="emergency_contact" 
                                           value="{{ old('emergency_contact', $user->emergency_contact) }}"
                                           placeholder="Nama - Hubungan - No. Telepon"
                                           class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                                </div>
                            </div>

                            <!-- Signature -->
                            <div class="sm:col-span-6 border-t pt-4 mt-2">
                                <label class="block text-sm font-medium text-gray-700">Tanda Tangan Digital</label>
                                <div class="mt-2 flex items-center space-x-6">
                                    @if ($user->signature_url)
                                        <div class="shrink-0 border rounded p-2 bg-white">
                                            <img class="h-16 object-contain" 
                                                 src="{{ Storage::url($user->signature_url) }}" 
                                                 alt="Current signature">
                                        </div>
                                    @endif
                                    <div class="grow">
                                        <input type="file" 
                                               name="signature" 
                                               id="signature"
                                               accept="image/png"
                                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                        <p class="mt-1 text-xs text-gray-500">Format PNG transparan. Max 1MB.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Section -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="p-6 space-y-6">
                        <h2 class="text-lg font-medium text-gray-900">Ubah Password</h2>
                        <p class="text-sm text-gray-500">Kosongkan jika tidak ingin mengubah password.</p>

                        <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                            <div class="sm:col-span-6">
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Password Saat Ini</label>
                                <div class="mt-1">
                                    <input type="password" name="current_password" id="current_password"
                                           class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                                <div class="mt-1">
                                    <input type="password" name="password" id="password"
                                           class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                                </div>
                            </div>

                            <div class="sm:col-span-3">
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                                <div class="mt-1">
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                           class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('profile') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Perbarui Profil
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('front.footer')
@endsection
