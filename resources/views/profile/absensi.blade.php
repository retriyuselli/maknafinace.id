@extends('profile.layout')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        #absensi-map {
            min-height: 360px;
        }

        #camera-preview video,
        #camera-preview img {
            width: 100%;
            min-height: 240px;
            max-height: 320px;
            object-fit: cover;
            border-radius: 0.75rem;
            background: #0f172a;
        }

        #camera-preview canvas {
            display: none;
        }
    </style>
@endpush

@section('profile-page-title', 'Absensi')
@section('profile-page-subtitle', 'Lakukan absen masuk, absen pulang, dan pantau riwayat kehadiran Anda')

@section('profile-content')
    @php
        $statusLabels = [
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'alfa' => 'Alfa',
            'cuti' => 'Cuti',
            'libur' => 'Libur',
            'libur_mingguan' => 'Libur Mingguan',
            'setengah_hari' => 'Setengah Hari',
            'remote' => 'Remote',
        ];

        $statusClasses = [
            'hadir' => 'bg-emerald-100 text-emerald-700',
            'terlambat' => 'bg-amber-100 text-amber-700',
            'alfa' => 'bg-rose-100 text-rose-700',
            'cuti' => 'bg-sky-100 text-sky-700',
            'libur' => 'bg-slate-100 text-slate-700',
            'libur_mingguan' => 'bg-slate-100 text-slate-700',
            'setengah_hari' => 'bg-violet-100 text-violet-700',
            'remote' => 'bg-indigo-100 text-indigo-700',
        ];

        $statusKey = $absensiHariIni?->status ?? 'alfa';
        $canMasuk = $pengaturan && ! $absensiHariIni?->jam_masuk;
        $canPulang = $pengaturan && (bool) $absensiHariIni?->jam_masuk && ! $absensiHariIni?->jam_pulang;
        $wajibFoto = (bool) ($pengaturan?->wajib_foto ?? false);
        $wajibLokasi = (bool) ($pengaturan?->wajib_lokasi ?? false);
        $maxFotoKb = max(1, (int) ($pengaturan?->ukuran_foto_maks_kb ?: 5120));
        $todayLabel = \Carbon\Carbon::parse($today, $timezone)->locale('id')->translatedFormat('l, d F Y');
        $jamMasuk = $absensiHariIni?->jam_masuk?->timezone($timezone)?->format('H:i') ?? '-';
        $jamPulang = $absensiHariIni?->jam_pulang?->timezone($timezone)?->format('H:i') ?? '-';
        $totalJamKerja = $absensiHariIni && $absensiHariIni->menit_kerja
            ? floor($absensiHariIni->menit_kerja / 60).' jam '.($absensiHariIni->menit_kerja % 60).' menit'
            : '-';
        $lokasiMapData = $lokasiAktif->map(fn ($lokasi) => [
            'nama' => $lokasi->nama,
            'alamat' => $lokasi->alamat,
            'lintang' => (float) $lokasi->lintang,
            'bujur' => (float) $lokasi->bujur,
            'radius_meter' => (int) $lokasi->radius_meter,
        ])->values();
        $defaultTab = $errors->any() ? 'hari-ini' : 'hari-ini';
    @endphp

    <div
        x-data="{
            tab: '{{ $defaultTab }}',
            setTab(name) {
                this.tab = name;
                this.$nextTick(() => {
                    window.dispatchEvent(new CustomEvent('absensi-tab-changed', { detail: { tab: name } }));
                });
            }
        }"
        class="space-y-6"
    >
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <div class="font-semibold">Absensi belum berhasil diproses:</div>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl bg-white p-2 shadow-lg sm:p-3">
            <nav class="flex gap-1 overflow-x-auto" aria-label="Tab absensi">
                <button type="button" @click="setTab('hari-ini')"
                    :class="tab === 'hari-ini' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                    class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                    Hari Ini
                </button>
                <button type="button" @click="setTab('peta')"
                    :class="tab === 'peta' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                    class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                    Peta & Lokasi
                </button>
                <button type="button" @click="setTab('riwayat')"
                    :class="tab === 'riwayat' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                    class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                    Riwayat
                </button>
                <button type="button" @click="setTab('ringkasan')"
                    :class="tab === 'ringkasan' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                    class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                    Ringkasan
                </button>
                <button type="button" @click="setTab('koreksi')"
                    :class="tab === 'koreksi' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                    class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                    Koreksi
                </button>
                <button type="button" @click="setTab('lembur')"
                    :class="tab === 'lembur' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                    class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                    Lembur
                </button>
            </nav>
        </div>

        {{-- Tab: Hari Ini --}}
        <div x-show="tab === 'hari-ini'" x-cloak class="space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-lg">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <div class="text-sm font-medium text-blue-600">Absensi Hari Ini</div>
                        <h2 class="mt-1 text-2xl font-bold text-gray-900">{{ $todayLabel }}</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Zona waktu aktif: <span class="font-medium text-gray-800">{{ $timezone }}</span>
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ $statusClasses[$statusKey] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ $statusLabels[$statusKey] ?? ucfirst($statusKey) }}
                    </span>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Jam Masuk</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ $jamMasuk }}</div>
                        @if (($absensiHariIni?->menit_terlambat ?? 0) > 0)
                            <div class="mt-1 text-xs text-amber-600">
                                Terlambat {{ (int) $absensiHariIni->menit_terlambat }} menit
                            </div>
                        @endif
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Jam Pulang</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ $jamPulang }}</div>
                        @if (($absensiHariIni?->menit_pulang_cepat ?? 0) > 0)
                            <div class="mt-1 text-xs text-rose-600">
                                Pulang cepat {{ (int) $absensiHariIni->menit_pulang_cepat }} menit
                            </div>
                        @endif
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="text-xs uppercase tracking-wide text-gray-500">Durasi Kerja</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ $totalJamKerja }}</div>
                        <div class="mt-1 text-xs text-gray-500">
                            Sumber: {{ $absensiHariIni?->sumber ? strtoupper($absensiHariIni->sumber) : '-' }}
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ $canMasuk ? route('profile.absensi.masuk') : route('profile.absensi.pulang') }}"
                    enctype="multipart/form-data" class="mt-6 space-y-4" data-absensi-form
                    data-masuk-url="{{ route('profile.absensi.masuk') }}"
                    data-pulang-url="{{ route('profile.absensi.pulang') }}"
                    data-wajib-lokasi="{{ $wajibLokasi ? '1' : '0' }}"
                    data-foto-max-kb="{{ $maxFotoKb }}">
                    @csrf

                    <input type="hidden" name="lintang" id="absensi-lintang" value="{{ old('lintang') }}">
                    <input type="hidden" name="bujur" id="absensi-bujur" value="{{ old('bujur') }}">
                    <input type="hidden" name="akurasi_meter" id="absensi-akurasi" value="{{ old('akurasi_meter') }}">
                    <input type="hidden" name="nama_perangkat" id="absensi-perangkat" value="{{ old('nama_perangkat') }}">

                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-blue-900">Status lokasi perangkat</div>
                                <p id="absensi-location-status" class="mt-1 text-sm text-blue-700">
                                    Menunggu izin lokasi dari browser...
                                </p>
                                <p class="mt-1 text-xs text-blue-600">
                                    @if ($wajibLokasi)
                                        Lokasi wajib aktif untuk absensi sesuai pengaturan perusahaan.
                                    @else
                                        Lokasi akan ikut dikirim bila tersedia untuk memperkaya log absensi.
                                    @endif
                                </p>
                            </div>
                            <button type="button" id="absensi-refresh-location"
                                class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                                Ambil Lokasi
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-gray-200 p-4">
                            <label for="foto" class="text-sm font-semibold text-gray-900">Foto Absensi</label>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $wajibFoto ? 'Wajib diunggah sesuai pengaturan absensi.' : 'Opsional, namun disarankan untuk dokumentasi.' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                Format yang didukung: JPG, PNG, WebP. Maksimal {{ number_format($maxFotoKb / 1024, 1) }} MB.
                            </p>
                            <div id="camera-preview" class="mt-3 space-y-3">
                                <video id="camera-stream" playsinline autoplay muted class="hidden"></video>
                                <img id="camera-captured-preview" alt="Preview selfie absensi" class="hidden">
                                <canvas id="camera-canvas"></canvas>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" id="camera-start-button"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        Aktifkan Kamera
                                    </button>
                                    <button type="button" id="camera-capture-button"
                                        class="hidden inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">
                                        Ambil Selfie
                                    </button>
                                    <button type="button" id="camera-retake-button"
                                        class="hidden inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        Ambil Ulang
                                    </button>
                                </div>
                                <p id="camera-status" class="text-xs text-gray-500">
                                    Anda bisa menggunakan kamera depan browser atau unggah file manual.
                                </p>
                            </div>
                            <input id="foto" name="foto" type="file" accept="image/jpeg,image/jpg,image/png,image/webp"
                                capture="user"
                                class="mt-3 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700"
                                @if ($wajibFoto) required @endif>
                        </div>
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="text-sm font-semibold text-gray-900">Aturan Aktif</div>
                            @if ($pengaturan)
                                <ul class="mt-3 space-y-2 text-sm text-gray-600">
                                    <li>Jam masuk: <span class="font-medium text-gray-800">{{ $pengaturan->jam_masuk }}</span></li>
                                    <li>Jam pulang: <span class="font-medium text-gray-800">{{ $pengaturan->jam_pulang }}</span></li>
                                    <li>Wajib lokasi: <span class="font-medium text-gray-800">{{ $wajibLokasi ? 'Ya' : 'Tidak' }}</span></li>
                                    <li>Wajib foto: <span class="font-medium text-gray-800">{{ $wajibFoto ? 'Ya' : 'Tidak' }}</span></li>
                                </ul>
                            @else
                                <p class="mt-3 text-sm text-amber-700">
                                    Pengaturan absensi aktif belum tersedia. Hubungi administrator.
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="submit" data-absensi-action="masuk"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                            @if (! $canMasuk) disabled @endif>
                            Absen Masuk
                        </button>
                        <button type="submit" data-absensi-action="pulang"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                            @if (! $canPulang) disabled @endif>
                            Absen Pulang
                        </button>
                    </div>

                    @if (! $pengaturan)
                        <p class="text-sm text-amber-700">Tombol absensi dinonaktifkan sampai pengaturan aktif tersedia.</p>
                    @elseif (! $canMasuk && ! $canPulang)
                        <p class="text-sm text-gray-500">Absensi hari ini sudah lengkap tercatat.</p>
                    @elseif (! $canMasuk && $canPulang)
                        <p class="text-sm text-gray-500">Absen masuk sudah tercatat. Anda bisa melakukan absen pulang.</p>
                    @endif
                </form>
            </div>
        </div>

        {{-- Tab: Peta & Lokasi --}}
        <div x-show="tab === 'peta'" x-cloak class="space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-lg">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Peta Lokasi Absensi</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Titik biru menunjukkan posisi Anda, lingkaran hijau menunjukkan radius lokasi absensi aktif.
                        </p>
                        <div id="radius-state-badge"
                            class="mt-3 inline-flex rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                            Status radius belum tersedia
                        </div>
                    </div>
                    <span id="map-distance-summary"
                        class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                        Menunggu lokasi...
                    </span>
                </div>

                <div class="mt-4 rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p id="map-privacy-status" class="text-sm text-gray-600">
                            Peta belum dimuat. Anda tetap bisa absensi tanpa membuka peta.
                        </p>
                        <button type="button" id="absensi-load-map"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                            Muat Peta
                        </button>
                    </div>
                    <div id="absensi-map" class="mt-4 hidden overflow-hidden rounded-2xl border border-gray-200"></div>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-lg">
                <h3 class="text-lg font-bold text-gray-900">Lokasi Absensi Aktif</h3>
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                    @forelse ($lokasiAktif as $lokasi)
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="font-semibold text-gray-900">{{ $lokasi->nama }}</div>
                            <div class="mt-1 text-sm text-gray-600">{{ $lokasi->alamat ?: 'Alamat belum diisi.' }}</div>
                            <div class="mt-2 text-xs text-gray-500">
                                Radius {{ (int) $lokasi->radius_meter }} meter ·
                                {{ number_format((float) $lokasi->lintang, 6) }}, {{ number_format((float) $lokasi->bujur, 6) }}
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500 md:col-span-2">
                            Belum ada lokasi absensi aktif.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Tab: Riwayat --}}
        <div x-show="tab === 'riwayat'" x-cloak>
            <div class="rounded-2xl bg-white p-6 shadow-lg">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Riwayat Absensi Terbaru</h3>
                    <span class="text-sm text-gray-500">{{ $riwayatAbsensi->count() }} data</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Masuk</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Pulang</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Log</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($riwayatAbsensi as $riwayat)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $riwayat->tanggal?->locale('id')->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$riwayat->status] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ $statusLabels[$riwayat->status] ?? ucfirst($riwayat->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $riwayat->jam_masuk?->timezone($timezone)?->format('H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $riwayat->jam_pulang?->timezone($timezone)?->format('H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        @if ($riwayat->logAbsensis->isEmpty())
                                            -
                                        @else
                                            <div class="space-y-2">
                                                @foreach ($riwayat->logAbsensis as $log)
                                                    <div class="rounded-lg bg-gray-50 px-3 py-2">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div class="font-medium text-gray-800">
                                                                {{ ucfirst($log->jenis) }} · {{ $log->waktu?->timezone($timezone)?->format('H:i') }}
                                                            </div>
                                                            <div class="text-xs {{ $log->dalam_radius ? 'text-emerald-600' : 'text-amber-600' }}">
                                                                {{ $log->dalam_radius ? 'Dalam radius' : 'Di luar radius' }}
                                                            </div>
                                                        </div>
                                                        <div class="mt-1 text-xs text-gray-500">
                                                            {{ $log->lokasiAbsensi?->nama ?? 'Lokasi tidak terdeteksi' }}
                                                            @if (! is_null($log->jarak_ke_kantor_meter))
                                                                · {{ (int) $log->jarak_ke_kantor_meter }} m
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada riwayat absensi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab: Ringkasan --}}
        <div x-show="tab === 'ringkasan'" x-cloak>
            <div class="rounded-2xl bg-white p-6 shadow-lg">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Ringkasan {{ $ringkasan['periode'] }}</h3>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('profile.absensi.laporan.excel', ['bulan' => $laporanBulan, 'tahun' => $laporanTahun]) }}"
                            class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                            Unduh Excel
                        </a>
                        <a href="{{ route('profile.absensi.laporan.pdf', ['bulan' => $laporanBulan, 'tahun' => $laporanTahun]) }}"
                            class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-2 text-sm font-medium text-white hover:bg-rose-700">
                            Unduh PDF
                        </a>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-3">
                    <div class="rounded-xl bg-emerald-50 p-4">
                        <div class="text-xs uppercase tracking-wide text-emerald-700">Hadir</div>
                        <div class="mt-2 text-2xl font-bold text-emerald-800">{{ $ringkasan['hadir'] }}</div>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-4">
                        <div class="text-xs uppercase tracking-wide text-amber-700">Terlambat</div>
                        <div class="mt-2 text-2xl font-bold text-amber-800">{{ $ringkasan['terlambat'] }}</div>
                    </div>
                    <div class="rounded-xl bg-sky-50 p-4">
                        <div class="text-xs uppercase tracking-wide text-sky-700">Cuti</div>
                        <div class="mt-2 text-2xl font-bold text-sky-800">{{ $ringkasan['cuti'] }}</div>
                    </div>
                    <div class="rounded-xl bg-slate-100 p-4">
                        <div class="text-xs uppercase tracking-wide text-slate-700">Libur</div>
                        <div class="mt-2 text-2xl font-bold text-slate-800">{{ $ringkasan['libur'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl bg-rose-50 p-4">
                        <div class="text-xs uppercase tracking-wide text-rose-700">Alfa</div>
                        <div class="mt-2 text-2xl font-bold text-rose-800">{{ $ringkasan['alfa'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl bg-indigo-50 p-4">
                        <div class="text-xs uppercase tracking-wide text-indigo-700">Total Kerja</div>
                        <div class="mt-2 text-lg font-bold text-indigo-800">
                            {{ floor($ringkasan['total_menit_kerja'] / 60) }}j {{ $ringkasan['total_menit_kerja'] % 60 }}m
                        </div>
                    </div>
                    <div class="rounded-xl bg-violet-50 p-4">
                        <div class="text-xs uppercase tracking-wide text-violet-700">Lembur Disetujui</div>
                        <div class="mt-2 text-lg font-bold text-violet-800">
                            {{ floor(($ringkasan['total_menit_lembur'] ?? 0) / 60) }}j {{ ($ringkasan['total_menit_lembur'] ?? 0) % 60 }}m
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tab: Koreksi --}}
        <div x-show="tab === 'koreksi'" x-cloak class="space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-lg">
                <h3 class="text-lg font-bold text-gray-900">Ajukan Koreksi Absensi</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Gunakan bila jam masuk/pulang perlu diperbaiki. Admin akan meninjau pengajuan Anda.
                </p>

                <form method="POST" action="{{ route('profile.absensi.koreksi') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="absensi_id" class="text-sm font-semibold text-gray-900">Tanggal Absensi</label>
                        <select id="absensi_id" name="absensi_id" required
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Pilih tanggal</option>
                            @foreach ($riwayatAbsensi as $item)
                                <option value="{{ $item->id }}" @selected(old('absensi_id') == $item->id)>
                                    {{ $item->tanggal?->format('d/m/Y') }} · {{ strtoupper($item->status) }}
                                    (masuk {{ $item->jam_masuk?->timezone($timezone)?->format('H:i') ?? '-' }},
                                    pulang {{ $item->jam_pulang?->timezone($timezone)?->format('H:i') ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="jam_masuk_diajukan" class="text-sm font-semibold text-gray-900">Jam Masuk Diajukan</label>
                            <input id="jam_masuk_diajukan" type="datetime-local" name="jam_masuk_diajukan"
                                value="{{ old('jam_masuk_diajukan') }}"
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="jam_pulang_diajukan" class="text-sm font-semibold text-gray-900">Jam Pulang Diajukan</label>
                            <input id="jam_pulang_diajukan" type="datetime-local" name="jam_pulang_diajukan"
                                value="{{ old('jam_pulang_diajukan') }}"
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="alasan" class="text-sm font-semibold text-gray-900">Alasan</label>
                        <textarea id="alasan" name="alasan" rows="3" required
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            placeholder="Jelaskan alasan koreksi...">{{ old('alasan') }}</textarea>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                        Kirim Pengajuan
                    </button>
                </form>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-lg">
                <h3 class="text-lg font-bold text-gray-900">Riwayat Pengajuan Koreksi</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tanggal Absensi</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Diajukan</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($riwayatKoreksi as $koreksi)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $koreksi->absensi?->tanggal?->format('d M Y') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        Masuk {{ $koreksi->jam_masuk_diajukan?->timezone($timezone)?->format('d/m H:i') ?? '-' }}
                                        · Pulang {{ $koreksi->jam_pulang_diajukan?->timezone($timezone)?->format('d/m H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $koreksiStatusClass = match ($koreksi->status) {
                                                'disetujui' => 'bg-emerald-100 text-emerald-700',
                                                'ditolak' => 'bg-rose-100 text-rose-700',
                                                default => 'bg-amber-100 text-amber-700',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $koreksiStatusClass }}">
                                            {{ ucfirst($koreksi->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $koreksi->catatan_peninjau ?: $koreksi->alasan }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada pengajuan koreksi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Tab: Lembur --}}
        <div x-show="tab === 'lembur'" x-cloak class="space-y-6">
            <div class="rounded-2xl bg-white p-6 shadow-lg">
                <h3 class="text-lg font-bold text-gray-900">Ajukan Lembur</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Isi rentang waktu lembur. Durasi dihitung otomatis dan menunggu persetujuan admin.
                </p>

                <form method="POST" action="{{ route('profile.absensi.lembur') }}" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="lembur_tanggal" class="text-sm font-semibold text-gray-900">Tanggal</label>
                            <input id="lembur_tanggal" type="date" name="tanggal"
                                value="{{ old('tanggal', $today) }}"
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="lembur_absensi_id" class="text-sm font-semibold text-gray-900">Kaitkan Absensi (opsional)</label>
                            <select id="lembur_absensi_id" name="absensi_id"
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                <option value="">Tidak dikaitkan</option>
                                @foreach ($riwayatAbsensi as $item)
                                    <option value="{{ $item->id }}" @selected(old('absensi_id') == $item->id)>
                                        {{ $item->tanggal?->format('d/m/Y') }} · {{ strtoupper($item->status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="mulai_pada" class="text-sm font-semibold text-gray-900">Mulai</label>
                            <input id="mulai_pada" type="datetime-local" name="mulai_pada" required
                                value="{{ old('mulai_pada') }}"
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label for="selesai_pada" class="text-sm font-semibold text-gray-900">Selesai</label>
                            <input id="selesai_pada" type="datetime-local" name="selesai_pada" required
                                value="{{ old('selesai_pada') }}"
                                class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="lembur_alasan" class="text-sm font-semibold text-gray-900">Alasan</label>
                        <textarea id="lembur_alasan" name="alasan" rows="3" required
                            class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            placeholder="Contoh: Menyelesaikan revisi report klien...">{{ old('alasan') }}</textarea>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white hover:bg-violet-700">
                        Kirim Pengajuan Lembur
                    </button>
                </form>
            </div>

            <div class="rounded-2xl bg-white p-6 shadow-lg">
                <h3 class="text-lg font-bold text-gray-900">Riwayat Pengajuan Lembur</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Waktu</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Durasi</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($riwayatLembur as $lembur)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $lembur->tanggal?->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $lembur->mulai_pada?->timezone($timezone)?->format('H:i') }}
                                        –
                                        {{ $lembur->selesai_pada?->timezone($timezone)?->format('H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $lembur->labelDurasi() }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $lemburStatusClass = match ($lembur->status) {
                                                'disetujui' => 'bg-emerald-100 text-emerald-700',
                                                'ditolak' => 'bg-rose-100 text-rose-700',
                                                default => 'bg-amber-100 text-amber-700',
                                            };
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $lemburStatusClass }}">
                                            {{ ucfirst($lembur->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $lembur->catatan ?: $lembur->alasan }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada pengajuan lembur.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-absensi-form]');
            if (!form) {
                return;
            }

            const lintangInput = document.getElementById('absensi-lintang');
            const bujurInput = document.getElementById('absensi-bujur');
            const akurasiInput = document.getElementById('absensi-akurasi');
            const perangkatInput = document.getElementById('absensi-perangkat');
            const statusElement = document.getElementById('absensi-location-status');
            const refreshButton = document.getElementById('absensi-refresh-location');
            const wajibLokasi = form.dataset.wajibLokasi === '1';
            const fotoInput = document.getElementById('foto');
            const cameraStatus = document.getElementById('camera-status');
            const videoElement = document.getElementById('camera-stream');
            const canvasElement = document.getElementById('camera-canvas');
            const imagePreviewElement = document.getElementById('camera-captured-preview');
            const startCameraButton = document.getElementById('camera-start-button');
            const captureCameraButton = document.getElementById('camera-capture-button');
            const retakeCameraButton = document.getElementById('camera-retake-button');
            const lokasiAktif = @json($lokasiMapData);
            const mapSummaryElement = document.getElementById('map-distance-summary');
            const radiusStateBadge = document.getElementById('radius-state-badge');
            const mapContainer = document.getElementById('absensi-map');
            const mapLoadButton = document.getElementById('absensi-load-map');
            const mapPrivacyStatus = document.getElementById('map-privacy-status');
            const fotoMaxKb = Number(form.dataset.fotoMaxKb || 5120);
            const fotoMaxBytes = fotoMaxKb * 1024;
            const allowedImageTypes = new Set(['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);

            let mediaStream = null;
            let map = null;
            let currentMarker = null;
            let officeLayer = null;
            let locationAccuracyCircle = null;
            let currentPreviewUrl = null;
            let mapEnabled = false;

            perangkatInput.value = `${navigator.platform || 'unknown-platform'} | ${navigator.userAgent || 'unknown-browser'}`.slice(0, 100);

            const setStatus = (message, tone = 'info') => {
                statusElement.textContent = message;
                statusElement.className = 'mt-1 text-sm';

                if (tone === 'success') {
                    statusElement.classList.add('text-emerald-700');
                } else if (tone === 'error') {
                    statusElement.classList.add('text-rose-700');
                } else {
                    statusElement.classList.add('text-blue-700');
                }
            };

            const setCameraStatus = (message, tone = 'info') => {
                cameraStatus.textContent = message;
                cameraStatus.className = 'text-xs';

                if (tone === 'success') {
                    cameraStatus.classList.add('text-emerald-600');
                } else if (tone === 'error') {
                    cameraStatus.classList.add('text-rose-600');
                } else {
                    cameraStatus.classList.add('text-gray-500');
                }
            };

            const revokePreviewUrl = () => {
                if (!currentPreviewUrl) {
                    return;
                }

                URL.revokeObjectURL(currentPreviewUrl);
                currentPreviewUrl = null;
            };

            const validateImageFile = (file) => {
                if (!file) {
                    return false;
                }

                if (!allowedImageTypes.has(file.type)) {
                    setCameraStatus('Format foto tidak didukung. Gunakan JPG, PNG, atau WebP.', 'error');
                    return false;
                }

                if (file.size > fotoMaxBytes) {
                    setCameraStatus(`Ukuran foto melebihi batas ${fotoMaxKb} KB.`, 'error');
                    return false;
                }

                return true;
            };

            const stopCamera = () => {
                if (!mediaStream) {
                    return;
                }

                mediaStream.getTracks().forEach((track) => track.stop());
                mediaStream = null;
                videoElement.srcObject = null;
            };

            const startCamera = async () => {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    setCameraStatus('Browser ini belum mendukung akses kamera langsung.', 'error');
                    return;
                }

                try {
                    stopCamera();

                    mediaStream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: 'user',
                            width: { ideal: 1280 },
                            height: { ideal: 720 },
                        },
                        audio: false,
                    });

                    videoElement.srcObject = mediaStream;
                    videoElement.classList.remove('hidden');
                    imagePreviewElement.classList.add('hidden');
                    startCameraButton.classList.add('hidden');
                    captureCameraButton.classList.remove('hidden');
                    retakeCameraButton.classList.add('hidden');
                    setCameraStatus('Kamera aktif. Arahkan wajah ke frame lalu klik "Ambil Selfie".', 'success');
                } catch (error) {
                    setCameraStatus('Kamera gagal diaktifkan. Pastikan izin kamera diberikan.', 'error');
                }
            };

            const capturePhoto = () => {
                if (!mediaStream || !videoElement.videoWidth || !videoElement.videoHeight) {
                    setCameraStatus('Kamera belum siap untuk mengambil foto.', 'error');
                    return;
                }

                canvasElement.width = videoElement.videoWidth;
                canvasElement.height = videoElement.videoHeight;

                const context = canvasElement.getContext('2d');
                context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);

                canvasElement.toBlob((blob) => {
                    if (!blob) {
                        setCameraStatus('Gagal membuat file foto dari kamera.', 'error');
                        return;
                    }

                    const file = new File([blob], `absensi-${Date.now()}.jpg`, {
                        type: 'image/jpeg',
                    });

                    if (!validateImageFile(file)) {
                        return;
                    }

                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    fotoInput.files = transfer.files;

                    revokePreviewUrl();
                    currentPreviewUrl = URL.createObjectURL(blob);
                    imagePreviewElement.src = currentPreviewUrl;
                    imagePreviewElement.classList.remove('hidden');
                    videoElement.classList.add('hidden');
                    captureCameraButton.classList.add('hidden');
                    retakeCameraButton.classList.remove('hidden');
                    startCameraButton.classList.remove('hidden');
                    startCameraButton.textContent = 'Aktifkan Lagi Kamera';
                    stopCamera();
                    setCameraStatus('Selfie berhasil diambil dan siap dikirim bersama absensi.', 'success');
                }, 'image/jpeg', 0.92);
            };

            const resetCameraPreview = (message = 'Anda bisa mengambil ulang selfie atau unggah file manual.', tone = 'info') => {
                revokePreviewUrl();
                imagePreviewElement.classList.add('hidden');
                imagePreviewElement.removeAttribute('src');
                captureCameraButton.classList.add('hidden');
                retakeCameraButton.classList.add('hidden');
                startCameraButton.classList.remove('hidden');
                startCameraButton.textContent = 'Aktifkan Kamera';
                setCameraStatus(message, tone);
            };

            const updateRadiusBadge = (label, tone = 'neutral') => {
                radiusStateBadge.textContent = label;
                radiusStateBadge.className = 'mt-3 inline-flex rounded-xl px-4 py-2 text-sm font-semibold';

                if (tone === 'inside') {
                    radiusStateBadge.classList.add('bg-emerald-100', 'text-emerald-700');
                } else if (tone === 'outside') {
                    radiusStateBadge.classList.add('bg-amber-100', 'text-amber-700');
                } else if (tone === 'error') {
                    radiusStateBadge.classList.add('bg-rose-100', 'text-rose-700');
                } else {
                    radiusStateBadge.classList.add('bg-slate-100', 'text-slate-700');
                }
            };

            const createPopupContent = (lokasi) => {
                const container = document.createElement('div');
                container.style.minWidth = '200px';

                const title = document.createElement('strong');
                title.textContent = lokasi.nama ?? 'Lokasi absensi';

                const address = document.createElement('span');
                address.textContent = lokasi.alamat || 'Alamat belum diisi.';

                const radius = document.createElement('small');
                radius.textContent = `Radius ${lokasi.radius_meter} meter`;

                container.appendChild(title);
                container.appendChild(document.createElement('br'));
                container.appendChild(address);
                container.appendChild(document.createElement('br'));
                container.appendChild(radius);

                return container;
            };

            const hitungJarakMeter = (lat1, lon1, lat2, lon2) => {
                const earth = 6371000;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) ** 2
                    + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;

                return 2 * earth * Math.asin(Math.min(1, Math.sqrt(a)));
            };

            const updateDistanceSummary = (lat, lng) => {
                if (!lokasiAktif.length) {
                    mapSummaryElement.textContent = 'Belum ada lokasi aktif.';
                    mapSummaryElement.className = 'inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700';
                    updateRadiusBadge('Lokasi absensi aktif belum tersedia', 'error');
                    return;
                }

                let nearest = null;
                let nearestDistance = null;

                lokasiAktif.forEach((lokasi) => {
                    const distance = hitungJarakMeter(lat, lng, lokasi.lintang, lokasi.bujur);

                    if (nearestDistance === null || distance < nearestDistance) {
                        nearest = lokasi;
                        nearestDistance = distance;
                    }
                });

                if (!nearest) {
                    mapSummaryElement.textContent = 'Lokasi aktif belum tersedia.';
                    mapSummaryElement.className = 'inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700';
                    updateRadiusBadge('Lokasi absensi aktif belum tersedia', 'error');
                    return;
                }

                const isInside = nearestDistance <= nearest.radius_meter;
                mapSummaryElement.textContent = `${nearest.nama} · ${Math.round(nearestDistance)} m`;
                mapSummaryElement.className = `inline-flex rounded-full px-3 py-1 text-xs font-semibold ${
                    isInside ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'
                }`;
                updateRadiusBadge(
                    isInside
                        ? `Anda berada di dalam radius ${nearest.nama}`
                        : `Anda berada di luar radius ${nearest.nama}`,
                    isInside ? 'inside' : 'outside'
                );
            };

            const initMap = () => {
                if (!mapEnabled || typeof L === 'undefined' || map) {
                    return;
                }

                const defaultPoint = lokasiAktif[0] ?? {
                    lintang: -2.990934,
                    bujur: 104.756554,
                };

                mapContainer.classList.remove('hidden');
                map = L.map('absensi-map').setView([defaultPoint.lintang, defaultPoint.bujur], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                    crossOrigin: true,
                    referrerPolicy: 'no-referrer',
                }).addTo(map);

                officeLayer = L.layerGroup().addTo(map);
                const bounds = [];

                lokasiAktif.forEach((lokasi) => {
                    const latLng = [lokasi.lintang, lokasi.bujur];
                    bounds.push(latLng);

                    L.marker(latLng)
                        .bindPopup(createPopupContent(lokasi))
                        .addTo(officeLayer);

                    L.circle(latLng, {
                        radius: lokasi.radius_meter,
                        color: '#16a34a',
                        fillColor: '#22c55e',
                        fillOpacity: 0.12,
                        weight: 2,
                    }).addTo(officeLayer);
                });

                if (bounds.length > 1) {
                    map.fitBounds(bounds, { padding: [24, 24] });
                }

                setTimeout(() => map.invalidateSize(), 250);
            };

            const updateMapLocation = (lat, lng, accuracy = null) => {
                updateDistanceSummary(lat, lng);

                if (!mapEnabled) {
                    return;
                }

                initMap();
                if (!map) {
                    return;
                }

                const latLng = [lat, lng];

                if (!currentMarker) {
                    currentMarker = L.marker(latLng).addTo(map).bindPopup('Posisi Anda saat ini');
                } else {
                    currentMarker.setLatLng(latLng);
                }

                if (accuracy) {
                    if (!locationAccuracyCircle) {
                        locationAccuracyCircle = L.circle(latLng, {
                            radius: accuracy,
                            color: '#2563eb',
                            fillColor: '#3b82f6',
                            fillOpacity: 0.08,
                            weight: 1,
                        }).addTo(map);
                    } else {
                        locationAccuracyCircle.setLatLng(latLng);
                        locationAccuracyCircle.setRadius(accuracy);
                    }
                }
                map.setView(latLng, Math.max(map.getZoom(), 15));
            };

            const ambilLokasi = () => {
                if (!navigator.geolocation) {
                    setStatus('Browser ini tidak mendukung geolocation.', 'error');
                    return;
                }

                setStatus('Mengambil lokasi perangkat...', 'info');

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        lintangInput.value = position.coords.latitude;
                        bujurInput.value = position.coords.longitude;
                        akurasiInput.value = position.coords.accuracy || '';

                        const accuracyLabel = position.coords.accuracy
                            ? `akurasi ±${Math.round(position.coords.accuracy)} m`
                            : 'akurasi tidak tersedia';

                        setStatus(`Lokasi siap digunakan (${accuracyLabel}).`, 'success');
                        updateMapLocation(
                            position.coords.latitude,
                            position.coords.longitude,
                            position.coords.accuracy ? Math.round(position.coords.accuracy) : null
                        );
                    },
                    (error) => {
                        const message = error.code === error.PERMISSION_DENIED
                            ? 'Izin lokasi ditolak. Aktifkan izin lokasi lalu coba lagi.'
                            : 'Lokasi gagal diambil. Pastikan GPS aktif dan coba lagi.';

                        setStatus(message, 'error');
                        updateRadiusBadge('Posisi belum dapat diverifikasi', 'error');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0,
                    }
                );
            };

            fotoInput.addEventListener('change', () => {
                if (!fotoInput.files || !fotoInput.files.length) {
                    return;
                }

                const [file] = fotoInput.files;
                if (file && validateImageFile(file)) {
                    revokePreviewUrl();
                    currentPreviewUrl = URL.createObjectURL(file);
                    imagePreviewElement.src = currentPreviewUrl;
                    imagePreviewElement.classList.remove('hidden');
                    videoElement.classList.add('hidden');
                    retakeCameraButton.classList.remove('hidden');
                    startCameraButton.classList.remove('hidden');
                    captureCameraButton.classList.add('hidden');
                    setCameraStatus('File foto dipilih dan siap dikirim.', 'success');
                    stopCamera();
                } else {
                    fotoInput.value = '';
                    resetCameraPreview(
                        `Pilih foto JPG, PNG, atau WebP dengan ukuran maksimal ${fotoMaxKb} KB.`,
                        'error'
                    );
                }
            });

            startCameraButton.addEventListener('click', startCamera);
            captureCameraButton.addEventListener('click', capturePhoto);
            retakeCameraButton.addEventListener('click', async () => {
                fotoInput.value = '';
                resetCameraPreview();
                await startCamera();
            });
            refreshButton.addEventListener('click', ambilLokasi);
            mapLoadButton.addEventListener('click', () => {
                if (mapEnabled) {
                    return;
                }

                mapEnabled = true;
                mapLoadButton.disabled = true;
                mapLoadButton.classList.add('cursor-not-allowed', 'opacity-60');
                mapPrivacyStatus.textContent = 'Peta dimuat dari OpenStreetMap. Permintaan tile akan dikirim setelah komponen ini aktif.';
                initMap();

                if (lintangInput.value && bujurInput.value) {
                    updateMapLocation(
                        Number(lintangInput.value),
                        Number(bujurInput.value),
                        akurasiInput.value ? Number(akurasiInput.value) : null
                    );
                } else {
                    updateDistanceSummary(
                        lokasiAktif[0]?.lintang ?? -2.990934,
                        lokasiAktif[0]?.bujur ?? 104.756554
                    );
                }
            });

            form.querySelectorAll('[data-absensi-action]').forEach((button) => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.absensiAction === 'pulang'
                        ? form.dataset.pulangUrl
                        : form.dataset.masukUrl;
                });
            });

            form.addEventListener('submit', (event) => {
                if (wajibLokasi && (!lintangInput.value || !bujurInput.value)) {
                    event.preventDefault();
                    setStatus('Lokasi wajib diambil sebelum absensi diproses.', 'error');
                }
            });

            window.addEventListener('absensi-tab-changed', (event) => {
                if (event.detail?.tab === 'peta' && map) {
                    setTimeout(() => map.invalidateSize(), 150);
                }
            });

            window.addEventListener('beforeunload', () => {
                stopCamera();
                revokePreviewUrl();
            });

            updateDistanceSummary(
                lokasiAktif[0]?.lintang ?? -2.990934,
                lokasiAktif[0]?.bujur ?? 104.756554
            );
            ambilLokasi();
            setCameraStatus('Klik "Aktifkan Kamera" jika ingin mengambil selfie langsung dari browser.', 'info');
        });
    </script>
@endsection
