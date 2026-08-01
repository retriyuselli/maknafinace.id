<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terima Kasih - Data Pribadi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans', system-ui, -apple-system, Segoe UI, Roboto, Oxygen, Ubuntu, Cantarell, Arial, sans-serif;
            background: linear-gradient(135deg, #f7fafc 0%, #eef2ff 100%);
            min-height: 100vh;
        }
        .hero-card {
            border-radius: 1rem;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .hero-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.2);
        }
        .btn-primary:hover { filter: brightness(1.05); }
    </style>
    <meta http-equiv="Cache-Control" content="no-store" />
</head>
<body>
    <div class="container py-5">
        <div class="hero-card shadow-lg">
            <div class="hero-header p-4 p-md-5 text-center">
                <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                    <i class="bi bi-check2-circle fs-1"></i>
                    <h1 class="h3 h-md-2 m-0">Terima Kasih!</h1>
                </div>
                <p class="mb-0 opacity-90">Data pribadi Anda telah berhasil disimpan.</p>
            </div>
            <div class="p-4 p-md-5 bg-white">
                <div class="row g-4 align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-start gap-3">
                            <div class="text-success">
                                <i class="bi bi-heart-fill fs-3"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-2">Kami menghargai waktu dan kepercayaan Anda</h2>
                                <p class="text-muted mb-0">Selamat bergabung di {{ $companyName ?? config('app.name') }}, kami mengharapkan dukungan dan partisipasi Anda dalam pengembangan bisnis kami.</p>
                            </div>
                        </div>
                    </div>
                    @php
                        $isSuperAdmin = auth()->check() && auth()->user()->hasRole('super_admin');
                    @endphp
                    @if ($isSuperAdmin)
                        <div class="col-md-4 text-md-end">
                            <a href="{{ route('data-pribadi.index') }}" class="btn btn-light me-2">
                                <i class="bi bi-card-checklist me-1"></i> Lihat Daftar Data
                            </a>
                            <a href="{{ route('data-pribadi.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Lagi
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="text-center text-muted mt-4">
            &copy; {{ date('Y') }} {{ $companyName ?? config('app.name') }}
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
