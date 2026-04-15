<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->prospect->name_event }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 200px 1cm 1.5cm 1cm;
            /* top, right, bottom, left */
        }

        body {
            color: #000000;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            font-weight: 400;
            line-height: 1;
            margin: 0;
            font-smoothing: antialiased;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            max-width: 100%;
        }

        /* Header */
        header {
            position: fixed;
            top: -160px;
            left: 0px;
            right: 0px;
            height: 100px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 5px;
            padding-bottom: 5px;
            text-align: center;
        }

        /* Page Footer */
        .page-footer {
            position: fixed;
            bottom: -40px;
            left: 0px;
            right: 0px;
            height: 30px;
            text-align: center;
            font-size: 12px;
            /* font-style: italic; */
            color: #555;
        }

        .header-table {
            width: 100%;
        }

        /* Header Company Info - Rapatkan jarak */
        header h2 {
            margin: 0 0 2px 0;
            line-height: 1;
        }

        header td {
            line-height: 1;
            padding: 0;
        }

        header img {
            max-height: 30px;
            max-width: 180px;
            width: auto;
            vertical-align: middle;
        }

        header h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }

        header p {
            font-size: 14px;
            margin: 0;
        }

        /* Table Base */
        table {
            border-collapse: collapse;
            margin-bottom: 3px;
            width: 100%;
        }

        th,
        td {
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        table.bordered th,
        table.bordered td {
            border: 1px solid #ddd;
        }

        /* Invoice Title */
        .invoice-title {
            margin: -30px 0 5px 0;
            text-align: center;
        }

        .invoice-title h1 {
            font-size: 24px;
            margin-bottom: 0;
            text-transform: uppercase;
        }

        .invoice-title h4 {
            font-size: 12px;
            font-weight: normal;
            margin-top: 0;
        }

        /* Invoice Details */
        .invoice-details td {
            border: none;
            padding: 10px 0;
            vertical-align: top;
            width: 50%;
        }

        .invoice-details address {
            font-size: 12px;
            font-style: normal;
            line-height: 1;
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
            /* Noto Sans Semibold */
            padding: 3px 4px;
            text-align: left;
            /* Header cells have a stronger bottom border and a right border */
            border-bottom: 1px solid #90a4ae;
            /* Darker separator for header */
            border-right: 1px solid #cfd8dc;
            /* Light vertical separator */
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            vertical-align: middle;
        }

        /* Remove the right border from the last header cell */
        .items-table thead th:last-child {
            border-right: none;
        }

        .items-table tbody td {
            padding: 5px 6px;
            /* Body cells have a lighter bottom border and a right border */
            border-bottom: 1px solid #cfd8dc;
            /* Light horizontal separator for rows */
            border-right: 1px solid #cfd8dc;
            /* Light vertical separator */
            vertical-align: top;
            font-size: 12px;
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
            font-size: 12px;
            margin-bottom: 5px;
        }

        /* Totals Table */
        .total-table {
            margin-left: 50%;
            margin-top: 10px;
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
            margin-top: 10px;
        }

        /* Warning Box */
        .warning {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            color: #721c24;
            margin: 10px 0;
            padding: 10px;
        }

        /* Addition and Reduction Sections */
        .addition-amount {
            color: #28a745 !important;
            font-weight: bold !important;
        }

        .reduction-amount {
            color: #dc3545 !important;
            font-weight: bold !important;
        }

        .section-container {
            margin-top: 20px;
        }

        .sub-section-title {
            font-size: 12px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            border-top: 1px solid #ddd;
            font-size: 12px;
            margin-top: 10px;
            padding-top: 20px;
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
            font-size: 12px;
        }

        .info-description {
            color: #000000;
            font-size: 12px;
            line-height: 1;
            margin-top: 0;
            white-space: normal;
        }

        .vendor-description {
            color: #000000;
            font-size: 12px;
            line-height: 1;
            margin-top: 0;
            white-space: normal;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 5px 0;
        }

        /* Badge Simulation */
        .badge {
            border-radius: .25rem;
            display: inline-block;
            font-size: 12px;
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

        /* Watermark */
        .watermark {
            color: rgba(0, 0, 0, 0.1);
            font-size: 150px;
            font-weight: bold;
            left: 50%;
            letter-spacing: 5px;
            pointer-events: none;
            position: fixed;
            text-transform: uppercase;
            top: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            white-space: nowrap;
            z-index: -1000;
        }

        .watermark.paid {
            color: rgba(40, 167, 69, 0.15);
        }

        .notes-content ul {
            list-style: none;
            padding-left: 0px;
            margin: 0;
        }

        .notes-content li {
            list-style: none;
            margin-bottom: 0px;
            padding-left: 14px;
            margin-left: 0px;
            position: relative;
        }

        .notes-content li:before {
            content: '-';
            left: 0;
            position: absolute;
            top: 0;
        }

        /* Menghilangkan margin default pada elemen p pertama agar sejajar dengan bullet */
        .notes-content p:first-child {
            margin-top: 0px;
            margin-bottom: 0px;
        }

        /* Memberikan jarak antar paragraf jika ada lebih dari satu */
        .notes-content p {
            margin-bottom: 5px;
            margin-left: 0;
            padding-left: 0;
        }

        .footer ul {
            list-style: none;
            margin: 0;
            padding-left: 0px;
        }

        .footer li {
            list-style: none;
            margin-bottom: 2px;
            padding-left: 14px;
            position: relative;
        }

        .footer li:before {
            content: '-';
            left: 0;
            position: absolute;
            top: 0;
        }

        .watermark.pending {
            color: rgba(255, 193, 7, 0.15);
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
    <!-- Watermark -->
    @if ($order->is_paid)
        <div class="watermark paid">Paid</div>
    @else
        <div class="watermark pending">Partial Paid</div>
    @endif

    <!-- Header -->
    <header>
        <table class="header-table">
            <tr>
                <td style="line-height: 1.2; font-size: 13px;">
                    <div>
                        <b>{{ $company->company_name ?? ($companyName ?? config('app.name')) }}</b><br>
                        {{ $company->address ?? 'Jln. Sintraman Jaya, No. 2148, Sekip Jaya, Palembang' }}<br>
                        Tlp: {{ $company->phone ?? '+62 822-9796-2600' }} | Email: {{ $company->email ?? 'maknawedding@gmail.com' }}
                    </div>
                </td>
                <td style="width: auto; height: 35px; text-align: right; vertical-align: middle;">
                    @if (! empty($logoBase64))
                        <img src="{{ $logoBase64 }}" alt="Company Logo">
                    @else
                        <span>Logo</span>
                    @endif
                </td>
            </tr>
        </table>
    </header>

    <!-- Page Footer -->
    <div class="page-footer">
        --* Dibuat secara otomatis sehingga tidak membutuhkan tanda tangan *--
    </div>

    <!-- Invoice Title -->
    <div class="invoice-title">
        <h1>INVOICE</h1>
        <h4>#{{ $order->prospect->name_event }}</h4>
    </div>

    <!-- Invoice Details -->
    <table class="invoice-details">
        <tr>
            <td style="width: 60%;">
                <div class="bold">Billed To :</div>
                <table style="width: 100%; font-size: 12px; line-height: 1;">
                    <tr>
                        <td style="width: 130px; vertical-align: top; padding: 2px 0;">Event</td>
                        <td style="width: 10px; vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">{{ $order->prospect->name_event }}</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Nama</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">{{ $order->prospect->name_cpp }} &
                            {{ $order->prospect->name_cpw }}</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Alamat</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">{{ $order->prospect->address }}</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">No. Tlp</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">
                            {{ $order->prospect->phone ? '+62' . $order->prospect->phone : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Venue</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">{{ $order->prospect->venue ?? 'N/A' }} /
                            {{ $order->pax ?? 'N/A' }} Pax</td>
                    </tr>
                </table>
            </td>
            <td style="padding-left: 60px;">
                <div class="bold">Invoice Information :</div>
                <table style="width: 100%; font-size: 12px; line-height: 1;">
                    <tr>
                        <td style="width: 120px; vertical-align: top; padding: 2px 0;">Invoice Date</td>
                        <td style="width: 10px; vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">{{ now()->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Due Date</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">
                            {{ $order->due_date ? \Carbon\Carbon::parse($order->due_date)->format('d F Y') : now()->addDays(config('invoice.payment_days', 7))->format('d F Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Tgl Lamaran</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">
                            {{ $order->prospect->date_lamaran ? \Carbon\Carbon::parse($order->prospect->date_lamaran)->format('d F Y') : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Tgl Akad</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">
                            {{ $order->prospect->date_akad ? \Carbon\Carbon::parse($order->prospect->date_akad)->format('d F Y') : '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top; padding: 2px 0;">Tgl Resepsi</td>
                        <td style="vertical-align: top; padding: 2px 0;">:</td>
                        <td style="vertical-align: top; padding: 2px 0;">
                            {{ $order->prospect->date_resepsi ? \Carbon\Carbon::parse($order->prospect->date_resepsi)->format('d F Y') : '-' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Billing Summary Table -->
    <div class="billing-summary" style="margin-top: 30px;">
        <table class="bordered">
            <thead>
                <tr>
                    <th colspan="2">DETAIL TAGIHAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Paket Awal</td>
                    <td class="text-right">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                </tr>

                @if (($totalAdditionAmount ?? 0) > 0)
                    <tr>
                        <td>Total Penambahan dari Produk</td>
                        <td class="text-right addition-amount">+ Rp
                            {{ number_format((int) ($totalAdditionAmount ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @endif

                @if ($order->promo > 0)
                    <tr>
                        <td>Diskon</td>
                        <td class="text-right">- Rp {{ number_format($order->promo, 0, ',', '.') }}</td>
                    </tr>
                @endif

                @if ($order->penambahan > 0)
                    <tr>
                        <td>Penambahan</td>
                        <td class="text-right">Rp {{ number_format($order->penambahan, 0, ',', '.') }}</td>
                    </tr>
                @endif

                @if ($order->pengurangan > 0)
                    <tr>
                        <td>Pengurangan</td>
                        <td class="text-right">Rp {{ number_format($order->pengurangan, 0, ',', '.') }}</td>
                    </tr>
                @endif

                <tr>
                    <td class="bold">Grand Total</td>
                    <td class="text-right bold">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Sudah Dibayar</td>
                    <td class="text-right">Rp {{ number_format($order->bayar, 0, ',', '.') }}</td>
                </tr>
                <tr class="total"> <!-- You might want to style .total rows specifically if needed -->
                    <td class="bold">Sisa Tagihan (Balance Due)</td>
                    <td class="text-right"><strong>Rp {{ number_format($order->sisa, 0, ',', '.') }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Detail Penambahan per Produk dalam Order -->
    @if (($allProductPenambahanHarga ?? collect())->isNotEmpty())
        <div class="section-container" style="margin-top: 20px;">
            <h3 class="sub-section-title"
                style="font-size: 12px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                Rincian Item Penambahan Produk</h3>
            <table class="bordered" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">No</th>
                        <th style="width: 70%;">Deskripsi Penambahan</th>
                        <th style="width: 25%; text-align: right;">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($allProductPenambahanHarga ?? collect()) as $index => $itemPenambahan)
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td>
                                {{ $itemPenambahan->vendor->name ?? 'N/A' }}
                                @if ($itemPenambahan->description)
                                    <div class="notes-content" style="font-size: 12px; margin-left: 15px; color: #000000;">
                                        {!! strip_tags($itemPenambahan->description, '<li><strong><ul><li><br><span><div>') !!}
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: right; color: #28a745; font-weight: bold;">+ Rp
                                {{ number_format($itemPenambahan->harga_publish ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Detail Pengurangan per Produk dalam Order -->
    @if (($allProductPengurangans ?? collect())->isNotEmpty())
        <div class="section-container" style="margin-top: 20px;">
            <h3 class="sub-section-title"
                style="font-size: 12px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                Rincian Item Pengurangan Produk</h3>
            <table class="bordered" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">No</th>
                        <th style="width: 70%;">Deskripsi Pengurangan</th>
                        <th style="width: 25%; text-align: right;">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($allProductPengurangans ?? collect()) as $index => $itemPengurangan)
                        <tr>
                            <td style="text-align: center;">{{ $index + 1 }}</td>
                            <td>
                                {{ $itemPengurangan->description ?? 'N/A' }}
                                @if ($itemPengurangan->notes)
                                    <div class="notes-content" style="font-size: 12px; margin-left: 15px; color: #030303;">
                                        {!! strip_tags($itemPengurangan->notes, '<li><strong><ul><li><br><span><div><p>') !!}
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: right; color: #dc3545; font-weight: bold;">- Rp
                                {{ number_format($itemPengurangan->amount ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Payment History -->
    @if (count($order->dataPembayaran) > 0)
        <div class="payment-history">
            <h3>Payment History</h3>
            <table class="bordered">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th>Date</th>
                        <th style="text-align: right;">Amount</th>
                        <th>Payment Method</th>
                        <th style="text-align: center;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->dataPembayaran as $payment)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($payment->tgl_bayar)->format('d F Y') }}</td>
                            <td class="text-right">Rp {{ number_format($payment->nominal, 0, ',', '.') }}</td>
                            <td>{{ $payment->paymentMethod->name ?? 'N/A' }}</td>
                            <td style="text-align: center;">{{ $payment->keterangan }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p style="margin-top: 20px; font-style: italic;">No payment history available.</p>
    @endif

    <!-- Footer -->
    <table class="footer" style="width: 100%;">
        <tr>
            <td style="width: 65%; vertical-align: top;">
                <div class="bold">Terms & Conditions</div>
                <ul>
                    <li>Silakan lakukan pembayaran melalui transfer bank ke rekening yang tertera. <br>{{ $paymentDetails ?? 'Please contact us for payment details.' }}</li>
                    <li>Pembayaran diharapkan lunas dalam waktu {{ config('invoice.payment_days', 7) }} hari dari tanggal invoice.</li>
                    <li>Mohon lakukan pembayaran sesuai nominal yang tertera.</li>
                    <li>Setelah melakukan pembayaran, silakan kirim bukti pembayaran ke email kami.</li>
                    <li>Dilarang melakukan transfer ke rekening selain yang tertera.</li>
                    <li>Jika ada pertanyaan, silakan hubungi layanan pelanggan kami.</li>
                </ul>
            </td>
            <td style="width: 35%; text-align: center; vertical-align: top;">
                <p style="margin-bottom: 10px;">Thank you for your business!</p>
            </td>
        </tr>
    </table>
</body>

</html>
