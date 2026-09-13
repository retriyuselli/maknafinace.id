<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Arus Kas</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2a37; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 6px; text-transform: uppercase; }
        .meta { text-align: center; color: #555; font-size: 9px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 7px 8px; }
        th { background: #e9e9e9; text-align: left; }
        .right { text-align: right; white-space: nowrap; }
        .in { color: #148a63; }
        .out { color: #c0392b; }
        .total-row td { font-weight: bold; background: #f5f5f5; }
        .company { text-align: center; margin-bottom: 10px; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <p class="company">{{ $companyLabel ?? config('app.name') }}</p>
    <h1>Laporan Arus Kas</h1>
    <div class="meta">
        Dicetak pada: {{ $generatedDate }}<br>
        Periode:
        {{ \Carbon\Carbon::parse($filterStartDate)->format('d M Y') }}
        –
        {{ \Carbon\Carbon::parse($filterEndDate)->format('d M Y') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th class="right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $label => $amount)
                @php $isIn = \Illuminate\Support\Str::contains(mb_strtolower((string) $label), 'masuk'); @endphp
                <tr>
                    <td>{{ $label }}</td>
                    <td class="right {{ $isIn ? 'in' : 'out' }}">Rp {{ number_format((int) $amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total Masuk</td>
                <td class="right in">Rp {{ number_format((int) $totalIn, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Keluar</td>
                <td class="right out">Rp {{ number_format((int) $totalOut, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>Kas Bersih</td>
                <td class="right {{ $net >= 0 ? 'in' : 'out' }}">Rp {{ number_format((int) $net, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
