<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ strtoupper($companyName ?? config('app.name')) }} - Simulasi Paket Pernikahan - {{ $companyName ?? config('app.name') }}</title>
    <meta name="author" content="themeholy">
    <meta name="description" content="Invar - Invoice HTML Template">
    <meta name="keywords" content="Invar - Invoice HTML Template" />
    <meta name="robots" content="INDEX,FOLLOW">

    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    @if (!($pdfMode ?? false))
        <link rel="icon" href="{{ $companyFaviconUrl ?? asset('images/favicon_makna.png') }}">
        <link rel="apple-touch-icon" href="{{ $companyFaviconUrl ?? asset('images/favicon_makna.png') }}">
        <meta name="theme-color" content="#ffffff">
    @endif

    <!--==============================
 Google Fonts
 ============================== -->
    @if (!($pdfMode ?? false))
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800;900&display=swap"
            rel="stylesheet">
    @endif


    <!--==============================
 All CSS File
 ============================== -->
    @if (!($pdfMode ?? false))
        <link rel="stylesheet" href="{{ asset('assetssimulasi/css/bootstrap.min.css') }}">
        <link rel="stylesheet" href="{{ asset('assetssimulasi/css/style.css') }}">
    @else
        <style>{!! file_get_contents(public_path('assetssimulasi/css/bootstrap.min.css')) !!}</style>
        <style>{!! file_get_contents(public_path('assetssimulasi/css/style.css')) !!}</style>
    @endif
    @if (!($pdfMode ?? false))
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    @endif
    <style>
        body,
        * {
            font-family: 'Noto Sans', sans-serif !important;
        }

        /* Remove gray background */
        body {
            background-color: #ffffff !important;
            overflow-x: hidden !important;
        }

        .invoice-container-wrap {
            background-color: #ffffff !important;
            display: flex !important;
            justify-content: center !important;
            align-items: flex-start !important;
            min-height: 100vh !important;
            padding: 0 !important;
            overflow-x: hidden !important;
        }

        .invoice-container {
            background-color: #ffffff !important;
            box-shadow: none !important;
            border: 0.5px solid #ddd !important;
            box-sizing: border-box !important;
            margin: 15px auto !important;
            padding: 20px !important;
            width: 100% !important;
            max-width: 980px !important;
            min-height: 100vh !important;
        }

        th {
            text-transform: uppercase;
        }

        /* Styling for addition items */
        .addition-row {
            background-color: #f8f9fa;
        }

        .addition-amount {
            color: #28a745 !important;
            font-weight: 600 !important;
        }

        .reduction-amount {
            color: #dc3545 !important;
            font-weight: 600 !important;
        }

        /* Fix for excessive spacing in description lists/paragraphs */
        .invoice-table td p,
        .invoice-table td ul,
        .invoice-table td ol {
            margin-bottom: 2px !important;
            margin-top: 0px !important;
        }

        /* General list indentation */
        .invoice-table td ol {
            padding-left: 30px !important;
        }

        /* Extra indentation for bullet points (ul) to look like sub-items */
        .invoice-table td ul {
            padding-left: 45px !important;
        }

        .invoice-table td li {
            margin-bottom: 0px !important;
        }

        /* Force black color for text elements to ensure consistency */
        .invoice-table td p,
        .invoice-table td ul,
        .invoice-table td ol,
        .invoice-table td li,
        .invoice-table td span,
        .invoice-table td div,
        .invoice-table td strong,
        .invoice-table td b {
            color: #000000 !important;
        }

        /* Ensure list markers (dots/numbers) are also black */
        .invoice-table td li::marker {
            color: #000000 !important;
        }

        .themeholy-header address {
            max-width: 100%;
            white-space: normal !important;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* Print/PDF-specific rules */
        @media print {

            /* Header Repeating Configuration */
            thead {
                display: table-header-group !important;
            }

            tfoot {
                display: table-footer-group !important;
            }

            tr {
                page-break-inside: avoid !important;
            }

            /* Ensure header layout is visible on every page */
            header,
            .themeholy-header {
                position: relative;
                width: 100%;
                display: block;
            }

            body,
            * {
                font-family: 'Noto Sans', sans-serif !important;
                font-size: 10px !important;
                line-height: 1.3 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                /* margin-top: 2px !important;
                margin-bottom: 0 !important;
                margin-left: 0 !important;
                margin-right: 0 !important; */
            }

            body {
                overflow: visible !important;
            }

            .no-print,
            .invoice-buttons {
                display: none !important;
            }

            #pdf_preview_overlay {
                display: none !important;
            }

            /* Hide border and reset container in print */
            .invoice-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
            }

            .invoice-container-wrap {
                display: block !important;
                padding: 0 !important;
                margin: 0 !important;
                min-height: auto !important;
            }

            /* Hide vendor and public price columns for main table only, not for addition/reduction tables */
            .invoice-table:not(.addition-table):not(.reduction-table) .col-vendor-price,
            .invoice-table:not(.addition-table):not(.reduction-table) .col-public-price,
            .total-table .col-public-price {
                display: none !important;
            }

            /* Show publish price column for addition table */
            .addition-table .col-vendor-price {
                display: none !important;
            }

            .addition-table .col-public-price {
                display: table-cell !important;
            }

            /* Show amount column for reduction table */
            .reduction-table .col-public-price {
                display: table-cell !important;
            }

            .invoice-table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-bottom: 15px !important;
            }

            .invoice-table th,
            .invoice-table td {
                border: 1px solid #ddd !important;
                padding: 8px !important;
                text-align: left !important;
            }

            .invoice-table th {
                background-color: #f8f9fa !important;
                font-weight: bold !important;
                text-transform: uppercase !important;
            }

            .total-table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            .total-table th,
            .total-table td {
                border: 1px solid #ddd !important;
                padding: 6px 8px !important;
            }

            .addition-row {
                background-color: #f8f9fa !important;
            }

            .addition-amount {
                color: #28a745 !important;
                font-weight: 600 !important;
            }

            .reduction-amount {
                color: #dc3545 !important;
                font-weight: 600 !important;
            }

            .signature-area {
                page-break-inside: avoid !important;
                margin-top: 40px !important;
            }

            .address-box,
            .booking-info {
                margin-bottom: 10px !important;
            }

            @page {
                margin: 0.5in !important;
                size: A4 !important;
            }
        }

        .pdf-mode,
        .pdf-mode * {
            font-family: 'Noto Sans', sans-serif !important;
            font-size: 9px !important;
            line-height: 1.25 !important;
            color: #000 !important;
        }

        .pdf-mode .themeholy-header,
        .pdf-mode .themeholy-header * {
            font-size: 10px !important;
            line-height: 1.3 !important;
        }

        .pdf-mode thead {
            display: table-header-group !important;
        }

        .pdf-mode tfoot {
            display: table-footer-group !important;
        }

        .pdf-mode tr {
            page-break-inside: avoid !important;
        }

        .pdf-mode .no-print,
        .pdf-mode .invoice-buttons,
        .pdf-mode #pdf_preview_overlay {
            display: none !important;
        }

        .pdf-mode .invoice-container-wrap {
            display: block !important;
            min-height: auto !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: visible !important;
        }

        .pdf-mode .invoice-container {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            min-height: auto !important;
        }

        .pdf-mode .col-vendor-price {
            display: none !important;
        }

        .pdf-mode .items-table .col-public-price {
            display: none !important;
        }
    </style>

