<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jurnal Umum</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        .muted { color: #6B7280; }
        .title { font-size: 16px; font-weight: 700; margin: 0 0 4px 0; }
        .meta { margin: 0 0 12px 0; }
        .box { border: 1px solid #E5E7EB; padding: 8px; border-radius: 6px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #E5E7EB; padding: 6px; vertical-align: top; }
        th { background: #F9FAFB; text-align: left; }
        .right { text-align: right; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>
    <div class="title">Jurnal Umum</div>
    <div class="meta muted">
        Dicetak: {{ now()->format('d M Y H:i') }}
    </div>

    <div class="box">
        <div><strong>Filter</strong></div>
        <div class="muted">
            Periode: {{ $filters['start_date'] ?? '-' }} s/d {{ $filters['end_date'] ?? '-' }} |
            Status: {{ $filters['status'] ?? 'all' }} |
            Reference Type: {{ $filters['reference_type'] ?? 'all' }}
        </div>
    </div>

    @if($batches->isEmpty())
        <div class="muted">Tidak ada data sesuai filter.</div>
    @else
        @foreach($batches as $batch)
            <div class="box">
                <table>
                    <tr>
                        <th class="nowrap" style="width: 18%;">Batch</th>
                        <td style="width: 32%;">{{ $batch->batch_number }}</td>
                        <th class="nowrap" style="width: 18%;">Tanggal</th>
                        <td style="width: 32%;">{{ optional($batch->transaction_date)->format('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>{{ $batch->status }}</td>
                        <th>Ref</th>
                        <td>{{ $batch->reference_type }}{{ $batch->reference_id ? ' #'.$batch->reference_id : '' }}</td>
                    </tr>
                    <tr>
                        <th>Deskripsi</th>
                        <td colspan="3">{{ $batch->description }}</td>
                    </tr>
                </table>

                <table style="margin-top: 8px;">
                    <thead>
                        <tr>
                            <th style="width: 18%;">Akun</th>
                            <th>Nama Akun</th>
                            <th style="width: 20%;" class="right">Debit</th>
                            <th style="width: 20%;" class="right">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batch->journalEntries as $entry)
                            <tr>
                                <td class="nowrap">{{ $entry->account?->account_code ?? '-' }}</td>
                                <td>{{ $entry->account?->account_name ?? '-' }}</td>
                                <td class="right nowrap">{{ number_format((float) $entry->debit_amount, 2, ',', '.') }}</td>
                                <td class="right nowrap">{{ number_format((float) $entry->credit_amount, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2" class="right"><strong>Total</strong></td>
                            <td class="right nowrap"><strong>{{ number_format((float) $batch->total_debit, 2, ',', '.') }}</strong></td>
                            <td class="right nowrap"><strong>{{ number_format((float) $batch->total_credit, 2, ',', '.') }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif
</body>
</html>

