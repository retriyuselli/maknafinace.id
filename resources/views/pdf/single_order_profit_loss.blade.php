<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Laba Rugi #{{ $order->prospect->name_event }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 110px 1.5cm 2.2cm 2cm;
            /* top, right, bottom, left */
        }

        body {
            color: #000000;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            font-weight: 400;
            margin: 0;
            padding: 0;
            line-height: 1.2;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            max-width: 100%;
        }

        /* Header — tampil di setiap halaman */
        header {
            position: fixed;
            top: -95px;
            left: 0;
            right: 0;
        }

        table.header {
            border-bottom: 1px solid #ddd;
            margin: 0;
            padding-bottom: 4px;
            border-collapse: collapse;
        }

        table.header td {
            padding: 0;
            line-height: 1.15;
            vertical-align: middle;
        }

        table.header img {
            max-height: 42px;
            max-width: 200px;
            width: auto;
            vertical-align: middle;
        }

        table.header h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 1px 0;
            padding: 0;
            line-height: 1.15;
        }

        table.header p {
            font-size: 11px;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }

        .page-footer {
            position: fixed;
            bottom: -45px;
            left: 0;
            right: 0;
            height: 22px;
            border-top: 1px solid #ddd;
        }

        /* Table Base */
        table {
            border-collapse: collapse;
            margin-bottom: 0;
            width: 100%;
        }

        table.bordered {
            margin-bottom: 0;
        }

        th,
        td {
            padding: 2px 5px;
            text-align: left;
            vertical-align: top;
            line-height: 1.15;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        table.bordered th,
        table.bordered td {
            border: 1px solid #ddd;
            padding: 2px 5px;
            font-size: 11px;
            line-height: 1.15;
        }

        table.bordered p,
        table.bordered div,
        table.bordered ul,
        table.bordered li {
            margin: 0;
            padding: 0;
            line-height: 1.15;
        }

        .cell-note {
            color: #555;
            font-size: 10px;
            line-height: 1.15;
            margin: 0;
            padding: 0;
        }

        /* Profit Loss Specific Styles */
        .profit-row {
            background-color: #e3f2fd;
            border: none;
        }

        .loss-row {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
        }

        .profit-text {
            color: #0d47a1;
            font-weight: bold;
        }

        .loss-text {
            color: #721c24;
            font-weight: bold;
        }

        .received-row {
            background-color: #e3f2fd;
        }

        .analysis-box {
            padding: 8px 10px;
            margin: 16px 0 8px 0;
            border: none;
        }

        .analysis-box.profit {
            background-color: #e3f2fd;
        }

        .analysis-box.profit .profit-text {
            color: #0d47a1;
        }

        .analysis-box.profit .profit-row {
            background-color: #e3f2fd;
            border: none;
        }

        .analysis-box.loss {
            background-color: #fff8f8;
        }

        /* Section Styling */
        .section-container {
            margin: 16px 0 0 0;
        }

        .sub-section-title {
            font-size: 12px;
            margin: 0 0 4px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 2px;
            font-weight: bold;
        }

        /* Additional Table Styling */
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        /* Font Size Controls */
        .bordered {
            font-size: 11px;
        }

        /* Invoice Title */
        .invoice-title {
            margin: 4px 0 2px 0;
            text-align: center;
        }

        .invoice-title h1 {
            font-size: 20px;
            margin: 0;
            padding: 0;
            line-height: 1.15;
        }

        .invoice-title h4 {
            font-size: 12px;
            font-weight: normal;
            margin: 1px 0 0 0;
            padding: 0;
            line-height: 1.15;
        }

        /* Invoice Details */
        table.invoice-details {
            margin: 0 0 6px 0;
        }

        .invoice-details td {
            border: none;
            padding: 2px 0 0 0;
            vertical-align: top;
            width: 50%;
            line-height: 1.2;
        }

        .invoice-details .bold {
            margin: 0 0 2px 0;
            font-size: 12px;
            line-height: 1.2;
        }

        .invoice-details address {
            font-size: 11px;
            font-style: normal;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        /* Items Table */
        .items-table {
            display: table;
            page-break-inside: auto;
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000000;
            margin-bottom: 5px;
            /* Consistent spacing */
        }

        .items-table tr,
        .items-table td,
        .items-table th {
            break-inside: avoid !important;
            page-break-after: auto;
            page-break-inside: avoid !important;
        }

        .items-table thead th {
            background-color: #eceff1;
            /* Light grey-blue background for header */
            color: #37474f;
            /* Dark grey-blue text */
            font-weight: bold;
            /* Poppins Semibold */
            padding: 3px 5px;
            text-align: left;
            /* Header cells have a stronger bottom border and a right border */
            border-bottom: 1px solid #90a4ae;
            /* Darker separator for header */
            border-right: 1px solid #cfd8dc;
            /* Light vertical separator */
            font-size: 11px;
            letter-spacing: 0.5px;
            vertical-align: middle;
        }

        /* Remove the right border from the last header cell */
        .items-table thead th:last-child {
            border-right: none;
        }

        .items-table tbody td {
            padding: 3px 6px;
            /* Body cells have a lighter bottom border and a right border */
            border-bottom: 1px solid #cfd8dc;
            /* Light horizontal separator for rows */
            border-right: 1px solid #cfd8dc;
            /* Light vertical separator */
            vertical-align: top;
            font-size: 11px;
            color: #000000;
            /* Slightly softer text color */
        }

        /* Remove the right border from the last cell in a body row */
        .items-table tbody td:last-child {
            border-right: none;
        }

        /* The last row of items should not have a bottom border */
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tfoot {
            display: table-footer-group;
        }

        /* Vendor Items Table */
        .vendor-item {
            font-size: 11px;
            margin-bottom: 3px;
        }

        /* Totals Table */
        .total-table {
            margin-left: 50%;
            margin-top: 8px;
            width: 50%;
        }

        .total-table th {
            font-weight: bold;
        }

        .total-table td:last-child {
            text-align: right;
        }

        /* Payment History */
        .payment-history {
            margin-top: 16px;
        }

        .payment-history h3 {
            font-size: 12px;
            margin: 0 0 4px 0;
        }

        /* Warning Box */
        .warning {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            color: #721c24;
            margin: 8px 0;
            padding: 8px;
        }

        /* Footer */
        .footer {
            border-top: 1px solid #ddd;
            font-size: 10px;
            margin-top: 16px;
            padding-top: 8px;
            page-break-inside: auto;
        }

        .footer td {
            color: #000000;
            page-break-inside: auto;
        }

        /* Page Break */
        .page-break {
            page-break-before: auto;
        }

        /* Helpers */
        .bold {
            font-weight: 600;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .small {
            font-size: 10px;
        }

        .footer ul {
            margin: 2px 0 0 16px;
            padding: 0;
        }

        .footer li {
            margin: 0;
            line-height: 1.25;
        }

        .footer p {
            margin: 0;
            line-height: 1.2;
        }

        .info-description {
            color: #000000;
            font-size: 16px;
            line-height: 1;
            margin-top: 2px;
            white-space: normal;
        }

        .vendor-description {
            color: #000000;
            font-size: 16px;
            line-height: 1;
            margin-top: 2px;
            white-space: normal;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 10px 0;
        }

        /* Badge Simulation */
        .badge {
            border-radius: .25rem;
            display: inline-block;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            padding: .25em .4em;
            text-align: center;
            vertical-align: baseline;
            white-space: nowrap;
        }

        .bg-success {
            background-color: #28a745;
            color: #fff;
        }

        .bg-warning {
            background-color: #ffc107;
            color: #212529;
        }

        @media print {
            .items-table {
                page-break-inside: auto;
            }

            .items-table tr,
            .items-table td,
            .items-table th {
                page-break-inside: avoid !important;
            }

            .items-table thead {
                display: table-header-group;
            }

            .items-table tfoot {
                display: table-footer-group;
            }
        }
    </style>

</head>

<body>
    <!-- Header (berulang di setiap halaman) -->
    <header>
    <table class="header" style="width: 100%;">
        <tr>
            <td style="width: 60%; text-align: left; vertical-align: top;">
                <h2>{{ config('app.name', 'Your Company') }}</h2>
                <p>{{ config('invoice.address', 'Your Company Address') }}</p>
                <p>Phone : {{ config('invoice.phone', '+123456789') }}</p>
                <p>Email : {{ config('invoice.email', 'info@yourcompany.com') }}</p>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: middle;">
                {{-- Embed image using Base64 for reliable PDF rendering --}}
                @php
                    $logoPath = public_path(config('invoice.logo', 'images/logo.png'));
                    if (file_exists($logoPath)) {
                        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
                        $logoData = file_get_contents($logoPath);
                        $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
                    } else {
                        $logoBase64 = ''; /* Handle missing logo */
                    }
                @endphp
                @if ($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Company Logo">
                @else
                    {{-- Optional: Display text or placeholder if logo is missing --}}
                    <span>Logo</span>
                @endif
            </td>
        </tr>
    </table>
    </header>

    <div class="page-footer"></div>

    <!-- Invoice Title -->
    <div class="invoice-title">
        <h1>Laporan Laba Rugi</h1>
        <h4>#{{ \Illuminate\Support\Str::title($order->prospect->name_event) }}</h4>
    </div>

    <!-- Invoice Details -->
    <table class="invoice-details">
        <tr>
            <td>
                <div class="bold">Billed To :</div>
                <address>
                    @if (filled($order->prospect->name_event))
                        Event : {{ \Illuminate\Support\Str::title($order->prospect->name_event) }}<br>
                    @endif
                    @if (filled($order->prospect->name_cpp) || filled($order->prospect->name_cpw))
                        Nama : {{ collect([\Illuminate\Support\Str::title($order->prospect->name_cpp), \Illuminate\Support\Str::title($order->prospect->name_cpw)])->filter()->implode(' & ') }}<br>
                    @endif
                    @if (filled($order->prospect->address))
                        Alamat : {{ \Illuminate\Support\Str::title($order->prospect->address) }}<br>
                    @endif
                    @if (filled($order->prospect->phone))
                        No. Tlp : +62{{ $order->prospect->phone }}<br>
                    @endif
                    @if (filled($order->prospect->venue) || filled($order->pax))
                        Venue : {{ collect([\Illuminate\Support\Str::title($order->prospect->venue), filled($order->pax) ? $order->pax.' Pax' : null])->filter()->implode(' / ') }}<br>
                    @endif
                    @if (filled($order->employee?->name))
                        Account Manager : {{ \Illuminate\Support\Str::title($order->employee->name) }}<br>
                    @endif
                </address>
            </td>
            <td class="text-right">
                <div class="bold">Laporan Information :</div>
                <address>
                    Tanggal Laporan : {{ $generatedDate ?? now()->format('d F Y H:i') }}<br>
                    Status Pembayaran : @if ($order->is_paid) <span style="color: #28a745; font-weight: bold;">Lunas</span> @else <span style="color: #dc3545; font-weight: bold;">Belum Lunas</span> @endif<br>
                    @if (filled($order->prospect->date_lamaran))
                        Tgl Lamaran : {{ \Carbon\Carbon::parse($order->prospect->date_lamaran)->format('d F Y') }}<br>
                    @endif
                    @if (filled($order->prospect->date_akad))
                        Tgl Akad : {{ \Carbon\Carbon::parse($order->prospect->date_akad)->format('d F Y') }}<br>
                    @endif
                    @if (filled($order->prospect->date_resepsi))
                        Tgl Resepsi : {{ \Carbon\Carbon::parse($order->prospect->date_resepsi)->format('d F Y') }}<br>
                    @endif
                </address>
            </td>
        </tr>
    </table>

    <!-- Financial Summary Table -->
    <div class="billing-summary" style="margin-top: 24px;">
        <table class="bordered">
            <thead>
                <tr>
                    <th colspan="2">Ringkasan Keuangan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Paket Awal</td>
                    <td class="text-right">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                </tr>

                @if ($order->promo > 0)
                    <tr>
                        <td>Diskon</td>
                        <td class="text-right">- Rp {{ number_format($order->promo, 0, ',', '.') }}</td>
                    </tr>
                @endif

                @if ($order->penambahan > 0)
                    <tr>
                        <td>Penambahan</td>
                        <td class="text-rose-800 text-right">Rp {{ number_format($order->penambahan, 0, ',', '.') }}</td>
                    </tr>
                @endif

                @if ($order->pengurangan > 0)
                    <tr>
                        <td>Pengurangan</td>
                        <td class="text-right">Rp {{ number_format($order->pengurangan, 0, ',', '.') }}</td>
                    </tr>
                @endif

                <tr>
                    <td class="bold">Grand Total (Pendapatan)</td>
                    <td class="text-right bold">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Sudah Dibayar</td>
                    <td class="text-right">Rp {{ number_format($order->bayar, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Sisa Tagihan (Balance Due)</td>
                    <td class="text-right">Rp {{ number_format($order->sisa, 0, ',', '.') }}</td>
                </tr>
                @php
                    $totalExpenses = $order->tot_pengeluaran ?? $order->expenses->sum('amount') ?? 0;
                    $profitLoss = $order->laba_kotor ?? (($order->grand_total ?? 0) - $totalExpenses);
                @endphp
                <tr>
                    <td class="bold">Total Pengeluaran</td>
                    <td class="text-right bold">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</td>
                </tr>
                <tr class="{{ $profitLoss >= 0 ? 'profit-row' : 'loss-row' }}">
                    <td class="bold {{ $profitLoss >= 0 ? 'profit-text' : 'loss-text' }}">
                        {{ $profitLoss >= 0 ? 'Laba Kotor' : 'Rugi Kotor' }}
                    </td>
                    <td class="text-right bold {{ $profitLoss >= 0 ? 'profit-text' : 'loss-text' }}">
                        <strong>Rp {{ number_format($profitLoss, 0, ',', '.') }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Detail Pengurangan per Produk dalam Order -->
    @php
        $allProductPengurangans = collect();
        if ($order->items && $order->items->count() > 0) {
            foreach ($order->items as $orderItem) {
                if ($orderItem->product && $orderItem->product->pengurangans->count() > 0) {
                    foreach ($orderItem->product->pengurangans as $pengurangan) {
                        // Menambahkan nama produk ke objek pengurangan untuk referensi
                        $pengurangan->product_name = $orderItem->product->name;
                        $allProductPengurangans->push($pengurangan);
                    }
                }
            }
        }
    @endphp

    @if ($allProductPengurangans->isNotEmpty())
        <div class="section-container">
            <h3 class="sub-section-title">Rincian Item Pengurangan Produk</h3>
            <table class="bordered">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%;">No</th>
                        <th>Deskripsi Pengurangan</th>
                        <th class="text-right" style="width: 20%;">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allProductPengurangans as $index => $itemPengurangan)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                {{ \Illuminate\Support\Str::title($itemPengurangan->description ?? 'N/A') }}
                            </td>
                            <td class="text-right">Rp {{ number_format($itemPengurangan->amount ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Pengeluaran Vendor -->
    @php
        $hasExpenseNotes = $order->expenses->contains(fn ($expense) => filled($expense->note));
    @endphp
    <div class="section-container">
        <h3 class="sub-section-title">Rincian Pengeluaran Vendor</h3>
        <table class="bordered">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">No</th>
                    <th style="width: 15%;">Tanggal</th>
                    <th>Vendor</th>
                    <th style="width: 15%;">No ND</th>
                    <th class="text-right" style="width: 20%;">Jumlah</th>
                    @if ($hasExpenseNotes)
                        <th>Keterangan</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($order->expenses as $index => $expense)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $expense->date_expense ? \Carbon\Carbon::parse($expense->date_expense)->format('d M Y') : '-' }}</td>
                        <td>{{ \Illuminate\Support\Str::title($expense->vendor->name ?? 'N/A') }}</td>
                        <td>{{ $expense->no_nd ? 'ND-0' . $expense->no_nd : '-' }}</td>
                        <td class="text-right">Rp {{ number_format($expense->amount ?? 0, 0, ',', '.') }}</td>
                        @if ($hasExpenseNotes)
                            <td>{{ filled($expense->note) ? \Illuminate\Support\Str::title($expense->note) : '' }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $hasExpenseNotes ? 6 : 5 }}" class="text-center" style="font-style: italic; color: #666;">
                            Tidak Ada Data Pengeluaran Vendor.
                        </td>
                    </tr>
                @endforelse
                @if($order->expenses->count() > 0)
                    <tr style="background-color: #f8f9fa;">
                        <td colspan="4" class="text-right bold">Total Pengeluaran:</td>
                        <td class="text-right bold">Rp {{ number_format($order->expenses->sum('amount'), 0, ',', '.') }}</td>
                        @if ($hasExpenseNotes)
                            <td></td>
                        @endif
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Payment History -->
    @if (count($order->dataPembayaran) > 0)
        <div class="payment-history">
            <h3>Riwayat Pembayaran Diterima</h3>
            <table class="bordered">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th>Tanggal</th>
                        <th>Jumlah</th>
                        <th>Metode Pembayaran</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->dataPembayaran as $payment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($payment->tgl_bayar)->format('d F Y') }}</td>
                            <td class="text-right">Rp {{ number_format($payment->nominal, 0, ',', '.') }}</td>
                            <td>{{ \Illuminate\Support\Str::title($payment->paymentMethod->name ?? 'N/A') }}</td>
                            <td>{{ \Illuminate\Support\Str::title($payment->keterangan) }}</td>
                        </tr>
                    @endforeach
                    <tr class="received-row">
                        <td class="bold text-right">Total Diterima:</td>
                        <td class="text-right bold">Rp {{ number_format($order->dataPembayaran->sum('nominal'), 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <p style="margin-top: 8px; font-style: italic; font-size: 11px;">Belum Ada Pembayaran Yang Diterima.</p>
    @endif

    <!-- Analisis Laba Rugi -->
    @php
        $totalRevenue = $order->grand_total ?? 0;
        $totalExpenses = $order->tot_pengeluaran ?? $order->expenses->sum('amount') ?? 0;
        $profitLoss = $order->laba_bersih ?? ($totalRevenue - $totalExpenses);
        $profitMargin = $totalRevenue > 0 ? ($profitLoss / $totalRevenue) * 100 : 0;
    @endphp
    <div class="analysis-box {{ $profitLoss >= 0 ? 'profit' : 'loss' }}">
        <h3 class="{{ $profitLoss >= 0 ? 'profit-text' : 'loss-text' }}" style="text-align: center; margin: 0 0 4px 0; font-size: 12px;">
            Analisis Laba Rugi
        </h3>
        <table class="bordered">
            <tr>
                <td style="width: 60%;"><strong>Total Pendapatan (Revenue):</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($totalRevenue, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td><strong>Total Pengeluaran (Expenses):</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($totalExpenses, 0, ',', '.') }}</strong></td>
            </tr>
            <tr class="{{ $profitLoss >= 0 ? 'profit-row' : 'loss-row' }}">
                <td class="{{ $profitLoss >= 0 ? 'profit-text' : 'loss-text' }}"><strong>{{ $profitLoss >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}:</strong></td>
                <td class="text-right {{ $profitLoss >= 0 ? 'profit-text' : 'loss-text' }}"><strong>Rp {{ number_format($profitLoss, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td><strong>Margin {{ $profitLoss >= 0 ? 'Laba' : 'Rugi' }}:</strong></td>
                <td class="text-right {{ $profitLoss >= 0 ? 'profit-text' : 'loss-text' }}"><strong>{{ number_format($profitMargin, 2) }}%</strong></td>
            </tr>
        </table>
        
        <div style="text-align: center; margin-top: 6px; font-size: 11px;">
            @if($profitLoss >= 0)
                <p class="profit-text">✓ Proyek Ini Menghasilkan Keuntungan</p>
            @else
                <p class="loss-text">⚠ Proyek Ini Mengalami Kerugian</p>
            @endif
        </div>
    </div>

    <!-- Footer -->
    <table class="footer" style="width: 100%;">
        <tr>
            <td style="width: 65%; vertical-align: top;">
                <div class="bold">Catatan Laporan</div>
                <ul>
                    <li>Laporan Ini Menampilkan Analisis Laba Rugi Berdasarkan Data Transaksi Yang Tercatat Dalam Sistem.</li>
                    <li>Total Pendapatan Dihitung Dari Grand Total Paket Yang Telah Disepakati Dengan Klien.</li>
                    <li>Total Pengeluaran Mencakup Semua Pembayaran Yang Dilakukan Kepada Vendor Terkait.</li>
                    <li>Margin Laba/Rugi Dihitung Berdasarkan Persentase Dari Total Pendapatan.</li>
                    <li>Untuk Pertanyaan Lebih Lanjut, Hubungi Bagian Keuangan.</li>
                </ul>
            </td>
            <td style="width: 35%; text-align: right; vertical-align: top;">
                <p style="margin-bottom: 10px;">Laporan Digenerate Pada:</p>
                <p style="font-weight: bold;">{{ $generatedDate ?? now()->format('d F Y H:i') }}</p>
                <p style="margin-top: 20px;">Disetujui Oleh:</p>
                <p style="margin-top: 60px;">____________________</p>
                @php
                    // Mengambil karyawan dengan posisi 'Finance'.
                    $financeApprover = \App\Models\Employee::where('position', 'Finance')->orderBy('name')->first();
                    $approverName = $financeApprover ? \Illuminate\Support\Str::title($financeApprover->name) : 'Finance Department';
                @endphp
                <p>{{ $approverName }}</p>
            </td>
        </tr>
    </table>
</body>

</html>