</head>

<body class="{{ ($pdfMode ?? false) ? 'pdf-mode' : '' }}">


    <!--[if lte IE 9]>
    <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
  <![endif]-->


    <!--********************************
   Code Start From Here
 ******************************** -->

    <div class="invoice-container-wrap">
        <div class="invoice-container">
            <main>
                <!--==============================
Invoice Area
==============================-->
                <div class="themeholy-invoice invoice_style2">
                    <div class="download-inner" id="download_section"
                        data-event-name="{{ $simulasi->prospect->name_event ?? '' }}"
                        data-pdf-url="{{ route('simulasi.pdf', $simulasi) }}">

                        <!-- Wrapper Table for Repeating Header -->
                        <table style="width: 100%; border: none; border-collapse: collapse; margin: 0; padding: 0;">
                            <thead>
                                <tr>
                                    <td style="border: none; padding: 0;">
                                        <!--==============================
                                         Header Area
                                         ==============================-->
                                        <header class="themeholy-header header-layout1">
                                            <div class="row align-items-center gx-0">
                                                <div class="col-12">
                                                    <div class="d-flex align-items-start justify-content-between"
                                                        style="border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 20px; gap: 12px;">

                                                        <div style="text-align: left; border: none !important; min-width: 0; flex: 1 1 auto;">
                                                            @php
                                                                $company = $company ?? \App\Models\Company::first();

                                                                if (
                                                                    $company &&
                                                                    $company->logo_url &&
                                                                    \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo_url)
                                                                ) {
                                                                    $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($company->logo_url);
                                                                } else {
                                                                    $logoPath = public_path('images/logomki.png');
                                                                }

                                                                $logoSrc = file_exists($logoPath)
                                                                    ? 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath))
                                                                    : '';
                                                            @endphp
                                                            <b>Office Information :</b>
                                                            <address style="white-space: normal; overflow-wrap: anywhere; word-break: break-word;">
                                                                {{ $company->company_name ?? ($companyName ?? config('app.name')) }}<br>
                                                                {{ $company->address ?? 'Jl. Sintraman Jaya I No. 2148, 20 Ilir D II, Kecamatan Kemuning, Kota Palembang, Sumatera Selatan 30137' }}
                                                                |
                                                                Phone: {{ $company->phone ?? '+62 822-9796-2600' }} <br>
                                                            </address>
                                                        </div>

                                                        <div class="header-logo"
                                                            style="max-height: 100px; text-align: right; flex: 0 0 auto; margin-left: auto;">
                                                            @if ($logoSrc)
                                                                <a href="{{ route('filament.admin.auth.login') }}"
                                                                    class="cta-button">
                                                                    <img src="{{ $logoSrc }}" alt="Logo Perusahaan"
                                                                        class="company-logo"
                                                                        style="display: block; max-height: 100px; width: 250px; margin-left: auto;">
                                                                </a>
                                                            @endif
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                            {{-- <div class="header-bottom">
                                                <div class="row align-items-center justify-content-between">
                                                    <div class="col-auto">
                                                        <div class="header-bottom_left">
                                                            <p><b>Event Name : </b>
                                                                {{ $simulasi->prospect->name_event ?? 'N/A' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <div class="header-bottom_right">
                                                            <p><b>Date :
                                                                </b>{{ $simulasi->created_at->format('d F Y H:i:s') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> --}}
                                        </header>
                                        <!-- Spacer after header in print -->
                                        <div style="height: 5px;"></div>
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="border: none; padding: 0;">
                                        <!-- Main Content Body -->
                                        <div class="row justify-content-between mb-4">
                                            <div class="col-auto">
                                                <div style="line-height: 1.2;">
                                                    <p style="margin-bottom: 2px;"><b>Event Name : </b>
                                                        {{ $simulasi->prospect->name_event ?? 'N/A' }}</p>
                                                    <p style="margin-bottom: 2px;"><b>Created By : </b>
                                                        {{ $simulasi->user->name ?? 'N/A' }}</p>
                                                    <p style="margin-bottom: 2px;"><b>Base Product : </b>
                                                        {{ $simulasi->product->name ?? 'N/A' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <div style="line-height: 1.2;">
                                                    <p style="margin-bottom: 2px;"><b>Date Akad : </b>
                                                        {{ $simulasi->prospect->date_akad ? $simulasi->prospect->date_akad->format('d F Y') : 'N/A' }}
                                                    </p>
                                                    <p style="margin-bottom: 2px;"><b>Date Resepsi : </b>
                                                        {{ $simulasi->prospect->date_resepsi ? $simulasi->prospect->date_resepsi->format('d F Y') : 'N/A' }}
                                                    </p>
                                                    <p style="margin-bottom: 2px;"><b>Valid Until : </b>
                                                        {{ $simulasi->created_at->addDays(4)->format('d F Y') }}
                                                    </p>
                                                    <p style="margin-bottom: 2px;"><b>Penawaran : </b>
                                                        00{{ $simulasi->id }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <table class="invoice-table items-table">
                                            <thead>
                                                <tr>
                                                    <th>Description</th>
                                                    <th class="col-vendor-price">Vendor</th>
                                                    <th class="col-public-price">Public</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($items as $index => $item)
                                                    <tr>
                                                        <td>
                                                            <div>
                                                                <strong>{{ $item->vendor->name ?? ($item->vendor_id ? 'Vendor ID: ' . $item->vendor_id : 'N/A') }}</strong>
                                                            </div>
                                                            @if ($item->description)
                                                                <div style="text-transform: capitalize;">
                                                                    {!! strtolower($item->description) !!}
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td class="col-vendor-price">
                                                            {{ number_format($item->harga_vendor ?? 0, 0, ',', '.') }}
                                                        </td>
                                                        <td class="col-public-price">
                                                            {{ number_format($item->harga_publish ?? 0, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td>
                                                            Tidak ada item spesifik yang terdaftar untuk produk ini.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>

                                        @php
                                            // Definisikan variabel untuk pengecekan agar lebih bersih
                                            $pengurangans = $simulasi->product->pengurangans ?? collect();
                                            $penambahanHarga = $simulasi->product->penambahanHarga ?? collect();
                                        @endphp

                                        {{-- Section Penambahan --}}
                                        @if ($penambahanHarga->isNotEmpty())
                                            <b>Detail Penambahan :</b>
                                            <table class="invoice-table addition-table">
                                                <thead>
                                                    <tr>
                                                        <th>Description</th>
                                                        <th class="col-vendor-price">Vendor</th>
                                                        <th class="col-public-price">Publish</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($penambahanHarga as $penambahan_item)
                                                        <tr>
                                                            <td>
                                                                <div>
                                                                    <strong>{{ $penambahan_item->vendor->name ?? 'Penambahan Tanpa Nama' }}</strong>
                                                                </div>
                                                                @if (!empty($penambahan_item->description))
                                                                    <div>
                                                                        {!! $penambahan_item->description !!}
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            <td class="col-vendor-price addition-amount">
                                                                {{ number_format($penambahan_item->harga_vendor ?? 0, 0, ',', '.') }}
                                                            </td>
                                                            <td class="col-public-price addition-amount">
                                                                {{ number_format($penambahan_item->harga_publish ?? 0, 0, ',', '.') }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif

                                        @if ($pengurangans->isNotEmpty())
                                            {{-- Bagian ini hanya akan ditampilkan jika ada item pengurangan --}}
                                            <b>Detail Pengurangan :</b>
                                            <table class="invoice-table reduction-table">
                                                <thead>
                                                    <tr>
                                                        <th>Description</th>
                                                        <th class="col-public-price">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($pengurangans as $pengurangan_item)
                                                        <tr>
                                                            <td>
                                                                <div>
                                                                    <strong>{{ $pengurangan_item->description ?? ($pengurangan_item->name ?? 'Pengurangan Tanpa Nama') }}</strong>
                                                                </div>
                                                                @if (!empty($pengurangan_item->notes))
                                                                    <div>{!! $pengurangan_item->notes !!}</div>
                                                                @endif
                                                            </td>
                                                            <td class="col-public-price">
                                                                {{ number_format($pengurangan_item->amount, 0, ',', '.') }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif

                                        @if (!empty($simulasi->notes) && trim(strip_tags($simulasi->notes)) !== '')
                                            <table class="invoice-table">
                                                <tbody>
                                                    <tr>
                                                        <td style="text-align: left;">
                                                            <b>Notes (Jika ada) :</b>
                                                            <p style="margin: 0;">{!! $simulasi->notes !!}</p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        @endif

                                        {{-- Price Calculation --}}
                                        @php
                                            // Hitung total publish price berdasarkan item-item dalam simulasi
                                            $totalPublicPrice = collect($items)->sum(function ($item) {
                                                return ($item->harga_publish ?? 0) * ($item->quantity ?? 1);
                                            });

                                            // Total biaya vendor berdasarkan item-item dalam simulasi
                                            $totalVendorPrice = collect($items)->sum(function ($item) {
                                                return ($item->harga_vendor ?? 0) * ($item->quantity ?? 1);
                                            });

                                            // Hitung total penambahan harga
                                            $totalAdditionPublish = (
                                                $simulasi->product->penambahanHarga ?? collect()
                                            )->sum('harga_publish');
                                            $totalAdditionVendor = (
                                                $simulasi->product->penambahanHarga ?? collect()
                                            )->sum('harga_vendor');

                                            // Harga dasar paket adalah total harga publik dari item dan harga vendor dari item
                                            $basePackagePrice = $totalPublicPrice;
                                            $baseVendorPrice = $totalVendorPrice;

                                            // Hitung total jumlah diskon
                                            $calculationTotalReductions = (
                                                $simulasi->product->pengurangans ?? collect()
                                            )->sum('amount');

                                            // Hitung total jumlah harga publish setelah pengurangan dan penambahan
                                            $finalPublicPriceAfterDiscounts =
                                                $basePackagePrice + $totalAdditionPublish - $calculationTotalReductions;
                                            $finalVendorPriceAfterDiscounts =
                                                $baseVendorPrice + $totalAdditionVendor - $calculationTotalReductions;

                                            // Profit & Loss for this simulation
                                            $calculationProfitLoss =
                                                $finalPublicPriceAfterDiscounts - $finalVendorPriceAfterDiscounts;
                                        @endphp {{-- Total Calculation Section was here, moved it down for clarity --}}
                                        <div class="col-12">
                                            <table class="invoice-table">
                                                <thead>
                                                    <tr>
                                                        <th>Description</th>
                                                        <th class="col-vendor-price">Vendor</th>
                                                        <th class="col-public-price">Publish</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><strong>Base Price</strong></td>
                                                        <td class="col-vendor-price">
                                                            {{ number_format($baseVendorPrice, 0, ',', '.') }}</td>
                                                        <td class="col-public-price"
                                                            style="display: table-cell !important;">
                                                            {{ number_format($basePackagePrice, 0, ',', '.') }}</td>
                                                    </tr>
                                                    @if ($totalAdditionPublish > 0 || $totalAdditionVendor > 0)
                                                        <tr>
                                                            <td style="color: #28a745; font-weight: 600;">
                                                                <strong>Additions</strong>
                                                            </td>
                                                            <td class="col-vendor-price"
                                                                style="color: #28a745; font-weight: 600;">
                                                                +
                                                                {{ number_format($totalAdditionVendor, 0, ',', '.') }}
                                                            </td>
                                                            <td class="col-public-price"
                                                                style="color: #28a745; font-weight: 600; display: table-cell !important;">
                                                                +
                                                                {{ number_format($totalAdditionPublish, 0, ',', '.') }}
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    @if ($calculationTotalReductions > 0)
                                                        <tr>
                                                            <td style="color: #dc3545; font-weight: 600;">
                                                                <strong>Reductions</strong>
                                                            </td>
                                                            <td class="col-vendor-price"
                                                                style="color: #dc3545; font-weight: 600;">
                                                                ({{ number_format($calculationTotalReductions, 0, ',', '.') }})
                                                            </td>
                                                            <td class="col-public-price"
                                                                style="color: #dc3545; font-weight: 600; display: table-cell !important;">
                                                                ({{ number_format($calculationTotalReductions, 0, ',', '.') }})
                                                            </td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <td><strong>TOTAL</strong></td>
                                                        <td class="col-vendor-price">
                                                            <strong>{{ number_format($finalVendorPriceAfterDiscounts, 0, ',', '.') }}</strong>
                                                        </td>
                                                        <td class="col-public-price"
                                                            style="display: table-cell !important;">
                                                            <strong>{{ number_format($finalPublicPriceAfterDiscounts, 0, ',', '.') }}</strong>
                                                        </td>
                                                    </tr>
                                                    <tr class="no-print">
                                                        <td colspan="2"
                                                            style="text-align: right; font-weight: bold;">PROFIT & LOSS
                                                        </td>
                                                        <td class="col-public-price"
                                                            style="{{ $calculationProfitLoss < 30000000 ? 'color: red; font-weight: bold;' : 'color: blue; font-weight: bold;' }}">
                                                            {{ number_format($calculationProfitLoss, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        {{-- Signature Section --}}
                                        <div class="signature-area"
                                            style="margin-top: 60px; width: 100%; overflow: auto; font-size: 12px;">
                                            <div style="float: left; width: 40%; text-align: center; margin-left: 5%;">
                                                <p style="margin-bottom: 70px;">Hormat Kami,</p>
                                                <p
                                                    style="border-top: 1px solid var(--title-color); margin: 0 10px; padding-top: 5px;">
                                                    ( {{ $simulasi->user->name ?? 'Account Manager' }} )
                                                </p>
                                                <p>{{ $company->company_name ?? ($companyName ?? config('app.name')) }}</p>
                                            </div>
                                            <div
                                                style="float: right; width: 40%; text-align: center; margin-right: 5%;">
                                                <p style="margin-bottom: 70px;">Disetujui Oleh,</p>
                                                <p
                                                    style="border-top: 1px solid var(--title-color); margin: 0 10px; padding-top: 5px;">
                                                    (_________________________)</p>
                                                <p>Klien</p>
                                            </div>
                                            <div style="clear: both;"></div>
                                        </div>
                                        {{-- <p class="company-address">
                            <b>Invar Inc:</b> <br>
                            12th Floor, Plot No.5, IFIC Bank, Gausin Rod, Suite 250-20, Franchisco USA 2022.
                        </p> --}}
                                        {{-- Moved the general note outside of the main content flow for PDF if it was part of the invoice-note --}}

                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @if (!($pdfMode ?? false))
                    <div class="invoice-buttons no-print">
                        <button id="download_btn" class="download_btn" type="button">
                            <svg width="25" height="19" viewBox="0 0 25 19" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M8.94531 11.1797C8.6849 10.8932 8.6849 10.6068 8.94531 10.3203C9.23177 10.0599 9.51823 10.0599 9.80469 10.3203L11.875 12.3516V6.375C11.901 5.98438 12.1094 5.77604 12.5 5.75C12.8906 5.77604 13.099 5.98438 13.125 6.375V12.3516L15.1953 10.3203C15.4818 10.0599 15.7682 10.0599 16.0547 10.3203C16.3151 10.6068 16.3151 10.8932 16.0547 11.1797L12.9297 14.3047C12.6432 14.5651 12.3568 14.5651 12.0703 14.3047L8.94531 11.1797ZM10.625 0.75C11.7969 0.75 12.8646 1.01042 13.8281 1.53125C14.8177 2.05208 15.625 2.76823 16.25 3.67969C16.8229 3.39323 17.4479 3.25 18.125 3.25C19.375 3.27604 20.4036 3.70573 21.2109 4.53906C22.0443 5.34635 22.474 6.375 22.5 7.625C22.5 8.01562 22.4479 8.41927 22.3438 8.83594C23.151 9.2526 23.7891 9.85156 24.2578 10.6328C24.7526 11.4141 25 12.2865 25 13.25C24.974 14.6562 24.4922 15.8411 23.5547 16.8047C22.5911 17.7422 21.4062 18.224 20 18.25H5.625C4.03646 18.1979 2.70833 17.651 1.64062 16.6094C0.598958 15.5417 0.0520833 14.2135 0 12.625C0.0260417 11.375 0.377604 10.2812 1.05469 9.34375C1.73177 8.40625 2.63021 7.72917 3.75 7.3125C3.88021 5.4375 4.58333 3.88802 5.85938 2.66406C7.13542 1.4401 8.72396 0.802083 10.625 0.75ZM10.625 2C9.08854 2.02604 7.78646 2.54688 6.71875 3.5625C5.67708 4.57812 5.10417 5.85417 5 7.39062C4.94792 7.91146 4.67448 8.27604 4.17969 8.48438C3.29427 8.79688 2.59115 9.33073 2.07031 10.0859C1.54948 10.8151 1.27604 11.6615 1.25 12.625C1.27604 13.875 1.70573 14.9036 2.53906 15.7109C3.34635 16.5443 4.375 16.974 5.625 17H20C21.0677 16.974 21.9531 16.6094 22.6562 15.9062C23.3594 15.2031 23.724 14.3177 23.75 13.25C23.75 12.5208 23.5677 11.8698 23.2031 11.2969C22.8385 10.724 22.3568 10.2682 21.7578 9.92969C21.2109 9.59115 21.0026 9.09635 21.1328 8.44531C21.2109 8.21094 21.25 7.9375 21.25 7.625C21.224 6.73958 20.9245 5.9974 20.3516 5.39844C19.7526 4.82552 19.0104 4.52604 18.125 4.5C17.6302 4.5 17.1875 4.60417 16.7969 4.8125C16.1719 5.04688 15.651 4.90365 15.2344 4.38281C14.7135 3.65365 14.0495 3.08073 13.2422 2.66406C12.4609 2.22135 11.5885 2 10.625 2Z"
                                    fill="#2D7CFE" />
                            </svg>
                        </button>
                    </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
    <!-- Invoice Conainter End -->

    <!--==============================
    All Js File
============================== -->
    @if (!($pdfMode ?? false))
        <!-- Jquery -->
        <script src="{{ asset('assetssimulasi/js/vendor/jquery-3.6.0.min.js') }}"></script>
        <!-- Bootstrap -->
        <script src="{{ asset('assetssimulasi/js/bootstrap.min.js') }}"></script>
        <!-- PDF Generator -->
        <script src="{{ asset('assetssimulasi/js/jspdf.min.js') }}"></script>
        <script src="{{ asset('assetssimulasi/js/html2canvas.min.js') }}"></script>
        <!-- Main Js File -->
        <script src="{{ asset('assetssimulasi/js/main.js') }}"></script>
    @endif

</body>

</html>
