<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 5px 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .summary { margin-bottom: 14px; }
        .summary td { border: none; padding: 2px 8px 2px 0; }
    </style>
</head>
<body>
    <h1>Laporan Absensi</h1>
    <div class="meta">
        Dibuat: {{ $generatedAt->format('d/m/Y H:i') }}
        @if (! empty($filters['bulan']) && ! empty($filters['tahun']))
            · Periode: {{ \Carbon\Carbon::create((int) $filters['tahun'], (int) $filters['bulan'], 1)->locale('id')->translatedFormat('F Y') }}
        @endif
        @if (! empty($filters['dari']) || ! empty($filters['sampai']))
            · Rentang: {{ $filters['dari'] ?? '...' }} s/d {{ $filters['sampai'] ?? '...' }}
        @endif
    </div>

    @if ($ringkasan)
        <table class="summary">
            <tr>
                <td><strong>Karyawan:</strong> {{ $ringkasan['user_name'] }}</td>
                <td><strong>Hadir:</strong> {{ $ringkasan['hadir'] }}</td>
                <td><strong>Terlambat:</strong> {{ $ringkasan['terlambat'] }}</td>
                <td><strong>Alfa:</strong> {{ $ringkasan['alfa'] }}</td>
            </tr>
            <tr>
                <td><strong>Cuti:</strong> {{ $ringkasan['cuti'] }}</td>
                <td><strong>Libur:</strong> {{ $ringkasan['libur'] }}</td>
                <td><strong>Menit terlambat:</strong> {{ $ringkasan['total_menit_terlambat'] }}</td>
                <td><strong>Lembur:</strong> {{ $ringkasan['total_menit_lembur'] }} mnt</td>
            </tr>
        </table>
    @endif

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Karyawan</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Masuk</th>
                <th>Pulang</th>
                <th>Kerja</th>
                <th>Terlambat</th>
                <th>Sumber</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row->user?->name }}</td>
                    <td>{{ optional($row->tanggal)->format('d/m/Y') }}</td>
                    <td>{{ $labelStatus($row->status) }}</td>
                    <td>{{ optional($row->jam_masuk)->format('H:i') ?? '-' }}</td>
                    <td>{{ optional($row->jam_pulang)->format('H:i') ?? '-' }}</td>
                    <td>{{ $row->menit_kerja ?? '-' }}</td>
                    <td>{{ $row->menit_terlambat }}</td>
                    <td>{{ $row->sumber ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;">Tidak ada data absensi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
