@php
    $canManageLeaveRequests = $profileUser?->hasRole(['super_admin', 'admin', 'finance']) ?? false;
    $isSuperAdmin = $profileUser?->hasRole('super_admin') ?? false;
@endphp

<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden lg:sticky lg:top-24">
    <div class="px-5 py-4 bg-indigo-700 bg-linear-to-r from-blue-600 to-indigo-700">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white/20 overflow-hidden flex items-center justify-center">
                @if($profileUser?->avatar_url)
                    <img class="w-10 h-10 object-cover"
                        src="{{ Storage::url($profileUser->avatar_url) }}"
                        alt="Profile {{ $profileUser->name }}"
                        onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($profileUser->name) }}&color=ffffff&background=1e40af&size=128&font-size=0.4'">
                @else
                    <img class="w-10 h-10 object-cover"
                        src="https://ui-avatars.com/api/?name={{ urlencode($profileUser->name) }}&color=ffffff&background=1e40af&size=128&font-size=0.4"
                        alt="Profile {{ $profileUser->name }}">
                @endif
            </div>
            <div class="min-w-0">
                <div class="text-sm font-semibold text-white truncate">{{ $profileUser->name }}</div>
                <div class="text-xs text-blue-100 truncate">{{ $profileUser->email }}</div>
            </div>
        </div>
    </div>

    <nav class="p-3 space-y-1">
        @php
            $overviewActive = request()->routeIs('profile') || request()->routeIs('profile.show') || request()->routeIs('profile.overview');
            $compensationActive = request()->routeIs('profile.compensation');
            $scheduleActive = request()->routeIs('profile.schedule');
        @endphp

        <a href="{{ route('profile') }}"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $overviewActive ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18M3 14h18M3 18h18" />
            </svg>
            <span>Ringkasan</span>
        </a>

        <a href="{{ route('profile.compensation') }}"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $compensationActive ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l-2 1m12 12V6l-2 1M5 6h14" />
            </svg>
            <span>Kompensasi & Cuti</span>
        </a>

        <a href="{{ route('profile.schedule') }}"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $scheduleActive ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>Jadwal & Riwayat</span>
        </a>

        <div class="my-3 border-t border-gray-200"></div>

        <a href="{{ route('profile.edit') }}"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition text-gray-700 hover:bg-gray-50">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <span>Edit Profil</span>
        </a>

        <a href="{{ route('dashboard') }}"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition text-gray-700 hover:bg-gray-50">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
            </svg>
            <span>Dashboard</span>
        </a>

        @if($canManageLeaveRequests)
            <a href="/admin/leave-requests"
                class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m2 8H7a2 2 0 01-2-2V6a2 2 0 012-2h6l4 4v12a2 2 0 01-2 2z" />
                </svg>
                <span>Kelola Cuti</span>
            </a>
        @endif
    </nav>

    @if($isSuperAdmin)
        <div class="px-3 pb-3">
            <div class="my-3 border-t border-gray-200"></div>
            <div class="px-3 py-2 text-xs font-semibold text-gray-500 tracking-wide uppercase">Admin Tools</div>

            @php
                $isAdminTools = request()->routeIs('profile.admin-tools');
                $isAdminUsers = request()->routeIs('profile.admin-tools.users');
                $isAdminRoles = request()->routeIs('profile.admin-tools.roles');
                $isAdminCompany = request()->routeIs('profile.admin-tools.company');
                $isAdminBranding = request()->routeIs('profile.admin-tools.branding');
                $isAdminSops = request()->routeIs('profile.admin-tools.sops');
                $isAdminDocumentations = request()->routeIs('profile.admin-tools.documentations');
                $isAdminDocumentCategories = request()->routeIs('profile.admin-tools.document-categories');
                $isAdminHelpCenter = request()->routeIs('profile.admin-tools.help-center');
                $isAdminProjects = request()->routeIs('profile.admin-tools.projects*');
                $isAdminBankStatements = request()->routeIs('profile.admin-tools.bank-statements*');
            @endphp

            <div class="space-y-1">
                <a href="{{ route('profile.admin-tools') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminTools ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <span>Ringkasan Admin</span>
                </a>

                <a href="{{ route('profile.admin-tools.users') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminUsers ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1m-4 6H2v-2a4 4 0 014-4h1m6-4a4 4 0 11-8 0 4 4 0 018 0zm8 4a4 4 0 10-4-4 4 4 0 004 4z" />
                    </svg>
                    <span>Manajemen Pengguna</span>
                </a>

                <a href="{{ route('profile.admin-tools.roles') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminRoles ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5s-3 1.343-3 3 1.343 3 3 3zm0 0v3m0 3h.01M6.938 6.938l1.414 1.414M15.648 15.648l1.414 1.414M5 12h2M17 12h2M6.938 17.062l1.414-1.414M15.648 8.352l1.414-1.414" />
                    </svg>
                    <span>Role & Permission</span>
                </a>

                <a href="{{ route('profile.admin-tools.company') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminCompany ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M4 21V7a2 2 0 012-2h3V3h6v2h3a2 2 0 012 2v14M9 21v-6h6v6" />
                    </svg>
                    <span>Pengaturan Perusahaan</span>
                </a>

                <a href="{{ route('profile.admin-tools.branding') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminBranding ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4-4a3 3 0 014 0l1 1a3 3 0 004 0l3-3M4 6h16v12H4V6z" />
                    </svg>
                    <span>Logo & Branding</span>
                </a>

                <a href="{{ route('profile.admin-tools.sops') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminSops ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m2 8H7a2 2 0 01-2-2V6a2 2 0 012-2h6l4 4v12a2 2 0 01-2 2z" />
                    </svg>
                    <span>SOP</span>
                </a>

                <a href="{{ route('profile.admin-tools.projects') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminProjects ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Proyek Wedding</span>
                </a>

                <a href="{{ route('profile.admin-tools.bank-statements') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminBankStatements ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H7a2 2 0 00-2 2v2m12 0H5m12 0a2 2 0 012 2v8a2 2 0 01-2 2H7a2 2 0 01-2-2v-8a2 2 0 012-2" />
                    </svg>
                    <span>Bank Statement</span>
                </a>

                <a href="{{ route('profile.admin-tools.documentations') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminDocumentations ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 11h10M7 15h6M5 3h10l4 4v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
                    </svg>
                    <span>Dokumentasi</span>
                </a>

                <a href="{{ route('profile.admin-tools.document-categories') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminDocumentCategories ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10" />
                    </svg>
                    <span>Kategori Dokumen</span>
                </a>

                <a href="{{ route('profile.admin-tools.help-center') }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ $isAdminHelpCenter ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10a4 4 0 118 0c0 2-2 3-2 3m-2 4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
                    </svg>
                    <span>Pusat Bantuan</span>
                </a>
            </div>
        </div>
    @endif
</div>
