<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details PDF: {{ $product->name }}</title>
    <style>
        /* DomPDF: jangan load @font-face Poppins (TTF besar) — bisa >2 menit per PDF.
           Pakai DejaVu Sans bawaan DomPDF (cepat + mendukung karakter umum). */
        *,
        *::before,
        *::after {
            font-family: DejaVu Sans, sans-serif !important;
        }

        @page {
            /* margin: 2cm; */
            margin-top: 4cm;
            /* Perbesar margin atas untuk header */
            margin-bottom: 1cm;
            margin-left: 1cm;
            margin-right: 1cm;
            /* Margin atas dan bawah bisa disesuaikan lebih lanjut jika header/footer membutuhkan ruang spesifik */
            /* Contoh: margin-top: 1.5cm; margin-bottom: 1.5cm; */
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            /* Ukuran font standar untuk PDF */
            background-color: #ffffff;
            margin: 0;
            /* Body margin is 0, page margins are handled by @page */
            padding: 0;
            line-height: 1;
            /* Sedikit lebih longgar dari 1 untuk keterbacaan dan potensi kalkulasi break yang lebih baik */
            color: #333;
        }

        .pdf-container {
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            padding: 0;
            /* Padding utama diatur oleh @page margin */
        }

        .header {
            position: fixed;
            top: -3cm;
            left: 0;
            right: 0;
            margin-bottom: 0px;
            padding-bottom: 15px;
            border-bottom: 1px solid #000000;
        }

        .header-table {
            border-collapse: collapse;
            width: 100%;
        }

        .header-table td {
            padding: 0;
        }

        .header-left {
            font-size: 8pt;
            line-height: 1;
            text-align: left;
            vertical-align: middle;
            width: 68%;
            padding-right: 24px;
        }

        .header-right {
            text-align: right;
            vertical-align: middle;
            width: 32%;
            padding-left: 16px;
        }

        .header img {
            max-height: 36px;
            max-width: 110px;
            width: auto;
            height: auto;
            margin-top: 0;
        }

        .header h1 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header p {
            margin: 2px 0;
            font-size: 8pt;
            color: #555;
        }

        .details-table {
            /* Tabel info dokumen */
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
            font-size: 8pt;
            /* Ukuran font lebih kecil untuk tabel */
        }

        .vendor-description {
            margin-left: 10px;
            font-size: 8pt;
            margin-top: 3px;
            margin-bottom: 3px;
            padding-left: 0;
            list-style: none;
        }

        .vendor-description li {
            list-style: none;
            margin: 0;
            padding-left: 16px;
            position: relative;
        }

        .vendor-description li:before {
            content: '-';
            left: 0;
            position: absolute;
            top: 0;
        }

        .items-table,
        .total-table {
            /* Tabel komponen, kalkulasi harga */
            width: 100%;
            margin-top: 10px;
            /* Margin atas dari judul section atau elemen sebelumnya */
            border-collapse: collapse;
            font-size: 8pt;
        }

        .details-table tr,
        .items-table tr,
        .total-table tr {
            page-break-inside: auto;
            /* Izinkan baris tabel terpotong jika perlu untuk mengisi halaman */
        }

        .details-table td,
        .items-table td,
        .items-table th,
        .total-table td {
            padding: 6px 8px;
            /* Padding lebih kecil */
            border: 1px solid #ddd;
            /* vertical-align: top; Jaga konsistensi alignment */
        }

        .items-table th {
            background: #f8f8f8;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            page-break-inside: auto;
            /* Izinkan header terpotong jika perlu */
        }

        .items-table thead,
        .details-table thead,
        .total-table thead {
            display: table-header-group;
            /* Agar header tabel berulang jika tabel multi-halaman */
        }

        .items-table .text-right,
        .total-table .text-right {
            text-align: right;
            text-transform: capitalize;
            font-size: 8pt;
        }

        .description-html-content {
            font-size: 8pt;
            color: #555;
            margin-top: 3px;
            line-height: 1.35;
            padding-left: 0;
            margin-bottom: 3px;
        }

        .description-html-content br {
            line-height: 1.35;
        }

        .total-table td {
            text-align: right;
        }

        .total-table td:first-child {
            text-align: right;
            font-weight: bold;
            width: 80%;
        }

        .package-details-box {
            margin-top: 20px;
            border: 1px solid #eee;
            padding: 15px;
            /* Padding sedikit lebih besar */
            background: #fdfdfd;
            page-break-before: auto;
            /* Izinkan box ini terpotong */
        }

        h3.section-title {
            /* Kelas untuk judul bagian */
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 9pt;
            font-weight: bold;
            color: #333;
            page-break-after: auto;
            /* Izinkan page break setelah judul section */
        }

        .signature-table {
            width: 100%;
            margin-top: 30px;
            page-break-inside: auto;
            /* Izinkan tabel tanda tangan terpotong */
            font-size: 9pt;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin: 0 20px;
            /* Margin kiri kanan untuk garis */
        }

        .footer {
            text-align: center;
            margin-top: 0px;
            /* Jarak dari konten terakhir */
            padding-top: 0px;
            font-size: 6pt;
            color: #777;
            position: fixed;
            bottom: 0.5cm;
            /* Jarak dari bawah halaman */
            left: 1cm;
            right: 1cm;
            /* width: auto; atau biarkan browser menghitung berdasarkan left/right */
        }

        strong {
            font-weight: bold;
        }

        /* Pastikan bold bekerja */
    </style>
