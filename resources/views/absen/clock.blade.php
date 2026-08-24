@extends('absen.layout')

@section('title', $aksi === 'masuk' ? 'Clock In' : 'Clock Out')

@section('styles')
<style>
    .clock-panel {
        background: #fff;
        border-radius: 18px;
        padding: 16px;
    }
    .clock-panel video,
    .clock-panel img {
        width: 100%;
        min-height: 220px;
        max-height: 280px;
        object-fit: cover;
        border-radius: 14px;
        background: #0f172a;
    }
    .clock-panel canvas { display: none; }
    .clock-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin: 12px 0;
    }
    .clock-meta div {
        background: #f8fafc;
        border-radius: 12px;
        padding: 10px;
        font-size: 12px;
    }
    .clock-meta strong { display: block; font-size: 18px; color: #111827; margin-top: 4px; }
    .clock-btn {
        width: 100%;
        border: 0;
        border-radius: 14px;
        padding: 14px;
        font-family: 'Poppins', system-ui, sans-serif;
        font-weight: 700;
        color: #fff;
        margin-top: 12px;
    }
    .clock-btn.is-in { background: #059669; }
    .clock-btn.is-out { background: #2563eb; }
    .clock-btn:disabled { background: #d1d5db; color: #6b7280; }
    .clock-ghost,
    .clock-panel input,
    .clock-panel button {
        font-family: 'Poppins', system-ui, sans-serif;
    }
    .clock-ghost {
        width: 100%;
        margin-top: 8px;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 12px;
        padding: 10px;
        font-size: 13px;
        color: #4b5563;
    }
</style>
@endsection

@section('content')
    <header class="absen-top">
        <a href="{{ route('absen.home') }}" class="absen-brand">
            <img src="{{ route('brand.logo') }}" alt="Makna">
            <div>
                <div class="absen-brand-name">{{ $aksi === 'masuk' ? 'CLOCK IN' : 'CLOCK OUT' }}</div>
                <div class="absen-brand-sub">ABSEN {{ $aksi === 'masuk' ? 'MASUK' : 'PULANG' }}</div>
            </div>
        </a>
        <a href="{{ route('absen.home') }}" class="absen-avatar" aria-label="Kembali">←</a>
    </header>

    @if (session('success'))
        <div class="clock-panel" style="margin-bottom:12px;color:#047857;">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="clock-panel" style="margin-bottom:12px;color:#be123c;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="clock-meta">
        <div>Masuk<strong>{{ $jamMasuk }}</strong></div>
        <div>Pulang<strong>{{ $jamPulang }}</strong></div>
    </div>

    <form method="POST"
        action="{{ $aksi === 'masuk' ? route('profile.absensi.masuk') : route('profile.absensi.pulang') }}"
        enctype="multipart/form-data"
        class="clock-panel"
        data-absensi-form
        data-wajib-lokasi="{{ $wajibLokasi ? '1' : '0' }}"
        data-wajib-foto="{{ $wajibFoto ? '1' : '0' }}"
        data-foto-max-kb="{{ $maxFotoKb }}">
        @csrf
        <input type="hidden" name="redirect_to" value="absen.home">
        <input type="hidden" name="lintang" id="absensi-lintang" value="{{ old('lintang') }}">
        <input type="hidden" name="bujur" id="absensi-bujur" value="{{ old('bujur') }}">
        <input type="hidden" name="akurasi_meter" id="absensi-akurasi" value="{{ old('akurasi_meter') }}">
        <input type="hidden" name="nama_perangkat" id="absensi-perangkat" value="{{ old('nama_perangkat') }}">

        <p id="absensi-location-status" class="text-sm" style="margin:0 0 10px;font-size:13px;">Menunggu izin lokasi...</p>
        <button type="button" id="absensi-refresh-location" class="clock-ghost">Ambil Lokasi</button>

        <div style="margin-top:14px;">
            <video id="camera-stream" playsinline autoplay muted class="hidden"></video>
            <img id="camera-captured-preview" alt="Preview selfie" class="hidden">
            <canvas id="camera-canvas"></canvas>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                <button type="button" id="camera-start-button" class="clock-ghost">Aktifkan Kamera</button>
                <button type="button" id="camera-capture-button" class="clock-ghost hidden">Ambil Selfie</button>
                <button type="button" id="camera-retake-button" class="clock-ghost hidden">Ambil Ulang</button>
            </div>
            <p id="camera-status" style="font-size:12px;color:#6b7280;margin:8px 0 0;">Aktifkan kamera, lalu ambil selfie.</p>
            <input id="foto" name="foto" type="file" accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden">
        </div>

        <button type="submit" class="clock-btn {{ $aksi === 'masuk' ? 'is-in' : 'is-out' }}">
            {{ $aksi === 'masuk' ? 'Kirim Absen Masuk' : 'Kirim Absen Pulang' }}
        </button>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-absensi-form]');
    if (!form) return;

    const lintangInput = document.getElementById('absensi-lintang');
    const bujurInput = document.getElementById('absensi-bujur');
    const akurasiInput = document.getElementById('absensi-akurasi');
    const perangkatInput = document.getElementById('absensi-perangkat');
    const statusElement = document.getElementById('absensi-location-status');
    const refreshButton = document.getElementById('absensi-refresh-location');
    const wajibLokasi = form.dataset.wajibLokasi === '1';
    const wajibFoto = form.dataset.wajibFoto === '1';
    const fotoInput = document.getElementById('foto');
    const cameraStatus = document.getElementById('camera-status');
    const videoElement = document.getElementById('camera-stream');
    const canvasElement = document.getElementById('camera-canvas');
    const imagePreviewElement = document.getElementById('camera-captured-preview');
    const startCameraButton = document.getElementById('camera-start-button');
    const captureCameraButton = document.getElementById('camera-capture-button');
    const retakeCameraButton = document.getElementById('camera-retake-button');
    const fotoMaxKb = Number(form.dataset.fotoMaxKb || 5120);
    const fotoMaxBytes = fotoMaxKb * 1024;
    const allowedImageTypes = new Set(['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);
    let mediaStream = null;
    let currentPreviewUrl = null;

    perangkatInput.value = `${navigator.platform || 'unknown'} | ${navigator.userAgent || 'browser'}`.slice(0, 100);

    const setStatus = (message, tone = 'info') => {
        statusElement.textContent = message;
        statusElement.style.color = tone === 'success' ? '#047857' : (tone === 'error' ? '#be123c' : '#2563eb');
    };
    const setCameraStatus = (message, tone = 'info') => {
        cameraStatus.textContent = message;
        cameraStatus.style.color = tone === 'success' ? '#047857' : (tone === 'error' ? '#be123c' : '#6b7280');
    };
    const revokePreviewUrl = () => {
        if (currentPreviewUrl) {
            URL.revokeObjectURL(currentPreviewUrl);
            currentPreviewUrl = null;
        }
    };
    const validateImageFile = (file) => {
        if (!file) return false;
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
        if (!mediaStream) return;
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
        videoElement.srcObject = null;
    };
    const startCamera = async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            setCameraStatus('Browser ini belum mendukung kamera.', 'error');
            return;
        }
        try {
            stopCamera();
            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false,
            });
            videoElement.srcObject = mediaStream;
            videoElement.classList.remove('hidden');
            imagePreviewElement.classList.add('hidden');
            startCameraButton.classList.add('hidden');
            captureCameraButton.classList.remove('hidden');
            retakeCameraButton.classList.add('hidden');
            setCameraStatus('Kamera aktif. Ambil selfie lalu kirim.', 'success');
        } catch (error) {
            setCameraStatus('Kamera gagal diaktifkan. Pastikan izin kamera diberikan.', 'error');
        }
    };
    const capturePhoto = () => {
        if (!mediaStream || !videoElement.videoWidth) {
            setCameraStatus('Kamera belum siap.', 'error');
            return;
        }
        canvasElement.width = videoElement.videoWidth;
        canvasElement.height = videoElement.videoHeight;
        canvasElement.getContext('2d').drawImage(videoElement, 0, 0);
        canvasElement.toBlob((blob) => {
            if (!blob) return;
            const file = new File([blob], `absensi-${Date.now()}.jpg`, { type: 'image/jpeg' });
            if (!validateImageFile(file)) return;
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
            setCameraStatus('Selfie siap dikirim.', 'success');
            stopCamera();
        }, 'image/jpeg', 0.9);
    };
    const ambilLokasi = () => {
        if (!navigator.geolocation) {
            setStatus('Browser tidak mendukung GPS.', 'error');
            return;
        }
        setStatus('Mengambil lokasi...', 'info');
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                lintangInput.value = pos.coords.latitude.toFixed(7);
                bujurInput.value = pos.coords.longitude.toFixed(7);
                akurasiInput.value = pos.coords.accuracy ? Math.round(pos.coords.accuracy) : '';
                setStatus(`Lokasi didapat (±${Math.round(pos.coords.accuracy || 0)} m).`, 'success');
            },
            () => setStatus('Lokasi gagal diambil. Aktifkan GPS lalu coba lagi.', 'error'),
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    };

    startCameraButton.addEventListener('click', startCamera);
    captureCameraButton.addEventListener('click', capturePhoto);
    retakeCameraButton.addEventListener('click', async () => {
        fotoInput.value = '';
        revokePreviewUrl();
        imagePreviewElement.classList.add('hidden');
        await startCamera();
    });
    refreshButton.addEventListener('click', ambilLokasi);
    form.addEventListener('submit', (event) => {
        if (wajibLokasi && (!lintangInput.value || !bujurInput.value)) {
            event.preventDefault();
            setStatus('Lokasi wajib diambil sebelum absensi diproses.', 'error');
            return;
        }
        if (wajibFoto && (!fotoInput.files || !fotoInput.files.length)) {
            event.preventDefault();
            setCameraStatus('Ambil selfie terlebih dahulu sebelum mengirim absensi.', 'error');
        }
    });
    window.addEventListener('beforeunload', () => {
        stopCamera();
        revokePreviewUrl();
    });
    ambilLokasi();
});
</script>
@endpush
