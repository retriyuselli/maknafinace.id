<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Laporan Account Manager - {{ $accountManager->name ?? 'Unknown' }} - {{ $monthName ?? 'Unknown Month' }}
        {{ $year ?? 'Unknown Year' }}</title>
    <meta name="author" content="Makna Kreatif">
    <meta name="description" content="Laporan Kinerja Account Manager">
    <meta name="keywords" content="Account Manager, Report, Performance, Makna Kreatif" />
    <meta name="robots" content="INDEX,FOLLOW">

    <!-- Mobile Specific Metas -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Favicons - Makna Kreatif Favicon -->
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon_makna.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon_makna.png') }}" type="image/png">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ asset('images/favicon_makna.png') }}">
    <meta name="theme-color" content="#ffffff">

    <!--==============================
 Google Fonts
 ============================== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">


    <!--==============================
 All CSS File
 ============================== -->
    <!-- Bootstrap -->
    <link rel="stylesheet" href="{{ public_path('assets_am/css/bootstrap.min.css') }}">
    <!-- Theme Custom CSS -->
    <link rel="stylesheet" href="{{ public_path('assets_am/css/style.css') }}">

    <!-- Custom Noto Sans Font CSS -->
    <style>
        /* PDF Page Settings */
        @page {
            margin: 0px;
            /* Reset default margin */
            size: A4 portrait;
        }

        /* Override Body Background */
        body {
            background-color: #ffffff !important;
            margin: 0;
            padding: 20px;
            /* Add global padding to prevent content hitting edges */
        }

        .invoice-container {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            /* Remove internal padding as body handles it */
            background-color: #ffffff !important;
            box-shadow: none !important;
        }

        .themeholy-invoice {
            background-color: #ffffff !important;
            box-shadow: none !important;
            border: none !important;
        }

        /* Enforce table width */
        .invoice-table {
            table-layout: fixed !important;
            width: 100% !important;
        }

        /* Ensure table header widths are respected */
        .invoice-table th {
            box-sizing: border-box;
        }

        * {
            font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
        }

        body {
            font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-weight: 600;
        }

        .invoice-table th,
        .invoice-table td {
            font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .table-title {
            font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-weight: 600;
        }

        .company-address {
            font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .invoice-note {
            font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .motivational-section h4,
        .motivational-section p,
        .motivational-footer h4,
        .motivational-footer p {
            font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .invoice-left b,
        .invoice-right b {
            font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-weight: 600;
        }

        .total-table th,
        .total-table td,
        .total-table2 th,
        .total-table2 td {
            font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Print Styles for A4 */
        @media print {
            .signature-section .row {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
            }

            .signature-section .col-md-5 {
                width: 45% !important;
                flex: 0 0 45% !important;
                max-width: 45% !important;
            }

            /* Ensure other grid elements also behave nicely if needed */
            .row {
                display: flex !important;
                flex-wrap: nowrap !important;
            }

            .col-auto {
                width: auto !important;
            }

            /* Ensure header repeats on every page */
            thead {
                display: table-header-group !important;
            }

            tbody {
                display: table-row-group !important;
            }
        }

        /* Override big-title background */
        .themeholy-invoice .big-title {
            background-color: transparent !important;
        }

        /* Ensure buttons are visible on top */
        .invoice-buttons {
            z-index: 9999 !important;
            position: relative !important;
        }
    </style>
</head>

<body>


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
                <div class="themeholy-invoice invoice_style6">
                    <div class="download-inner" id="download_section">
                        <table style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0;">
                            <thead>
                                <tr>
                                    <th style="border: none; padding: 0; width: 100%;">
                                        <!--==============================
                                        Header Area
                                        ==============================-->
                                        <header class="themeholy-header header-layout4">
                                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                                <tr>
                                                    <td style="width: 50%; text-align: left; vertical-align: middle;">
                                                        <div class="header-logo">
                                                            <img src="{{ public_path('images/logomki.png') }}"
                                                                alt="Makna Kreatif" width="250"
                                                                style="max-width: 250px; height: auto;">
                                                        </div>
                                                    </td>
                                                    <td style="width: 50%; text-align: right; vertical-align: middle;">
                                                        <h1 class="big-title" style="margin: 0; font-size: 24px;">
                                                            Laporan Account Manager</h1>
                                                        <span style="display: block; margin-top: 5px;"><b>Periode: </b>
                                                            {{ $monthName ?? 'Unknown Month' }}
                                                            {{ $year ?? 'Unknown Year' }}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </header>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="border: none; padding: 0;">
                                        <div class="content-wrapper">
                                            <div class="row justify-content-between mb-4">
                                                <div class="col-auto">
                                                    <div class="invoice-left">
                                                        <b>Account Manager:</b>
                                                        <address>
                                                            <strong>{{ $accountManager->name ?? 'Unknown' }}</strong><br>
                                                            Email: {{ $accountManager->email ?? 'No email' }}<br>
                                                            @if ($reportData['target'] ?? null)
                                                                Target:
                                                                {{ number_format($reportData['target']->target_amount ?? 0, 0, ',', '.') }}<br>
                                                                Status:
                                                                {{ ucfirst($reportData['target']->status ?? 'pending') }}
                                                            @else
                                                                Target: Tidak ada target yang ditetapkan
                                                            @endif
                                                        </address>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="invoice-right">
                                                        <b>PT. Makna Kreatif Indonesia</b>
                                                        <address>
                                                            Jl. Sintraman Jaya I No. 2148 <br>
                                                            20 Ilir D II, Kec. Kemuning, Kota Palembang<br>
                                                            Sumatera Selatan 30137<br>
                                                            Email: info@maknawedding.id<br>
                                                            Tlp: +62 813 7318 3794
                                                        </address>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr class="style1">

                                            <!-- Target vs Achievement Section -->
                                            <div class="target-achievement-section"
                                                style="background: #F5F5F5; border-radius: 15px; padding: 25px; margin: 20px 0;">
                                                <h4 style="color: #333; font-weight: bold; margin-bottom: 20px;">Target
                                                    vs
                                                    Achievement</h4>

                                                <div
                                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                                    <div>
                                                        <span style="color: #666; font-size: 1rem;">Target: </span>
                                                        <span
                                                            style="color: #4F7FFF; font-weight: bold; font-size: 1.2rem;">
                                                            {{ number_format($reportData['target']->target_amount ?? 0, 0, '.', '.') }}</span>
                                                    </div>
                                                    <div style="text-align: right;">
                                                        <span style="color: #666; font-size: 1rem;">Achievement:
                                                        </span>
                                                        <span
                                                            style="color: #4F7FFF; font-weight: bold; font-size: 1.2rem;">
                                                            {{ number_format($reportData['totalRevenue'] ?? 0, 0, '.', '.') }}</span>
                                                    </div>
                                                </div>

                                                <!-- Progress Bar -->
                                                <div class="progress-bar-container"
                                                    style="background: #E0E0E0; border-radius: 10px; height: 20px; overflow: hidden; margin: 15px 0;">
                                                    <div
                                                        style="background: linear-gradient(90deg, #4F7FFF 0%, #6B9BFF 100%); height: 100%; width: {{ min($reportData['achievementPercentage'] ?? 0, 100) }}%; border-radius: 10px; transition: width 0.5s ease;">
                                                    </div>
                                                </div>
                                            </div>

                                            <p class="table-title"><b>Ringkasan Kinerja:</b></p>
                                            <table class="invoice-table table-style1 mt-2">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 15% !important;">Metrik</th>
                                                        <th style="width: 20% !important;">Target</th>
                                                        <th style="width: 20% !important;">Realisasi</th>
                                                        <th style="width: 10% !important;">%</th>
                                                        <th style="width: 35% !important;">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><strong>Revenue</strong></td>
                                                        <td> {{ number_format($reportData['target']->target_amount ?? 0, 0, ',', '.') }}
                                                        </td>
                                                        <td> {{ number_format($reportData['totalRevenue'] ?? 0, 0, ',', '.') }}
                                                        </td>
                                                        <td> {{ number_format($reportData['achievementPercentage'] ?? 0, 1) }}%
                                                        </td>
                                                        <td>
                                                            @if (($reportData['achievementPercentage'] ?? 0) >= 100)
                                                                <span style="color: green;">✓ Tercapai</span>
                                                            @elseif(($reportData['achievementPercentage'] ?? 0) >= 75)
                                                                <span style="color: orange;">⚠ Hampir Tercapai</span>
                                                            @else
                                                                <span style="color: red;">✗ Belum Tercapai</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Jumlah Order</strong></td>
                                                        <td>-</td>
                                                        <td>{{ $reportData['totalOrders'] ?? 0 }} orders</td>
                                                        <td>-</td>
                                                        <td>
                                                            @if (($reportData['totalOrders'] ?? 0) > 0)
                                                                <span style="color: green;">✓ Ada Aktivitas</span>
                                                            @else
                                                                <span style="color: red;">✗ Tidak Ada Order</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Rata-rata Order</strong></td>
                                                        <td>-</td>
                                                        <td> {{ number_format($reportData['averageOrderValue'] ?? 0, 0, ',', '.') }}
                                                        </td>
                                                        <td>-</td>
                                                        <td>
                                                            @if (($reportData['averageOrderValue'] ?? 0) > 800000000)
                                                                <span style="color: green;">✓ Tinggi</span>
                                                            @elseif(($reportData['averageOrderValue'] ?? 0) > 500000000)
                                                                <span style="color: orange;">⚠ Sedang</span>
                                                            @else
                                                                <span style="color: red;">✗ Rendah</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            @if (!empty($reportData['orders']) && count($reportData['orders']) > 0)
                                                <p class="table-title"><b>Detail Project:</b></p>
                                                <table class="invoice-table table-style1 mt-2">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 20% !important;">Client</th>
                                                            <th style="width: 38% !important;">Package</th>
                                                            <th style="width: 12% !important; padding-right: 15px;">
                                                                Tanggal</th>
                                                            <th style="width: 15% !important; padding-left: 15px;">
                                                                Grand Total</th>
                                                            <th style="width: 15% !important;">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($reportData['orders'] as $index => $order)
                                                            <tr>
                                                                <td>{{ $order->prospect->name_event ?? 'N/A' }}</td>
                                                                <td>
                                                                    @if (isset($order->package_name) && !empty($order->package_name))
                                                                        {{ $order->package_name }}
                                                                    @elseif(isset($order->items) && $order->items->isNotEmpty())
                                                                        @php
                                                                            $firstItem = $order->items->first();
                                                                        @endphp
                                                                        {{ $firstItem->product->name ?? 'Custom Package' }}
                                                                        @if ($order->items->count() > 1)
                                                                            <small>(+{{ $order->items->count() - 1 }}
                                                                                more)</small>
                                                                        @endif
                                                                    @else
                                                                        Custom Package
                                                                    @endif
                                                                </td>
                                                                <td style="padding-right: 15px;">
                                                                    {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}
                                                                </td>
                                                                <td style="text-align: right; padding-left: 15px;">
                                                                    {{ number_format($order->grand_total ?? 0, 0, ',', '.') }}
                                                                </td>
                                                                <td>
                                                                    @if ($order->status == 'confirmed' || (is_object($order->status) && $order->status->value == 'confirmed'))
                                                                        <span style="color: green;">✓ Confirmed</span>
                                                                    @elseif($order->status == 'pending' || (is_object($order->status) && $order->status->value == 'pending'))
                                                                        <span style="color: orange;">⏳ Pending</span>
                                                                    @else
                                                                        <span
                                                                            style="color: blue;">{{ is_object($order->status) ? ucfirst($order->status->value) : ucfirst($order->status) }}</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div class="no-orders-section"
                                                    style="text-align: center; padding: 40px; background-color: #f8f9fa; border-radius: 8px; margin: 20px 0;">
                                                    <h4 style="color: #6c757d;">Belum Ada Order</h4>
                                                    <p style="color: #6c757d; margin: 0;">Tidak ada order pada periode
                                                        ini.
                                                        Semangat untuk
                                                        mendapatkan order pertama!</p>
                                                </div>
                                            @endif

                                            <!-- Detail Tahun Berjalan Section -->
                                            <p class="table-title"><b>Detail Tahun Berjalan ({{ $currentYear }}):</b>
                                            </p>
                                            <table class="invoice-table table-style1 mt-2">
                                                <thead>
                                                    <tr>
                                                        <th>Bulan</th>
                                                        <th>Total Order</th>
                                                        <th>Revenue</th>
                                                        <th>Target Bulanan</th>
                                                        <th>Achievement</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($currentYearData['monthly'] as $month => $data)
                                                        @if ($month <= date('n'))
                                                            <tr>
                                                                <td>
                                                                    <strong>{{ $data['name'] }}</strong>
                                                                    @if ($month == date('n'))
                                                                        <small style="color: #4F7FFF;"> (Bulan
                                                                            Ini)</small>
                                                                    @endif
                                                                </td>
                                                                <td>{{ number_format($data['orders']) }} orders</td>
                                                                <td> {{ number_format($data['revenue'], 0, ',', '.') }}
                                                                </td>
                                                                <td> {{ number_format($data['target'], 0, ',', '.') }}
                                                                </td>
                                                                <td>{{ number_format($data['achievement'], 1) }}%</td>
                                                                <td>
                                                                    @if ($data['achievement'] >= 100)
                                                                        <span style="color: green;">✓ Tercapai</span>
                                                                    @elseif($data['achievement'] >= 75)
                                                                        <span style="color: orange;">⚠ Hampir
                                                                            Tercapai</span>
                                                                    @else
                                                                        <span style="color: red;">✗ Belum
                                                                            Tercapai</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach

                                                    <!-- Summary Row -->
                                                    <tr
                                                        style="background-color: #ffffff; color: rgb(0, 0, 0); font-weight: bold;">
                                                        <td><strong>TOTAL TAHUN {{ $currentYear }}</strong></td>
                                                        <td><strong>{{ number_format($currentYearData['summary']['orders']) }}
                                                                orders</strong>
                                                        </td>
                                                        <td><strong>
                                                                {{ number_format($currentYearData['summary']['revenue'], 0, ',', '.') }}</strong>
                                                        </td>
                                                        <td><strong>
                                                                {{ number_format($currentYearData['summary']['target'], 0, ',', '.') }}</strong>
                                                        </td>
                                                        <td><strong>{{ number_format($currentYearData['summary']['achievement'], 1) }}%</strong>
                                                        </td>
                                                        <td>
                                                            @if ($currentYearData['summary']['achievement'] >= 100)
                                                                <span style="color: #90EE90;">✓ TERCAPAI</span>
                                                            @elseif($currentYearData['summary']['achievement'] >= 75)
                                                                <span style="color: #FFD700;">⚠ HAMPIR TERCAPAI</span>
                                                            @else
                                                                <span style="color: #c40623;">✗ BELUM TERCAPAI</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <!-- Detail Tahun Sebelumnya Section -->
                                            <p class="table-title"><b>Detail Tahun Sebelumnya
                                                    ({{ $currentYear - 1 }}):</b>
                                            </p>
                                            <table class="invoice-table table-style1 mt-2">
                                                <thead>
                                                    <tr>
                                                        <th>Bulan</th>
                                                        <th>Total Order</th>
                                                        <th>Revenue</th>
                                                        <th>Target Bulanan</th>
                                                        <th>Achievement</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($previousYearData['monthly'] as $month => $data)
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $data['name'] }}</strong>
                                                            </td>
                                                            <td>{{ number_format($data['orders']) }} orders</td>
                                                            <td> {{ number_format($data['revenue'], 0, ',', '.') }}
                                                            </td>
                                                            <td> {{ number_format($data['target'], 0, ',', '.') }}
                                                            </td>
                                                            <td>{{ number_format($data['achievement'], 1) }}%</td>
                                                            <td>
                                                                @if ($data['achievement'] >= 100)
                                                                    <span style="color: green;">✓ Tercapai</span>
                                                                @elseif($data['achievement'] >= 75)
                                                                    <span style="color: orange;">⚠ Hampir
                                                                        Tercapai</span>
                                                                @else
                                                                    <span style="color: red;">✗ Belum
                                                                        Tercapai</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach

                                                    <!-- Summary Row -->
                                                    <tr
                                                        style="background-color: #ffffff; color: rgb(0, 0, 0); font-weight: bold;">
                                                        <td><strong>TOTAL TAHUN {{ $currentYear - 1 }}</strong></td>
                                                        <td><strong>{{ number_format($previousYearData['summary']['orders']) }}
                                                                orders</strong>
                                                        </td>
                                                        <td><strong>
                                                                {{ number_format($previousYearData['summary']['revenue'], 0, ',', '.') }}</strong>
                                                        </td>
                                                        <td><strong>
                                                                {{ number_format($previousYearData['summary']['target'], 0, ',', '.') }}</strong>
                                                        </td>
                                                        <td><strong>{{ number_format($previousYearData['summary']['achievement'], 1) }}%</strong>
                                                        </td>
                                                        <td>
                                                            @if ($previousYearData['summary']['achievement'] >= 100)
                                                                <span style="color: #90EE90;">✓ TERCAPAI</span>
                                                            @elseif($previousYearData['summary']['achievement'] >= 75)
                                                                <span style="color: #FFD700;">⚠ HAMPIR TERCAPAI</span>
                                                            @else
                                                                <span style="color: #c40623;">✗ BELUM TERCAPAI</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>

                                            <!-- Yearly Performance Insight -->
                                            <div class="yearly-performance-insight decorative-gradient"
                                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; padding: 20px; color: white; text-align: center; margin: 20px 0; page-break-inside: avoid; break-inside: avoid;">
                                                @php
                                                    $yearlyAchievement =
                                                        $currentYearData['summary']['achievement'] ?? 0;
                                                    $totalYearlyRevenue = $currentYearData['summary']['revenue'] ?? 0;
                                                    $fixedTargetAmount = 1000000000;
                                                    $avgMonthlyAchievement =
                                                        $currentMonth > 0 ? $yearlyAchievement / $currentMonth : 0;
                                                    $projectedYearlyRevenue =
                                                        $avgMonthlyAchievement > 0
                                                            ? ($totalYearlyRevenue / $currentMonth) * 12
                                                            : 0;
                                                @endphp
                                                <h4 style="color: white; margin-bottom: 10px;">� Proyeksi Tahun
                                                    {{ date('Y') }}</h4>
                                                <p style="color: white; margin: 0;">
                                                    Berdasarkan performa {{ $currentMonth }} bulan terakhir, proyeksi
                                                    revenue akhir tahun:
                                                    <strong>
                                                        {{ number_format($projectedYearlyRevenue, 0, ',', '.') }}</strong>
                                                </p>
                                                <p style="color: white; margin: 5px 0 0 0; font-size: 0.9em;">
                                                    @if ($projectedYearlyRevenue >= $fixedTargetAmount * 12)
                                                        🎯 Proyeksi menunjukkan target tahunan akan tercapai!
                                                    @else
                                                        💪 Butuh akselerasi untuk mencapai target tahunan!
                                                    @endif
                                                </p>
                                            </div>

                                            <table class="invoice-table table-style1 mt-4">
                                                <thead>
                                                    <tr>
                                                        {{-- <td><b>Periode: </b> {{ \Carbon\Carbon::parse($reportData['target']->start_date ?? now())->format('F Y') }}</td> --}}
                                                        <td><b>Generated: </b> {{ now()->format('d/m/Y H:i') }}</td>
                                                        <td><b>System: </b> Makna Kreatif CRM</td>
                                                    </tr>
                                                </thead>
                                                {{-- <tbody>
                                <tr>
                                    <td colspan="3"><b>Catatan: </b> Laporan ini menampilkan kinerja Account Manager berdasarkan target yang ditetapkan untuk periode yang sedang berjalan.</td>
                                </tr>
                            </tbody> --}}
                                            </table>
                                            {{-- <td><b>Child</b></td>
                                    <td>0</td>
                                </tr>
                            </tbody> --}}
                        </table>
                        <table style="width: 100%; margin-top: 20px;">
                            <tr>
                                <td style="vertical-align: top; width: 55%;">
                                    <div class="invoice-left tips-section">
                                        <b>Tips Sukses Account Manager</b>
                                        <p class="mb-0">1. Follow up dengan client secara berkala <br>
                                            2. Berikan solusi yang sesuai kebutuhan <br>
                                            3. Jaga hubungan baik dengan semua vendor <br>
                                            4. Pahami produk dan layanan <br>
                                            5. Dengarkan kebutuhan dan keluhan client <br>
                                            6. Kelola waktu dan prioritas dengan baik <br>
                                            7. Bangun komunikasi yang jelas dan transparan <br>
                                            8. Selalu update tren industri dan kompetitor <br>
                                            9. Buat laporan progress yang terukur <br>
                                            10. Jaga profesionalisme dan integritas
                                        </p>
                                    </div>
                                </td>
                                <td style="vertical-align: top; width: 45%; padding-left: 20px;">
                                    <table class="total-table" style="width: 100%;">
                                        <tr>
                                            <th>Target Bulanan:</th>
                                            <td> {{ number_format($reportData['target']->target_amount ?? 0, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Terealisasi:</th>
                                            <td> {{ number_format($reportData['totalRevenue'] ?? 0, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Persentase:</th>
                                            <td>{{ number_format($reportData['achievementPercentage'] ?? 0, 1) }}%</td>
                                        </tr>
                                        <tr style="background-color: #f8f9fa;">
                                            <th>Status Target:</th>
                                            <td>
                                                @if (($reportData['achievementPercentage'] ?? 0) >= 100)
                                                    <span style="color: green; font-weight: bold;">✓ TERCAPAI</span>
                                                @elseif(($reportData['achievementPercentage'] ?? 0) >= 75)
                                                    <span style="color: orange; font-weight: bold;">⚠ HAMPIR
                                                        TERCAPAI</span>
                                                @else
                                                    <span style="color: red; font-weight: bold;">✗ BELUM
                                                        TERCAPAI</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <div class="motivational-footer"
                            style="background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%); border-radius: 10px; padding: 20px; color: white; text-align: center; margin: 20px 0;">
                            @if (($reportData['achievementPercentage'] ?? 0) >= 100)
                                <h4 style="color: white; margin-bottom: 10px;">🏆 EXCELLENT PERFORMANCE!</h4>
                                <p style="color: white; margin: 0;">Target tercapai dengan sempurna! Anda adalah
                                    inspirasi untuk tim lainnya. Pertahankan prestasi gemilang ini!</p>
                            @elseif(($reportData['achievementPercentage'] ?? 0) >= 75)
                                <h4 style="color: white; margin-bottom: 10px;">🎯 KEEP PUSHING!</h4>
                                <p style="color: white; margin: 0;">Hanya tinggal sedikit lagi untuk mencapai target.
                                    Konsistensi adalah kunci kesuksesan!</p>
                            @else
                                <h4 style="color: white; margin-bottom: 10px;">💪 NEVER GIVE UP!</h4>
                                <p style="color: white; margin: 0;">Setiap tantangan adalah kesempatan untuk
                                    berkembang. Tetap semangat dan pantang menyerah!</p>
                            @endif
                        </div>

                        <!-- Employee Information Section -->

                        <!-- Payroll Information -->

                        <!-- Leave Balance Information -->

                        <!-- Additional Benefits Info -->

                        <!-- Signature Section -->
                        <div class="signature-section"
                            style="margin: 40px 0; padding: 30px 0; border-top: 2px solid #eee;">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="width: 45%; text-align: center; vertical-align: top;">
                                        <p style="margin-bottom: 5px; font-weight: 600; color: #333;">Account Manager
                                        </p>
                                        <div
                                            style="height: 80px; border-bottom: 1px solid #ccc; margin: 20px 0; position: relative;">
                                            <!-- Space for manual signature -->
                                            <div
                                                style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); font-size: 12px; color: #888;">
                                                (Tanda Tangan)
                                            </div>
                                        </div>
                                        <p style="margin: 10px 0 5px 0; font-weight: 600; color: #333;">
                                            {{ $accountManager->name ?? 'Unknown' }}</p>
                                        <p style="margin: 0; font-size: 12px; color: #666;">
                                            Tanggal: {{ now()->format('d/m/Y') }}
                                        </p>
                                    </td>
                                    <td style="width: 10%;"></td>
                                    <td style="width: 45%; text-align: center; vertical-align: top;">
                                        <p style="margin-bottom: 5px; font-weight: 600; color: #333;">Direktur</p>
                                        <div
                                            style="height: 80px; border-bottom: 1px solid #ccc; margin: 20px 0; position: relative;">
                                            <!-- Space for manual signature -->
                                            <div
                                                style="position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); font-size: 12px; color: #888;">
                                                (Tanda Tangan)
                                            </div>
                                        </div>
                                        <p style="margin: 10px 0 5px 0; font-weight: 600; color: #333;">Rama Dhona
                                            Utama
                                        </p>
                                        <p style="margin: 0; font-size: 12px; color: #666;">
                                            PT. Makna Kreatif Indonesia
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <p class="invoice-note mt-3">
                            <svg width="14" height="18" viewBox="0 0 14 18" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M3.64581 13.7917H10.3541V12.5417H3.64581V13.7917ZM3.64581 10.25H10.3541V9.00002H3.64581V10.25ZM1.58331 17.3334C1.24998 17.3334 0.958313 17.2084 0.708313 16.9584C0.458313 16.7084 0.333313 16.4167 0.333313 16.0834V1.91669C0.333313 1.58335 0.458313 1.29169 0.708313 1.04169C0.958313 0.791687 1.24998 0.666687 1.58331 0.666687H9.10415L13.6666 5.22919V16.0834C13.6666 16.4167 13.5416 16.7084 13.2916 16.9584C13.0416 17.2084 12.75 17.3334 12.4166 17.3334H1.58331ZM8.47915 5.79169V1.91669H1.58331V16.0834H12.4166V5.79169H8.47915ZM1.58331 1.91669V5.79169V1.91669V16.0834V1.91669Z"
                                    fill="#2D7CFE" />
                            </svg>

                            <b>CATATAN: </b>Laporan ini telah diverifikasi oleh Account Manager dan disetujui oleh
                            Direktur PT. Makna Kreatif Indonesia sebagai dokumen resmi evaluasi kinerja periode
                            {{ $monthName ?? 'Unknown Month' }} {{ $year ?? 'Unknown Year' }}.
                        </p>
                    </div>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                </div>
        </div>
        </main>
    </div>
    </div>
    <!-- Invoice Conainter End -->

</body>

</html>