</head>

<body>
    {{-- Header Section — branding dari model Company (+ logoSrc dari controller) --}}
    <div class="header">
        @php
            $displayCompanyName = $company?->company_name ?? ($companyName ?? config('app.name'));
            $addressParts = array_filter([
                $company?->address,
                $company?->city,
                $company?->province,
                $company?->postal_code,
            ]);
            $displayAddress = ! empty($addressParts)
                ? implode(', ', $addressParts)
                : 'Jl. Sintraman Jaya I No. 2148, 20 Ilir D II, Kecamatan Kemuning, Kota Palembang, Sumatera Selatan 30137';
            $displayPhone = $company?->phone ?? '+62 822-9796-2600';
            $displayEmail = $company?->email ?? 'maknawedding@gmail.com';
        @endphp
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <strong>{{ $displayCompanyName }}</strong><br>
                    {{ $displayAddress }}<br>
                    {{ $displayPhone }} | {{ $displayEmail }}
                </td>
                <td class="header-right">
                    @if (! empty($logoSrc))
                        <img src="{{ $logoSrc }}" alt="{{ $displayCompanyName }}">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="pdf-container">

        {{-- Simulation Information --}}
        <table class="details-table">
            <tr>
                <td style="width: 50%;">
                    <strong>Wedding Package Product</strong><br>
                    Product Name : {{ $product->name }}<br>
                    Category : {{ $product->category->name ?? 'N/A' }}<br>
                    Capacity : {{ $product->pax }} Pax
                </td>
                <td style="width: 50%;">
                    <strong>Document Details</strong><br>
                    Reference : PROD-{{ str_pad($product->id, 6, '0', STR_PAD_LEFT) }}<br>
                    Date : {{ now()->format('d F Y H:i:s') }}<br>
                    Printed By : <strong>{{ auth()->user()->name ?? 'System' }}</strong>
                </td>
            </tr>
        </table>

        {{-- Package Details — angka mengikuti preview (harga × quantity) --}}
        <div class="package-details-box">
            <h3 class="section-title">Package Components & Services</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th>Description</th>
                        <th style="width: 15%; text-align: right;">Vendor</th>
                        <th style="width: 15%; text-align: right;">Public</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->items ?? [] as $item)
                        @php
                            $vendorLineTotal = $item->total_price
                                ?? $item->calculate_price_vendor
                                ?? (($item->harga_vendor ?? 0) * max(1, (int) ($item->quantity ?? 1)));
                            $publicLineTotal = $item->price_public
                                ?? $item->calculate_price_public
                                ?? (($item->harga_publish ?? 0) * max(1, (int) ($item->quantity ?? 1)));
                            $detailsNotes = \App\Support\ProductNotesFormatter::forPdf($item->description);
                        @endphp
                        <tr>
                            <td style="text-align: center; vertical-align: top;">{{ $loop->iteration }}</td>
                            <td>
                                <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 2px;">
                                    {{ $item->vendor->name ?? 'Vendor Tidak Diketahui' }}
                                </div>
                                @if ($detailsNotes !== '')
                                    <div class="description-html-content" style="margin-left: 8px;">
                                        {!! $detailsNotes !!}
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: right; vertical-align: top;">
                                {{ number_format((int) $vendorLineTotal, 0, ',', '.') }}
                            </td>
                            <td style="text-align: right; vertical-align: top;">
                                {{ number_format((int) $publicLineTotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 10px;">Tidak ada item spesifik yang
                                terdaftar untuk produk ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Addition Details --}}
        @if ($product->penambahanHarga && $product->penambahanHarga->count() > 0)
            <div class="package-details-box">
                <h3 class="section-title">Penambahan</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 5%; vertical-align: top;">No</th>
                            <th style="vertical-align: top;">Description</th>
                            <th style="width: 15%; text-align: right; vertical-align: top;">Vendor</th>
                            <th style="width: 15%; text-align: right; vertical-align: top;">Public</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product->penambahanHarga as $addition)
                            @php
                                $detailsNotes = \App\Support\ProductNotesFormatter::forPdf($addition->description);
                            @endphp
                            <tr>
                                <td style="text-align: center; vertical-align: top;">{{ $loop->iteration }}</td>
                                <td>
                                    <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 2px;">
                                        {{ $addition->vendor->name ?? 'N/A' }}
                                    </div>
                                    @if ($detailsNotes !== '')
                                        <div class="description-html-content" style="margin-left: 8px;">
                                            {!! $detailsNotes !!}
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align: right; vertical-align: top;">
                                    {{ number_format($addition->harga_vendor ?? 0, 0, ',', '.') }}
                                </td>
                                <td style="text-align: right; vertical-align: top;">
                                    {{ number_format($addition->harga_publish ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Reduction Details --}}
        @if (($product->pengurangans ?? collect())->count() > 0)
            <div class="package-details-box">
                <h3 class="section-title">Pengurangan</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 5%; vertical-align: top;">No</th>
                            <th style="vertical-align: top;">Description</th>
                            <th style="width: 15%; text-align: right; vertical-align: top;">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product->pengurangans as $discount)
                            @php
                                $detailsNotes = \App\Support\ProductNotesFormatter::forPdf($discount->notes);
                            @endphp
                            <tr>
                                <td style="text-align: center; vertical-align: top;">{{ $loop->iteration }}</td>
                                <td>
                                    <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 2px;">
                                        {{ $discount->description ?? 'N/A' }}
                                    </div>
                                    @if ($detailsNotes !== '')
                                        <div class="description-html-content" style="margin-left: 8px;">
                                            {!! $detailsNotes !!}
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align: right; vertical-align: top;">
                                    {{ number_format($discount->amount ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif


        {{-- Price Calculation (dari ProductPricingCalculator) --}}
        @php
            $pricing = $pricing ?? \App\Services\ProductPricingCalculator::calculateForProduct($product);
            $totalPublicPrice = $pricing['total_public_price'];
            $totalVendorPrice = $pricing['total_vendor_price'];
            $totalDiscountAmount = $pricing['total_discount_amount'];
            $totalAdditionAmount = $pricing['total_addition_publish'];
            $totalAdditionVendorAmount = $pricing['total_addition_vendor'];
            $subtotalPublish = $pricing['subtotal_publish'];
            $subtotalVendor = $pricing['subtotal_vendor'];
            $finalPriceAfterDiscounts = $pricing['final_publish'];
            $finalVendorPriceAfterDiscounts = $pricing['final_vendor'];
            $profitAndLoss = $pricing['profit_and_loss'];
        @endphp

        <div class="package-details-box">
            <h3 class="section-title">Price Calculation</h3>
            <table class="items-table" style="width: 100%; border-collapse: collapse; font-size: 8pt; margin-top: 10px;">
                <thead>
                    <tr style="background-color: #f3f4f6;">
                        <th style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; font-size: 8pt;">Keterangan</th>
                        <th style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; font-size: 8pt;">Publish (Rp)</th>
                        <th style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; font-size: 8pt;">Vendor (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Harga Awal --}}
                    <tr>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; font-weight: bold; font-size: 8pt;">Harga Awal</td>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; font-size: 8pt;">
                            {{ number_format($totalPublicPrice, 0, ',', '.') }}</td>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; font-size: 8pt;">
                            {{ number_format($totalVendorPrice, 0, ',', '.') }}</td>
                    </tr>

                    {{-- Addition (Penambahan) --}}
                    <tr>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; font-weight: bold; font-size: 8pt;">Addition (Penambahan)
                        </td>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; color: green; font-size: 8pt;">+
                            {{ number_format($totalAdditionAmount, 0, ',', '.') }}</td>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; color: green; font-size: 8pt;">+
                            {{ number_format($totalAdditionVendorAmount, 0, ',', '.') }}</td>
                    </tr>

                    {{-- Subtotal --}}
                    <tr style="background-color: #f9fafb;">
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; font-weight: bold; font-size: 8pt;">Subtotal</td>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; font-weight: bold; font-size: 8pt;">
                            {{ number_format($subtotalPublish, 0, ',', '.') }}</td>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; font-weight: bold; font-size: 8pt;">
                            {{ number_format($subtotalVendor, 0, ',', '.') }}</td>
                    </tr>

                    {{-- Reduction (Pengurangan) --}}
                    <tr>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; font-weight: bold; font-size: 8pt;">Reduction (Pengurangan)
                        </td>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; color: red; font-size: 8pt;">-
                            {{ number_format($totalDiscountAmount, 0, ',', '.') }}</td>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; color: red; font-size: 8pt;">-
                            {{ number_format($totalDiscountAmount, 0, ',', '.') }}</td>
                    </tr>

                    {{-- Total Paket --}}
                    <tr style="background-color: #f9fafb;">
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; font-weight: bold; font-size: 8pt;">Total Paket</td>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; font-weight: bold; font-size: 8pt;">
                            {{ number_format($finalPriceAfterDiscounts, 0, ',', '.') }}</td>
                        <td style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; font-weight: bold; font-size: 8pt;">
                            {{ number_format($finalVendorPriceAfterDiscounts, 0, ',', '.') }}</td>
                    </tr>

                    {{-- Profit / (Loss) --}}
                    <tr>
                        <td colspan="2" style="border: 1px solid #d1d5db; padding: 6px 8px; font-weight: bold; font-size: 8pt;">Profit /
                            (Loss)</td>
                        <td
                            style="border: 1px solid #d1d5db; padding: 6px 8px; text-align: right; font-weight: bold; font-size: 8pt; color: {{ $profitAndLoss < 0 ? 'red' : 'green' }};">
                            {{ number_format($profitAndLoss, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Signatures --}}
        <table class="signature-table" style="width: 100%;">
            <tr>
                {{-- Kolom Kiri: Approval By --}}
                <td style="width: 48%; text-align: center; vertical-align: top; padding: 0;">
                    <p style="margin-bottom: 70px;"><strong>Approval By:</strong></p>
                    <br>
                    <br>
                    <p style="margin-top: 2px; margin-bottom: 1.5px; font-size: 8pt;">{{ $company?->owner_name ?? 'Nama Owner' }}</p>
                    <div style="border-top: 1px solid #000; width: 70%; margin: 2px auto 0;"></div>
                    <p style="margin-top: 2px; font-size: 8pt;">{{ $company?->jabatan_owner ?? 'Jabatan Jawaban Owner' }}</p>
                </td>

                {{-- Spasi antara kolom --}}
                <td style="width: 4%; padding: 0;"></td>

                {{-- Kolom Kanan: Prepared By --}}
                <td style="width: 48%; text-align: center; vertical-align: top; padding: 0;">
                    <p style="margin-bottom: 70px;"><strong>Prepared By:</strong></p>
                    <br>
                    <br>
                    <p style="margin-top: 2px; margin-bottom: 1.5px; font-size: 8pt;">
                        {{ $product->lastEditedBy?->name ?? auth()->user()->name ?? 'System' }}
                    </p>
                    <div style="border-top: 1px solid #000; width: 70%; margin: 2px auto 0;"></div>
                    <p style="margin-top: 2px; font-size: 8pt;">Account Manager</p>
                </td>
            </tr>
        </table>

        {{-- Footer (jika diperlukan di setiap halaman) --}}
        <div class="footer">
            {{ $displayCompanyName ?? ($company?->company_name ?? config('app.name')) }} | {{ now()->format('d F Y H:i:s') }}
        </div>
    </div>
</body>

</html>
