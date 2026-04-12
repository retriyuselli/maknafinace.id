<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simulasi Penawaran</title>
    <style>
        @page {
            margin: 110pt 35pt 18pt 60pt;
            size: A4 portrait;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            line-height: 1;
            color: #000;
        }

        #footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -8pt;
            font-size: 7pt;
            color: #555;
        }

        #footer .footer-left {
            float: left;
        }

        #footer .footer-right {
            float: right;
        }

        #footer:after {
            content: "";
            display: block;
            clear: both;
        }

        #header {
            position: fixed;
            left: 0;
            right: 0;
            top: -84pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        .header-wrap {
            border-bottom: 1px solid #000;
            padding-bottom: 8pt;
            margin-bottom: 6pt;
            page-break-inside: avoid;
        }

        .header-left {
            width: 65%;
            vertical-align: top;
        }

        .header-right {
            width: 35%;
            vertical-align: top;
            text-align: right;
        }

        .header-title {
            font-size: 9pt;
            font-weight: bold;
            margin: 0 0 4pt 0;
        }

        .meta-grid {
            width: 100%;
            margin: -16pt 0 12pt 0;
        }

        .meta-grid td {
            vertical-align: top;
            padding: 0;
        }

        .meta-grid p {
            margin: 0 0 2pt 0;
        }

        .meta-grid tr {
            page-break-inside: avoid;
        }

        .invoice-table th,
        .invoice-table td {
            border: 0.8pt solid #ddd;
            padding: 6pt;
            text-align: left;
            vertical-align: top;
        }

        .invoice-table {
            border: 0.8pt solid #ddd;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;
        }

        .invoice-table tr {
            page-break-inside: auto;
        }

        .invoice-table th {
            background: #eef4ff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
        }

        .no-print {
            display: none;
        }

        .item-desc {
            text-transform: capitalize;
        }

        .item-desc p,
        .item-desc ul,
        .item-desc ol {
            margin: 0 0 2pt 0;
        }

        .item-desc ol {
            padding-left: 18pt;
        }

        .item-desc ul {
            padding-left: 28pt;
        }

        .item-desc li {
            margin: 0;
        }

        .amount {
            text-align: right;
            white-space: nowrap;
        }

        .addition-amount {
            color: #28a745;
            font-weight: 600;
        }

        .reduction-amount {
            color: #dc3545;
            font-weight: 600;
        }

        .signature-area {
            margin-top: 28pt;
            width: 100%;
            font-size: 9pt;
            page-break-inside: avoid;
        }

        .signature-col {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin: 0 18pt;
            padding-top: 4pt;
        }

        .notes-box {
            border: 1px solid #ddd;
            padding: 8pt;
            margin: 0 0 10pt 0;
        }
    </style>
</head>

