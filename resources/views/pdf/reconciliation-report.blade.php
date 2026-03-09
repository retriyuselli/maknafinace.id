<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reconciliation Report</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .header { margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1a56db; font-size: 18px; }
        .header p { margin: 2px 0; color: #555; }
        .summary-box { border: 1px solid #ddd; padding: 10px; margin-bottom: 20px; background-color: #f9f9f9; }
        .summary-grid { display: table; width: 100%; }
        .summary-item { display: table-cell; text-align: center; width: 25%; }
        .summary-value { font-size: 14px; font-weight: bold; display: block; }
        .summary-label { color: #666; font-size: 9px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-red { color: #dc2626; }
        .text-green { color: #16a34a; }
        .section-title { font-size: 12px; font-weight: bold; margin: 15px 0 5px 0; border-bottom: 2px solid #eee; padding-bottom: 3px; }
        .page-break { page-break-after: always; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #888; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Rekonsiliasi Bank</h1>
        <p><strong>Rekening:</strong> {{ $paymentMethod->name }} ({{ $paymentMethod->no_rekening }})</p>
        <p><strong>Periode:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
        <p><strong>Dicetak Oleh:</strong> {{ $user }} pada {{ $timestamp }}</p>
    </div>

    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-value text-green">{{ $statistics['matched_count'] }}</span>
                <span class="summary-label">Transaksi Cocok</span>
            </div>
            <div class="summary-item">
                <span class="summary-value text-red">{{ $statistics['unmatched_app_count'] }}</span>
                <span class="summary-label">App Belum Cocok</span>
            </div>
            <div class="summary-item">
                <span class="summary-value text-red">{{ $statistics['unmatched_bank_count'] }}</span>
                <span class="summary-label">Bank Belum Cocok</span>
            </div>
            <div class="summary-item">
                <span class="summary-value">
                    @if($statistics['total_app_transactions'] > 0)
                        {{ round(($statistics['matched_count'] / $statistics['total_app_transactions']) * 100, 1) }}%
                    @else
                        0%
                    @endif
                </span>
                <span class="summary-label">Tingkat Kecocokan</span>
            </div>
        </div>
    </div>

    @if(count($matched) > 0)
    <div class="section-title">Transaksi Cocok (Matched)</div>
    <table>
        <thead>
            <tr>
                <th width="12%">Tanggal</th>
                <th width="30%">Keterangan App</th>
                <th width="30%">Keterangan Bank</th>
                <th width="18%" class="text-right">Nominal</th>
                <th width="10%" class="text-center">Confidence</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matched as $match)
                @php 
                    $app = $match['app_transaction'];
                    $bank = $match['bank_item'];
                    $amount = $app->debit_amount ?: $app->credit_amount;
                    $isDebit = (bool) $app->debit_amount;
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($app->transaction_date)->format('d/m/Y') }}</td>
                    <td>
                        {{ \Illuminate\Support\Str::limit($app->description, 40) }}
                        <div style="font-size: 8px; color: #666;">Ref: {{ $app->source_table }} #{{ $app->source_id }}</div>
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit($bank->description, 40) }}</td>
                    <td class="text-right {{ $isDebit ? 'text-red' : 'text-green' }}">
                        {{ $isDebit ? '-' : '+' }}Rp {{ number_format($amount, 0, ',', '.') }}
                    </td>
                    <td class="text-center">{{ $match['confidence'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(count($unmatchedApp) > 0)
    <div class="page-break"></div>
    <div class="section-title">Transaksi Aplikasi Belum Cocok (Unmatched App)</div>
    <table>
        <thead>
            <tr>
                <th width="15%">Tanggal</th>
                <th width="50%">Keterangan</th>
                <th width="20%" class="text-right">Nominal</th>
                <th width="15%">Sumber</th>
            </tr>
        </thead>
        <tbody>
            @foreach($unmatchedApp as $item)
                @php 
                    $amount = $item->debit_amount ?: $item->credit_amount;
                    $isDebit = (bool) $item->debit_amount;
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->transaction_date)->format('d/m/Y') }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right {{ $isDebit ? 'text-red' : 'text-green' }}">
                        {{ $isDebit ? '-' : '+' }}Rp {{ number_format($amount, 0, ',', '.') }}
                    </td>
                    <td>{{ $item->source_table }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(count($unmatchedBank) > 0)
    <div class="section-title">Mutasi Bank Belum Cocok (Unmatched Bank)</div>
    <table>
        <thead>
            <tr>
                <th width="15%">Tanggal</th>
                <th width="65%">Keterangan</th>
                <th width="20%" class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($unmatchedBank as $item)
                @php 
                    $amount = $item->debit ?: $item->credit;
                    $isDebit = (bool) $item->debit;
                @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="text-right {{ $isDebit ? 'text-red' : 'text-green' }}">
                        {{ $isDebit ? '-' : '+' }}Rp {{ number_format($amount, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Generated by Makna Finance System on {{ $timestamp }} | Page <span class="page-number"></span>
    </div>
</body>
</html>
