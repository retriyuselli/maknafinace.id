<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Account Manager - {{ $accountManager->name ?? 'Unknown' }} - {{ $monthName ?? '-' }} {{ $year ?? '-' }}</title>
    <style>
        @page { size: A4 portrait; margin: 32mm 12mm 14mm 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #111827; margin: 0; }
        .muted { color: #6b7280; }
        .title { font-size: 18px; font-weight: 700; margin: 0; }
        .subtitle { margin: 2px 0 0 0; }
        .section { margin-top: 14px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid th, .grid td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
        .grid th { background: #f3f4f6; font-weight: 700; }
        .right { text-align: right; }
        .center { text-align: center; }
        .pill { display: inline-block; padding: 2px 6px; border-radius: 999px; background: #eef2ff; color: #3730a3; font-size: 11px; }
        .kpi { width: 100%; border-collapse: collapse; }
        .kpi td { border: 1px solid #e5e7eb; padding: 8px; }
        .kpi .label { color: #6b7280; font-size: 11px; }
        .kpi .value { font-weight: 700; font-size: 14px; margin-top: 2px; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
        .progress { width: 100%; height: 10px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
        .progress > div { height: 10px; background: #4f46e5; }
        .h { font-weight: 700; margin: 0 0 6px 0; }
        .note { border-top: 1px solid #e5e7eb; margin-top: 14px; padding-top: 10px; font-size: 11px; color: #374151; }
        .sig td { border: none; }
        header { position: fixed; top: -22mm; left: 0; right: 0; height: 22mm; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-left { width: 45%; vertical-align: middle; }
        .header-right { width: 55%; text-align: right; vertical-align: middle; }
        .header-logo { max-height: 8mm; width: auto; }
        .header-title { font-size: 16px; font-weight: 700; margin: 0; }
        .header-subtitle { font-size: 11px; margin: 2px 0 0 0; color: #6b7280; }
    </style>
</head>
<body>
    @php
        $targetAmount = (int) ($target?->target_amount ?? 0);
        $revenue = (int) ($totalRevenue ?? 0);
        $gap = $targetAmount - $revenue;
        $achievement = (float) ($achievementPercentage ?? 0);
        $barWidth = min(max($achievement, 0), 100);

        $companyName = $companyName ?? config('app.name');
        
        $companyAddress = $companyAddress ?? 'Jl. Sintraman Jaya I No. 2148, 20 Ilir D II, Kec. Kemuning, Kota Palembang, Sumatera Selatan';
        $companyEmail = $companyEmail ?? 'info@maknawedding.id';
        $companyPhone = $companyPhone ?? '+62 813 7318 3794';
    @endphp

    <header>
        <table class="header-table">
            <tr>
                <td class="header-left">
                    @if (! empty($logoBase64))
                        <img class="header-logo" src="{{ $logoBase64 }}" alt="{{ $companyName }}">
                    @endif
                </td>
                <td class="header-right">
                    <p class="header-title">Laporan Kinerja Account Manager</p>
                    <p class="header-subtitle">
                        {{ $accountManager->name ?? '-' }} • {{ $monthName ?? '-' }} {{ $year ?? '-' }}
                    </p>
                </td>
            </tr>
        </table>
    </header>

    <div class="section">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:55%; vertical-align: top; padding-right: 10px;">
                    <div class="box">
                        <div class="h">Account Manager</div>
                        <div><b>{{ $accountManager->name ?? '-' }}</b></div>
                        <div class="muted">Email: {{ $accountManager->email ?? '-' }}</div>
                        <div class="muted">Target: Rp {{ number_format($targetAmount, 0, ',', '.') }}</div>
                        <div class="muted">Status: {{ ucfirst((string) ($target?->status ?? 'pending')) }}</div>
                    </div>
                </td>
                <td style="width:45%; vertical-align: top; padding-left: 10px;">
                    <div class="box">
                        <div class="h">{{ $companyName }}</div>
                        <div class="muted">{{ $companyAddress }}</div>
                        <div class="muted">Email: {{ $companyEmail }}</div>
                        <div class="muted">Tlp: {{ $companyPhone }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="kpi">
            <tr>
                <td>
                    <div class="label">Total Revenue</div>
                    <div class="value">Rp {{ number_format($revenue, 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="label">Jumlah Closing</div>
                    <div class="value">{{ number_format((int) ($totalOrders ?? 0), 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="label">Rata-rata Order</div>
                    <div class="value">Rp {{ number_format((int) ($averageOrderValue ?? 0), 0, ',', '.') }}</div>
                </td>
                <td>
                    <div class="label">Pencapaian</div>
                    <div class="value">{{ number_format($achievement, 1, ',', '.') }}%</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="h">Target vs Achievement</div>
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:50%; padding-right: 8px;">
                    <div class="muted">Target</div>
                    <div style="font-weight:700;">Rp {{ number_format($targetAmount, 0, ',', '.') }}</div>
                </td>
                <td style="width:50%; padding-left: 8px;">
                    <div class="muted">Achievement</div>
                    <div style="font-weight:700;">Rp {{ number_format($revenue, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>
        <div style="margin-top: 8px;">
            <div class="progress"><div style="width: {{ $barWidth }}%;"></div></div>
        </div>
    </div>

    <div class="section">
        <div class="h">Ringkasan Kinerja</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 22%;">Metrik</th>
                    <th style="width: 26%;" class="right">Target</th>
                    <th style="width: 26%;" class="right">Realisasi</th>
                    <th style="width: 12%;" class="right">%</th>
                    <th style="width: 14%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><b>Revenue</b></td>
                    <td class="right">Rp {{ number_format($targetAmount, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($revenue, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($achievement, 1, ',', '.') }}%</td>
                    <td>
                        @if ($achievement >= 100)
                            Tercapai
                        @elseif($achievement >= 75)
                            Hampir Tercapai
                        @else
                            Belum Tercapai
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><b>Jumlah Order</b></td>
                    <td class="right">-</td>
                    <td class="right">{{ number_format((int) ($totalOrders ?? 0), 0, ',', '.') }}</td>
                    <td class="right">-</td>
                    <td>{{ ((int) ($totalOrders ?? 0)) > 0 ? 'Ada Aktivitas' : 'Tidak Ada Order' }}</td>
                </tr>
                <tr>
                    <td><b>Rata-rata Order</b></td>
                    <td class="right">-</td>
                    <td class="right">Rp {{ number_format((int) ($averageOrderValue ?? 0), 0, ',', '.') }}</td>
                    <td class="right">-</td>
                    <td>
                        @php $avg = (int) ($averageOrderValue ?? 0); @endphp
                        @if ($avg > 800000000)
                            Tinggi
                        @elseif($avg > 500000000)
                            Sedang
                        @else
                            Rendah
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="h">Detail Project</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 28%;">Client</th>
                    <th style="width: 16%;">Tanggal</th>
                    <th style="width: 14%;" class="right">Grand Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse (($orders ?? collect()) as $order)
                    @php
                        $package = null;
                        if (! empty($order->name)) {
                            $package = $order->name;
                        } elseif ($order->relationLoaded('items') && $order->items->isNotEmpty()) {
                            $first = $order->items->first();
                            $package = $first?->product?->name ?? 'Custom Package';
                            if ($order->items->count() > 1) {
                                $package .= ' (+' . ($order->items->count() - 1) . ' more)';
                            }
                        } else {
                            $package = 'Custom Package';
                        }

                        $status = $order->status;
                        $statusValue = is_object($status) && property_exists($status, 'value') ? $status->value : $status;
                        $statusText = ucfirst((string) $statusValue);
                    @endphp
                    <tr>
                        <td>
                            {{ $order->prospect?->name_event ?? '-' }}<br>
                            <span class="muted">{{ $statusText }}</span>
                        </td>
                        <td>{{ $order->closing_date ? \Carbon\Carbon::parse($order->closing_date)->format('d/m/Y') : \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</td>
                        <td class="right">Rp {{ number_format((int) ($order->grand_total ?? $order->total_price ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="center muted">Tidak ada order pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="h">Detail Tahun Berjalan ({{ $currentYear ?? '-' }})</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 24%;">Bulan</th>
                    <th style="width: 5%;" class="right">Total</th>
                    <th style="width: 22%;" class="right">Revenue</th>
                    <th style="width: 20%;" class="right">Target Bulanan</th>
                    <th style="width: 10%;" class="right">%</th>
                    <th style="width: 8%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @php $limit = (int) ($currentMonth ?? 12); @endphp
                @foreach (($currentYearData['monthly'] ?? []) as $m => $row)
                    @if ((int) $m <= $limit)
                        <tr>
                            <td><b>{{ $row['name'] ?? $m }}</b></td>
                            <td class="right">{{ number_format((int) ($row['orders'] ?? 0), 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format((int) ($row['revenue'] ?? 0), 0, ',', '.') }}</td>
                            <td class="right">Rp {{ number_format((int) ($row['target'] ?? 0), 0, ',', '.') }}</td>
                            <td class="right">{{ number_format((float) ($row['achievement'] ?? 0), 1, ',', '.') }}%</td>
                            <td>
                                @php $ach = (float) ($row['achievement'] ?? 0); @endphp
                                @if ($ach >= 100)
                                    Tercapai
                                @elseif($ach >= 75)
                                    Hampir
                                @else
                                    Belum
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
                <tr>
                    <td><b>TOTAL TAHUN {{ $currentYear ?? '-' }}</b></td>
                    <td class="right"><b>{{ number_format((int) ($currentYearData['summary']['orders'] ?? 0), 0, ',', '.') }}</b></td>
                    <td class="right"><b>Rp {{ number_format((int) ($currentYearData['summary']['revenue'] ?? 0), 0, ',', '.') }}</b></td>
                    <td class="right"><b>Rp {{ number_format((int) ($currentYearData['summary']['target'] ?? 0), 0, ',', '.') }}</b></td>
                    <td class="right"><b>{{ number_format((float) ($currentYearData['summary']['achievement'] ?? 0), 1, ',', '.') }}%</b></td>
                    <td><b>
                        @php $sAch = (float) ($currentYearData['summary']['achievement'] ?? 0); @endphp
                        @if ($sAch >= 100)
                            Tercapai
                        @elseif($sAch >= 75)
                            Hampir
                        @else
                            Belum
                        @endif
                    </b></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="h" style="margin-top: 10px;">Detail Tahun Sebelumnya ({{ (int) ($currentYear ?? 0) - 1 }})</div>
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 24%;">Bulan</th>
                    <th style="width: 5%;" class="right">Total</th>
                    <th style="width: 22%;" class="right">Revenue</th>
                    <th style="width: 20%;" class="right">Target Bulanan</th>
                    <th style="width: 10%;" class="right">%</th>
                    <th style="width: 8%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach (($previousYearData['monthly'] ?? []) as $m => $row)
                    <tr>
                        <td><b>{{ $row['name'] ?? $m }}</b></td>
                        <td class="right">{{ number_format((int) ($row['orders'] ?? 0), 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format((int) ($row['revenue'] ?? 0), 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format((int) ($row['target'] ?? 0), 0, ',', '.') }}</td>
                        <td class="right">{{ number_format((float) ($row['achievement'] ?? 0), 1, ',', '.') }}%</td>
                        <td>
                            @php $ach = (float) ($row['achievement'] ?? 0); @endphp
                            @if ($ach >= 100)
                                Tercapai
                            @elseif($ach >= 75)
                                Hampir
                            @else
                                Belum
                            @endif
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td><b>TOTAL TAHUN {{ (int) ($currentYear ?? 0) - 1 }}</b></td>
                    <td class="right"><b>{{ number_format((int) ($previousYearData['summary']['orders'] ?? 0), 0, ',', '.') }}</b></td>
                    <td class="right"><b>Rp {{ number_format((int) ($previousYearData['summary']['revenue'] ?? 0), 0, ',', '.') }}</b></td>
                    <td class="right"><b>Rp {{ number_format((int) ($previousYearData['summary']['target'] ?? 0), 0, ',', '.') }}</b></td>
                    <td class="right"><b>{{ number_format((float) ($previousYearData['summary']['achievement'] ?? 0), 1, ',', '.') }}%</b></td>
                    <td><b>
                        @php $sAch = (float) ($previousYearData['summary']['achievement'] ?? 0); @endphp
                        @if ($sAch >= 100)
                            Tercapai
                        @elseif($sAch >= 75)
                            Hampir
                        @else
                            Belum
                        @endif
                    </b></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        @php
            $cm = (int) ($currentMonth ?? 0);
            $yearRevenue = (int) ($currentYearData['summary']['revenue'] ?? 0);
            $projected = $cm > 0 ? (int) round(($yearRevenue / $cm) * 12) : 0;
        @endphp
        <div class="box center">
            <div class="h">Proyeksi Tahun {{ $currentYear ?? '-' }}</div>
            <div class="muted">Proyeksi revenue akhir tahun (berdasarkan {{ $cm }} bulan berjalan)</div>
            <div style="font-weight:700; font-size: 14px; margin-top: 4px;">Rp {{ number_format($projected, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="section">
        <table style="width:100%; border-collapse: collapse;">
            <tr>
                <td style="width:55%; vertical-align: top; padding-right: 10px;">
                    <div class="box">
                        <div class="h">Tips Sukses Account Manager</div>
                        <div class="muted">
                            1. Follow up client secara berkala<br>
                            2. Berikan solusi sesuai kebutuhan<br>
                            3. Pahami produk dan layanan<br>
                            4. Kelola waktu dan prioritas dengan baik<br>
                            5. Bangun komunikasi yang jelas dan transparan
                        </div>
                    </div>
                </td>
                <td style="width:45%; vertical-align: top; padding-left: 10px;">
                    <div class="box">
                        <table style="width:100%; border-collapse: collapse;">
                            <tr>
                                <td class="muted">Target Bulanan</td>
                                <td class="right">Rp {{ number_format($targetAmount, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="muted">Terealisasi</td>
                                <td class="right">Rp {{ number_format($revenue, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="muted">Persentase</td>
                                <td class="right">{{ number_format($achievement, 1, ',', '.') }}%</td>
                            </tr>
                            <tr>
                                <td class="muted">Status Target</td>
                                <td class="right">
                                    @if ($achievement >= 100)
                                        TERCAPAI
                                    @elseif($achievement >= 75)
                                        HAMPIR TERCAPAI
                                    @else
                                        BELUM TERCAPAI
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="h">Payroll</div>
        @if (! empty($payrollData))
            <table class="grid">
                <thead>
                    <tr>
                        <th>Gaji Pokok</th>
                        <th>Tunjangan</th>
                        <th>Bonus</th>
                        <th>Pengurangan</th>
                        <th class="right">Monthly Salary</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Rp {{ number_format((int) ($payrollData->gaji_pokok ?? 0), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((int) ($payrollData->tunjangan ?? 0), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((int) ($payrollData->bonus ?? 0), 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((int) ($payrollData->pengurangan ?? 0), 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format((int) ($payrollData->monthly_salary ?? 0), 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <div class="muted">Data payroll tidak tersedia untuk periode ini.</div>
        @endif
    </div>

    <div class="section">
        <div class="h">Leave ({{ $monthName ?? '-' }} {{ $year ?? '-' }})</div>
        @if (($leaveData ?? collect())->isNotEmpty())
            <table class="grid">
                <thead>
                    <tr>
                        <th style="width: 8%;" class="center">No</th>
                        <th style="width: 28%;">Tipe</th>
                        <th style="width: 18%;">Mulai</th>
                        <th style="width: 18%;">Selesai</th>
                        <th style="width: 10%;" class="right">Hari</th>
                        <th style="width: 18%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($leaveData ?? collect()) as $i => $leave)
                        <tr>
                            <td class="center">{{ $i + 1 }}</td>
                            <td>{{ $leave->leaveType?->name ?? '-' }}</td>
                            <td>{{ $leave->start_date ? $leave->start_date->format('d-m-Y') : '-' }}</td>
                            <td>{{ $leave->end_date ? $leave->end_date->format('d-m-Y') : '-' }}</td>
                            <td class="right">{{ number_format((int) ($leave->total_days ?? 0), 0, ',', '.') }}</td>
                            <td>{{ ucfirst((string) ($leave->status ?? '-')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="muted">Tidak ada data leave pada periode ini.</div>
        @endif
    </div>

    <div class="section box center">
        @if ($achievement >= 100)
            <div class="h">EXCELLENT PERFORMANCE</div>
            <div class="muted">Target tercapai dengan sempurna. Pertahankan konsistensi.</div>
        @elseif($achievement >= 75)
            <div class="h">KEEP PUSHING</div>
            <div class="muted">Tinggal sedikit lagi untuk mencapai target. Konsistensi adalah kunci.</div>
        @else
            <div class="h">NEVER GIVE UP</div>
            <div class="muted">Setiap tantangan adalah kesempatan untuk berkembang. Tetap semangat.</div>
        @endif
    </div>

    <div class="section">
        <table class="sig" style="width:100%; border-collapse: collapse; margin-top: 10px;">
            <tr>
                <td style="width:45%; text-align:center; vertical-align: top;">
                    <div style="font-weight:700;">Account Manager</div>
                    <div style="height: 70px; border-bottom: 1px solid #d1d5db; margin: 18px 0 10px 0;"></div>
                    <div style="font-weight:700;">{{ $accountManager->name ?? '-' }}</div>
                    <div class="muted">Tanggal: {{ now()->format('d/m/Y') }}</div>
                </td>
                <td style="width:10%;"></td>
                <td style="width:45%; text-align:center; vertical-align: top;">
                    <div style="font-weight:700;">Direktur</div>
                    <div style="height: 70px; border-bottom: 1px solid #d1d5db; margin: 18px 0 10px 0;"></div>
                    <div style="font-weight:700;">Rama Dhona Utama</div>
                    <div class="muted">{{ $companyName }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="note">
        <b>CATATAN:</b> Laporan ini dibuat oleh sistem {{ $companyName }} untuk periode {{ $monthName ?? '-' }} {{ $year ?? '-' }} pada {{ now()->format('d/m/Y H:i') }}.
    </div>
</body>
</html>