<body>
    <div id="footer">
        <div class="footer-left">Data ini dicetak otomatis</div>
        <div class="footer-right">{{ now()->timezone('Asia/Jakarta')->format('d F Y H:i') }}</div>
    </div>
    @php
        $company = $company ?? \App\Models\Company::first();

        $logoSrc = '';
        if ($company && $company->logo_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo_url)) {
            $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($company->logo_url);
        } else {
            $logoPath = public_path('images/logomki.png');
        }

        if (is_string($logoPath) && file_exists($logoPath)) {
            $logoSrc = 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $itemsCollection = collect($items ?? []);

        $totalPublicPrice = $itemsCollection->sum(function ($item) {
            return ($item->harga_publish ?? 0) * ($item->quantity ?? 1);
        });

        $totalVendorPrice = $itemsCollection->sum(function ($item) {
            return ($item->harga_vendor ?? 0) * ($item->quantity ?? 1);
        });

        $penambahanHarga = $simulasi->product->penambahanHarga ?? collect();
        $pengurangans = $simulasi->product->pengurangans ?? collect();

        $totalAdditionPublish = $penambahanHarga->sum('harga_publish');
        $totalAdditionVendor = $penambahanHarga->sum('harga_vendor');

        $calculationTotalReductions = $pengurangans->sum('amount');

        $basePackagePrice = $totalPublicPrice;
        $baseVendorPrice = $totalVendorPrice;

        $finalPublicPriceAfterDiscounts = $basePackagePrice + $totalAdditionPublish - $calculationTotalReductions;
        $finalVendorPriceAfterDiscounts = $baseVendorPrice + $totalAdditionVendor - $calculationTotalReductions;

        $calculationProfitLoss = $finalPublicPriceAfterDiscounts - $finalVendorPriceAfterDiscounts;
    @endphp

    <div id="header">
        <div class="header-wrap">
            <table>
                <tr>
                    <td class="header-left">
                        {{-- <p class="header-title">Office Information :</p> --}}
                        <div>
                            <div><strong>{{ strtoupper($company->company_name ?? ($companyName ?? config('app.name'))) }}</strong></div>
                            <div>{{ $company->address ?? '-' }}</div>
                            <div>Phone: {{ $company->phone ?? '-' }}</div>
                        </div>
                    </td>
                    <td class="header-right">
                        @if ($logoSrc)
                            <img src="{{ $logoSrc }}" alt="Logo" style="max-height: 60pt; width: 100pt;">
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <table class="meta-grid">
        <tr>
            <td style="width: 55%;">
                <p><b>Event Name :</b> {{ $simulasi->prospect->name_event ?? 'N/A' }}</p>
                <p><b>Created By :</b> {{ $simulasi->user->name ?? 'N/A' }}</p>
                <p><b>Base Product :</b> {{ \Illuminate\Support\Str::title($simulasi->product->name ?? 'N/A') }}</p>
            </td>
            <td style="width: 45%;">
                <p><b>Date Akad :</b> {{ $simulasi->prospect->date_akad ? $simulasi->prospect->date_akad->format('d F Y') : 'N/A' }}</p>
                <p><b>Date Resepsi :</b> {{ $simulasi->prospect->date_resepsi ? $simulasi->prospect->date_resepsi->format('d F Y') : 'N/A' }}</p>
                <p><b>Valid Until :</b> {{ $simulasi->created_at ? $simulasi->created_at->addDays(4)->format('d F Y') : 'N/A' }}</p>
                <p><b>Penawaran :</b> 00{{ $simulasi->id }}</p>
            </td>
        </tr>
    </table>

    <table class="invoice-table" style="margin-bottom: 10pt;">
        <thead>
            <tr>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($itemsCollection as $item)
                <tr>
                    <td>
                        <div>
                            <strong>{{ $item->vendor->name ?? ($item->vendor_id ? 'Vendor ID: ' . $item->vendor_id : 'N/A') }}</strong>
                        </div>
                        @if (!empty($item->description))
                            <div class="item-desc">{!! strtolower($item->description) !!}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td>Tidak ada item spesifik yang terdaftar untuk produk ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($penambahanHarga->isNotEmpty())
        <div style="margin: 0 0 4pt 0;"><b>Detail Penambahan :</b></div>
        <table class="invoice-table" style="margin-bottom: 10pt;">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount">Publish</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($penambahanHarga as $penambahan_item)
                    <tr>
                        <td>
                            <div><strong>{{ $penambahan_item->vendor->name ?? 'Penambahan Tanpa Nama' }}</strong></div>
                            @if (!empty($penambahan_item->description))
                                <div class="item-desc">{!! $penambahan_item->description !!}</div>
                            @endif
                        </td>
                        <td class="amount addition-amount">{{ number_format($penambahan_item->harga_publish ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($pengurangans->isNotEmpty())
        <div style="margin: 0 0 4pt 0;"><b>Detail Pengurangan :</b></div>
        <table class="invoice-table" style="margin-bottom: 10pt;">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pengurangans as $pengurangan_item)
                    <tr>
                        <td>
                            <div><strong>{{ $pengurangan_item->description ?? ($pengurangan_item->name ?? 'Pengurangan Tanpa Nama') }}</strong></div>
                            @if (!empty($pengurangan_item->notes))
                                <div class="item-desc">{!! $pengurangan_item->notes !!}</div>
                            @endif
                        </td>
                        <td class="amount">{{ number_format($pengurangan_item->amount ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (!empty($simulasi->notes) && trim(strip_tags($simulasi->notes)) !== '')
        <table class="invoice-table" style="margin-bottom: 10pt;">
            <tbody>
                <tr>
                    <td>
                        <b>Notes (Jika ada) :</b>
                        <div>{!! $simulasi->notes !!}</div>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    <table class="invoice-table" style="margin-bottom: 10pt;">
        <thead>
            <tr>
                <th>Description</th>
                <th class="amount">Publish</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Base Price</strong></td>
                <td class="amount">{{ number_format($basePackagePrice, 0, ',', '.') }}</td>
            </tr>
            @if ($totalAdditionPublish > 0 || $totalAdditionVendor > 0)
                <tr>
                    <td class="addition-amount"><strong>Additions</strong></td>
                    <td class="amount addition-amount">+ {{ number_format($totalAdditionPublish, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if ($calculationTotalReductions > 0)
                <tr>
                    <td class="reduction-amount"><strong>Reductions</strong></td>
                    <td class="amount reduction-amount">({{ number_format($calculationTotalReductions, 0, ',', '.') }})</td>
                </tr>
            @endif
            <tr>
                <td><strong>TOTAL</strong></td>
                <td class="amount"><strong>{{ number_format($finalPublicPriceAfterDiscounts, 0, ',', '.') }}</strong></td>
            </tr>
            <tr class="no-print">
                <td style="text-align: right; font-weight: bold;">PROFIT & LOSS</td>
                <td class="amount" style="{{ $calculationProfitLoss < 30000000 ? 'color: red; font-weight: bold;' : 'color: blue; font-weight: bold;' }}">
                    {{ number_format($calculationProfitLoss, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <table class="signature-area">
        <tr>
            <td class="signature-col">
                <div style="margin-bottom: 60pt;">Hormat Kami,</div>
                <div class="signature-line">( {{ $simulasi->user->name ?? 'Account Manager' }} )</div>
                <div>{{ $company->company_name ?? ($companyName ?? config('app.name')) }}</div>
            </td>
            <td class="signature-col">
                <div style="margin-bottom: 60pt;">Disetujui Oleh,</div>
                <div class="signature-line">(_________________________)</div>
                <div>Klien</div>
            </td>
        </tr>
    </table>
</body>

</html>
