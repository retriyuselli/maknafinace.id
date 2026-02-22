<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Draft Kontrak</title>
    <style>
        @page {
            /* Top margin adjusted to ensure content starts below the fixed header on all pages */
            margin: 110px 45px 30px 65px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1;
            color: #000;
            margin: 0;
        }

        /* Fixed Header */
        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 100px;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-img {
            max-height: 60px;
            width: 80%;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0px;
            right: 0px;
            text-align: right;
            font-size: 11px;
            color: #000000;
        }

        .pagenum:before {
            content: counter(page);
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.1);
            /* Transparent gray */
            z-index: -1000;
            text-align: center;
            white-space: nowrap;
        }

        /* Typography */
        .title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            font-size: 11px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 10px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 5px !important;
            text-transform: uppercase;
            font-size: 11px;
            text-decoration: underline;
        }

        .facility-list {
            /* margin-top: 5px; */
            margin-bottom: 5px;
        }

        .facility-list>ol {
            margin-top: 5px;
            margin-bottom: 5px;
            padding-left: 20px;
        }

        .facility-list>ol>li {
            margin-top: 5px;
            margin-bottom: 0px;
        }

        .facility-list>ol>li>ol {
            margin-top: 5px;
            margin-bottom: 5px;
            /* padding-left: 15px; */
        }

        .facility-list p {
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .facility-list li ol {
            margin-top: 5px;
            margin-bottom: 5px;
            padding-left: 10px;
            list-style-type: lower-alpha;
        }

        .penambahan-list {
            margin-top: 5px;
            margin-bottom: 0px;
        }

        .penambahan-list>ol {
            margin-top: 0px;
            margin-bottom: 5px;
            padding-left: 20px;
        }

        .penambahan-list>ol>li {
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .penambahan-list>ol>li>ol {
            margin-top: 5px;
            margin-bottom: 5px;
            /* padding-left: 15px; */
        }

        .penambahan-list p {
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .penambahan-list li ol {
            margin-top: 5px;
            margin-bottom: 5px;
            padding-left: 30px;
            list-style-type: lower-alpha;
        }

        .pengurangan-list {
            margin-top: 0;
            margin-bottom: 5px;
        }

        .pengurangan-list>ol {
            margin-top: 0;
            margin-bottom: 5px;
            padding-left: 20px;
        }

        .pengurangan-list>ol>li {
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .pengurangan-list>ol>li>ol {
            margin-top: 5px;
            margin-bottom: 5px;
            /* padding-left: 15px; */
        }

        .pengurangan-list p {
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .pengurangan-list li ol {
            margin-top: 5px;
            margin-bottom: 5px;
            padding-left: 30px;
            list-style-type: lower-alpha;
        }

        .konfirmasi-list {
            margin-top: 5px;
            margin-bottom: 10px;
            padding-left: 20px;
        }

        .konfirmasi-list>li {
            margin-top: 5px;
            margin-bottom: 5px;
        }

        /* Content Tables */
        table.content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        table.content-table td {
            vertical-align: top;
            padding: 2px 0;
        }

        .label {
            width: 130px;
            font-weight: normal;
        }

        .separator {
            width: 10px;
            text-align: center;
        }

        .amount {
            text-align: right;
        }

        /* Lists */
        ol,
        ul {
            margin: 0;
            padding-left: 20px;
        }

        li {
            margin-bottom: 5px;
            text-align: justify;
        }

        li p {
            margin-bottom: 5px;
            margin: 0;
            display: inline;
        }

        /* Utilities */
        .text-justify {
            text-align: justify;
        }

        .indent {
            margin-left: 20px;
        }

        .page-break {
            page-break-after: always;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 40px;
            width: 100%;
            page-break-inside: avoid;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            border: 1px solid #000;
            padding: 10px;
        }

        .sign-space {
            height: 70px;
        }

        /* Invoice Style Header Text */
        .company-name {
            font-size: 11px;
            font-weight: bold;
        }

        .company-info {
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="watermark"></div>
    @php
        $companyName = '{Isi dengan nama perusahaan}';
        $companyAddress = '{Isi dengan alamat perusahaan}';
        $companyPhone = '{Isi dengan nomor telepon perusahaan}';
        $companyEmail = '{Isi dengan email perusahaan}';
        $companyOwnerName = '{Isi dengan nama pemilik perusahaan}';
        $companyOwnerPosition = '{Isi dengan jabatan pemilik perusahaan}';
        $companyBankName = '{Isi dengan nama bank perusahaan}';
        $companyBankAccount = '{Isi dengan nomor rekening bank perusahaan}';
        $companyBankHolder = '{Isi dengan nama pemegang rekening bank perusahaan}';

        if (\Illuminate\Support\Facades\Schema::hasTable('companies')) {
            $company = \App\Models\Company::with('paymentMethod')->first();

            if ($company?->company_name) {
                $companyName = $company->company_name;
            }

            if ($company?->address) {
                $companyAddress = $company->address;
            }

            if ($company?->phone) {
                $companyPhone = $company->phone;
            }

            if ($company?->email) {
                $companyEmail = $company->email;
            }

            if ($company?->owner_name) {
                $companyOwnerName = $company->owner_name;
            }

            if ($company?->jabatan_owner) {
                $companyOwnerPosition = $company->jabatan_owner;
            }

            if ($company?->paymentMethod) {
                if ($company->paymentMethod->bank_name) {
                    $companyBankName = $company->paymentMethod->bank_name;
                }
                if ($company->paymentMethod->no_rekening) {
                    $companyBankAccount = $company->paymentMethod->no_rekening;
                }
                if ($company->paymentMethod->name) {
                    $companyBankHolder = $company->paymentMethod->name;
                }
            }
        }
    @endphp
    <!-- HEADER (Fixed on every page) -->
    <header>
        <table class="header-table">
            <tr>
                <td style="width: 65%;">
                    <div class="company-name">{{ $companyName }}</div>
                    <div class="company-info">
                        Alamat : {{ $companyAddress }}<br>
                        No. Tlp : {{ $companyPhone }}<br>
                        Email : {{ $companyEmail }}
                    </div>
                </td>
                <td style="text-align: right;">
                    @php
                        $logoPath = null;
                        $logoSrc = '';

                        if (
                            isset($company) &&
                            $company?->logo_url &&
                            \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo_url)
                        ) {
                            $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($company->logo_url);
                        } else {
                            $logoPath = public_path('images/logomki.png');
                        }

                        if ($logoPath && file_exists($logoPath)) {
                            $logoMime = mime_content_type($logoPath);
                            if ($logoMime) {
                                $logoSrc =
                                    'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoPath));
                            }
                        }
                    @endphp
                    @if ($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Logo Perusahaan" class="logo-img">
                    @else
                        <b>{{ $companyName }}</b>
                    @endif
                </td>
            </tr>
        </table>
    </header>

    <!-- FOOTER -->
    <div class="footer">
        <table style="width: 100%; border-collapse: collapse; border: none;">
            <tr>
                <td style="text-align: right; vertical-align: bottom; padding-right: 0px; font-size: 9px;">
                    @php
                        $printedAt = \Carbon\Carbon::now()->setTimezone('Asia/Jakarta');
                    @endphp
                    <span>
                        Dokumen ini dicetak secara otomatis pada
                        {{ $printedAt->translatedFormat('d F Y') }} pukul {{ $printedAt->translatedFormat('H:i') }} |
                        Hal <span class="pagenum"></span> |
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- MAIN CONTENT -->

    <!-- Title -->
    <div class="title">KONTRAK KERJASAMA PERNIKAHAN</div>
    <div class="subtitle">Nomor : {{ $nomorSurat }}</div>

    <!-- Pihak Pertama -->
    <table class="content-table">
        <tr>
            <td style="width: 20px;">I.</td>
            <td class="label">Nama</td>
            <td class="separator">:</td>
            <td>{{ $companyOwnerName }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="label">Jabatan</td>
            <td class="separator">:</td>
            <td>{{ $companyOwnerPosition }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="label">No. Telp</td>
            <td class="separator">:</td>
            <td>{{ $companyPhone }}</td>
        </tr>
    </table>
    <div class="text-justify indent" style="margin-bottom: 10px;">
        Bertindak untuk dan atas nama {{ $companyName }} beralamat di {{ $companyAddress }}, selanjutnya disebut
        PIHAK PERTAMA.
    </div>

    <!-- Pihak Kedua -->
    <table class="content-table">
        <tr>
            <td style="width: 20px;">II.</td>
            <td class="label">Nama</td>
            <td class="separator">:</td>
            <td>{{ $record->name_ttd ?? '{Nama Pihak Kedua}' }}
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="label">No. Telp</td>
            <td class="separator">:</td>
            <td>+62{{ $prospect->phone ?? '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="label">Alamat</td>
            <td class="separator">:</td>
            <td>{{ $prospect->address ?? '{Alamat Sesuai KTP}' }}</td>
        </tr>
    </table>
    <div class="text-justify indent" style="margin-bottom: 15px;">
        Bertindak untuk dan atas nama diri sendiri, selanjutnya disebut PIHAK KEDUA.
    </div>

    <div class="text-justify" style="margin-bottom: 15px;">
        Sehubungan dengan akan diadakannya Pernikahan <b>{{ $prospect->name_cpw ?? '...' }} &
            {{ $prospect->name_cpp ?? '...' }}</b> di <b>{{ $prospect->venue ?? '...' }}</b>, berikut adalah rincian
        dan
        ketentuan Paket Pernikahannya :
    </div>

    <!-- Event Details -->
    <div class="section-title">Dream Wedding Packages</div>
    <table class="content-table">
        <tr>
            <td class="label">Nama Acara</td>
            <td class="separator">:</td>
            <td>{{ $prospect->name_event ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Paket WO</td>
            <td class="separator">:</td>
            <td>
                @if ($record->product?->name)
                    {{ \Illuminate\Support\Str::title($record->product->name) }}
                @else
                    -
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Lokasi Acara</td>
            <td class="separator">:</td>
            <td>{{ $prospect->venue ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Akad Nikah</div>
    <table class="content-table">
        <tr>
            <td class="label">Hari / Tanggal</td>
            <td class="separator">:</td>
            <td>{{ $prospect->date_akad ? \Carbon\Carbon::parse($prospect->date_akad)->translatedFormat('l, d F Y') : '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">Waktu</td>
            <td class="separator">:</td>
            <td>
                @if (!empty($prospect->time_akad))
                    Pukul {{ \Carbon\Carbon::parse($prospect->time_akad)->format('H:i') }} wib s.d Selesai
                @else
                    Pukul 07:00 / 07:30 wib s.d Selesai
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Jumlah Undangan</td>
            <td class="separator">:</td>
            <td>
                {{ $record->product->pax_akad ?? 500 }} Pax atau
                {{ ($record->product->pax_akad ?? 500) / 2 }} Undangan (Asumsi)
            </td>
        </tr>
    </table>

    <div class="section-title">Resepsi</div>
    <table class="content-table">
        <tr>
            <td class="label">Hari / Tanggal</td>
            <td class="separator">:</td>
            <td>{{ $prospect->date_resepsi ? \Carbon\Carbon::parse($prospect->date_resepsi)->translatedFormat('l, d F Y') : '-' }}
            </td>
        </tr>
        <tr>
            <td class="label">Waktu</td>
            <td class="separator">:</td>
            <td>
                @if (!empty($prospect->time_resepsi))
                    Pukul {{ \Carbon\Carbon::parse($prospect->time_resepsi)->format('H:i') }} wib s.d Selesai
                @else
                    Pukul 10:00 wib s.d Selesai
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Jumlah Undangan</td>
            <td class="separator">:</td>
            <td>{{ $record->product->pax ?? 500 }} Pax atau {{ ($record->product->pax ?? 500) / 2 }} Undangan (Asumsi)
            </td>
        </tr>
    </table>

    @php
        $product = $record->product;
        $baseTotalPrice = $product?->product_price ?? ($record->total_price ?? 0);
        $productPenambahan = $product?->penambahan_publish ?? ($record->penambahan ?? 0);
        $productPengurangan = $product?->pengurangan ?? ($record->pengurangan ?? 0);
        $promo = $record->promo ?? 0;
        $computedGrandTotal = \App\Services\OrderFinance::computeGrandTotalFromValues(
            (float) $baseTotalPrice,
            (float) $productPenambahan,
            (float) $promo,
            (float) $productPengurangan,
        );
    @endphp

    <div class="section-title" style="margin-top: 15px;">PERINCIAN BIAYA</div>
    <table class="content-table" style="width: 100%;">
        <tr>
            <td style="padding: 5px 0;"><b>DREAM WEDDING PACKAGE</b></td>
            <td style="width: 1%; white-space: nowrap; padding: 5px 0;"><b>: Rp. </b></td>
            <td style="width: 60%; padding: 5px 0; text-align: left;">
                <b>&nbsp;{{ number_format($baseTotalPrice, 0, ',', '.') }},-</b>
            </td>
        </tr>
        @if ($productPenambahan > 0)
            <tr>
                <td style="padding: 5px 0;">PENAMBAHAN</td>
                <td style="width: 1%; white-space: nowrap; padding: 5px 0;">: Rp. </td>
                <td style="text-align: left; padding: 5px 0;">
                    &nbsp;{{ number_format($productPenambahan, 0, ',', '.') }},-</td>
            </tr>
        @endif
        @if ($productPengurangan > 0)
            <tr>
                <td style="padding: 5px 0;">PENGURANGAN</td>
                <td style="width: 1%; white-space: nowrap; padding: 5px 0;">: Rp. </td>
                <td style="text-align: left; padding: 5px 0;">
                    &nbsp;({{ number_format($productPengurangan, 0, ',', '.') }},-)</td>
            </tr>
        @endif
        @if ($promo > 0)
            <tr>
                <td style="padding: 5px 0;">PROMO</td>
                <td style="width: 1%; white-space: nowrap; padding: 5px 0;">: Rp. </td>
                <td style="text-align: left; padding: 5px 0;">
                    &nbsp;({{ number_format($promo, 0, ',', '.') }},-)</td>
            </tr>
        @endif
        <tr>
            <td style="padding: 5px 0;"><b>TOTAL PEMBAYARAN</b></td>
            <td style="padding: 5px 0; width: 1%; white-space: nowrap;"><b>: Rp.</b></td>
            <td style="padding: 5px 0; text-align: left;">
                <b>&nbsp;{{ number_format($computedGrandTotal, 0, ',', '.') }},-</b>
            </td>
        </tr>
    </table>

    <!-- Facilities -->
    <div class="section-title" style="margin-top: 15px;">DENGAN RINCIAN FASILITAS SEBAGAI BERIKUT :</div>
    @php
        $groupedItems = $items->groupBy(function ($item) {
            return $item->vendor->name ?? 'LAIN-LAIN';
        });
    @endphp

    <div class="facility-list">
        <ol>
            @foreach ($groupedItems as $categoryName => $categoryItems)
                <li style="font-weight: normal; font-size: 11px; margin-top: 1px;">
                    {{ strtoupper($categoryName) }}

                    @if ($categoryItems->count() === 1)
                        @php
                            $item = $categoryItems->first();
                            $vendor = $item->vendor;
                            $description = $vendor?->description ?? ($item->description ?? ($vendor?->name ?? ''));
                            $plainContent = trim(strip_tags($description));
                            $hideListStyle = preg_match('/^\s*[\da-zA-Z]+[.)]\s*/', $plainContent);
                            $cleanContent = $hideListStyle
                                ? preg_replace('/^\s*[\da-zA-Z]+[.)]\s*/', '', $plainContent)
                                : $plainContent;
                        @endphp
                        <div style="margin-top: 8px; margin-left: 20px;">
                            @if ($hideListStyle)
                                {{ $cleanContent }}
                            @else
                                {!! $description !!}
                            @endif
                            @if ($item->quantity > 1)
                                <b>({{ $item->quantity }}x)</b>
                            @endif
                        </div>
                    @else
                        <ol type="a" style="font-weight: normal; margin-top: 5px; margin-left: 20px;">
                            @foreach ($categoryItems as $item)
                                @php
                                    $vendor = $item->vendor;
                                    $description =
                                        $vendor?->description ?? ($item->description ?? ($vendor?->name ?? ''));
                                    $plainContent = trim(strip_tags($description));
                                    $hideListStyle = preg_match('/^\s*[\da-zA-Z]+[.)]\s*/', $plainContent);
                                    $cleanContent = $hideListStyle
                                        ? preg_replace('/^\s*[\da-zA-Z]+[.)]\s*/', '', $plainContent)
                                        : $plainContent;
                                @endphp
                                <li style="font-size: 11px; margin-top: 5px; margin-bottom: 5px;">
                                    @if ($hideListStyle)
                                        {{ $cleanContent }}
                                    @else
                                        {!! $description !!}
                                    @endif
                                    @if ($item->quantity > 1)
                                        <b>({{ $item->quantity }}x)</b>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    <!-- Penambahan -->
    @php
        $product = $record->product;
        $penambahanItems = $product?->penambahanHarga ?? collect();
        $penguranganItems = $product?->pengurangans ?? collect();
    @endphp

    @if ($penambahanItems->isNotEmpty())
        <div class="section-title">PENAMBAHAN :</div>
        <div class="penambahan-list">
            <ol>
                @foreach ($penambahanItems as $item)
                    <li>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="margin-right: 10px;">
                                <div style="font-weight: normal;">
                                    {{ strtoupper($item->vendor->name ?? 'Penambahan Tanpa Nama') }}
                                </div>
                                @if (!empty($item->description))
                                    <div>
                                        {!! $item->description !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    <!-- Pengurangan -->
    @if ($penguranganItems->isNotEmpty())
        <div class="section-title">PENGURANGAN :</div>
        <div class="pengurangan-list">
            <ol>
                @foreach ($penguranganItems as $item)
                    <li>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="margin-right: 10px;">
                                <div style="font-weight: normal;">
                                    {{ strtoupper($item->description ?? 'Pengurangan Tanpa Nama') }}
                                </div>
                                @if (!empty($item->notes))
                                    <div>
                                        {!! $item->notes !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    <!-- Terms & Confirmation -->
    <div class="section-title">KETENTUAN TAMBAHAN</div>
    <div class="section-title">KONFIRMASI</div>
    <ol class="konfirmasi-list">
        <li>PIHAK PERTAMA harus menerima konfirmasi dari PIHAK KEDUA tentang acara/event tersebut di atas
            selambat-lambatnya 3 (tiga) hari kerja dari Kontrak Kerjasama Paket Pernikahan ini dibuat.</li>
        <li>Pembatalan secara mendadak setelah Kontrak Kerjasama Paket Pernikahan ini ditandatangani akan dikenakan
            biaya sebesar 50% dari total biaya yang tercantum di Kontrak Kerjasama Paket Pernikahan.</li>
        <li>Kontrak Kerjasama Paket Pernikahan ini juga berlaku sebagai Jaminan atas Pembayaran dari PIHAK KEDUA.
        </li>
        <li>PIHAK PERTAMA akan tetap mengikuti kebijakan pihak Gedung yang menjadi lokasi pernikahan yang dipilih
            oleh
            PIHAK KEDUA.</li>
    </ol>

    <div class="section-title">PEMBAYARAN</div>
    <ol>
        <li>
            Pembayaran DP (Down Payment) sebesar
            Rp. {{ number_format($record->payment_dp_amount ?? 0, 0, ',', '.') }},-
            sebagai Booking Date.
        </li>
        @php
            $termins = $record->payment_simulation ?? [];
            $terminCount = is_array($termins) ? count($termins) : 0;
        @endphp
        <li>
            Pembayaran termin dilakukan sesuai simulasi pembayaran:
            @if ($terminCount > 0)
                <ol type="a" style="margin-top: 5px; margin-left: 10px;">
                    @foreach ($termins as $index => $termin)
                        @php
                            $persen = $termin['persen'] ?? null;
                            $nominal = $termin['nominal'] ?? null;
                            $bulan = $termin['bulan'] ?? null;
                            $tahun = $termin['tahun'] ?? null;
                        @endphp
                        <li>
                            Termin {{ $index + 1 }}
                            @if (!is_null($nominal))
                                sebesar Rp. {{ number_format((float) $nominal, 0, ',', '.') }},-
                            @endif
                            @if (!is_null($persen))
                                yaitu {{ rtrim(rtrim(number_format((float) $persen, 2, ',', '.'), '0'), ',') }}%
                            @endif
                            @if (!empty($bulan))
                                pada Bulan {{ $bulan }}@if (!is_null($tahun))
                                    {{ $tahun }}
                                @endif
                            @endif
                        </li>
                    @endforeach
                </ol>
            @else
                dengan ketentuan yang disepakati bersama oleh kedua belah pihak.
            @endif
        </li>
        <li>Pelunasan pembayaran paling lambat H-14 (Empat Belas Hari) sebelum acara dilaksanakan.</li>
        <li>Pembayaran dapat dilakukan melalui transfer ke rekening:
            <div style="text-align: center; margin-top: 10px; margin-bottom: 10px;">
                Bank <b>{{ $companyBankName }}</b><br>
                No. Rekening: <b>{{ $companyBankAccount }}</b><br>
                A.n: <b>{{ $companyBankHolder }}</b>
            </div>
        </li>
        <li>Bukti transfer dapat di email ke {{ $companyEmail }} atau datang langsung ke kantor {{ $companyName }}
            dengan
            menunjukkan bukti ke bagian administrasi.</li>
        <li>Pembayaran secara tunai dilakukan langsung ke bagian administrasi di kantor {{ $companyName }} dan
            PIHAK KEDUA akan menerima bukti pembayaran atau pelunasan yang telah ditandatangani oleh bagian keuangan
            atau bisa langsung menghubungi saudari <b>{{ $financeUser->name ?? 'Finance' }} di nomor
                {{ $financeUser->phone_number ?? '-' }}</b>.</li>
        <li>Tidak dibenarkan melakukan pembayaran di luar dengan cara menitipkan kepada pihak lain selain yang
            ditunjuk oleh PIHAK PERTAMA.</li>
    </ol>

    <div class="section-title" style="margin-top: 10px;">VENDOR</div>
    <ol>
        <li>Vendor pernikahan yang telah dipilih oleh PIHAK KEDUA, wajib bertanggung jawab terhadap fasilitas yang
            telah
            diberikan sesuai dengan paket yang telah dipilih. PIHAK PERTAMA bersedia membantu sebagai mediator dalam
            berdiskusi dan koordinasi jika terjadi kendala dengan vendor.</li>
        <li>PIHAK PERTAMA akan memberikan daftar rekomendasi vendor yang telah sesuai dengan kriteria sehingga dapat
            dijadikan pilihan oleh PIHAK KEDUA dalam menentukan vendor pernikahan.</li>
        <li>PIHAK KEDUA dapat melakukan perubahan vendor diluar rekomendasi yang telah disampaikan dengan
            menyesuaikan
            perhitungan dari paket sebelumnya.</li>
        <li>Apabila diperlukan, para vendor akan diminta untuk membuat kontrak kerjasama yang isinya mengenai
            pertanggungjawaban para vendor terhadap keberhasilan acara pernikahan sesuai dengan ketentuan yang telah
            disepakati sebelumnya antara vendor dan PIHAK KEDUA.</li>
        <li>Jika vendor yang telah dipilih PIHAK KEDUA tidak mampu mengikuti kesepakatan dari PIHAK KEDUA mengenai
            pertanggung jawaban, maka PIHAK PERTAMA akan memberikan rekomendasi vendor lain yang mampu mengikuti
            kesepakatan PIHAK PERTAMA dan PIHAK KEDUA</li>
    </ol>

    <div class="section-title" style="margin-top: 10px;">PEMBATALAN :</div>
    <ol>
        <li>Apabila terjadi pembatalan sepihak dari konsumen (keluarga/pengantin) PIHAK KEDUA, maka uang yang telah
            disetorkan dapat dikembalikan dengan syarat sebagai berikut :</li>
        <li>Jika pembatalan 3 (tiga) bulan sebelum acara berlangsung maka akan dikenakan biaya 50% dari total biaya
            yang
            telah disepakati.</li>
        <li>Jika pembatalan 1 (satu) bulan sebelum acara berlangsung, maka akan dikenakan biaya 100% dari total
            biaya
            yang telah disepakati.</li>
        <li>Jika pembatalan dilakukan setelah ada pembayaran ke beberapa vendor, maka uang yang telah disetor ke
            vendor
            akan mengikuti kebijakan dari masing - masing vendor dalam hal pengembalian uang.</li>
        <li>Uang muka sebagai tanda jadi atau down payment (DP) yang telah dibayarkan tidak dapat dikembalikan.</li>
    </ol>

    <div class="section-title" style="margin-top: 10px;">FORCE MAJEURE</div>
    <ol>
        <li>Force Majeure yang dimaksud adalah suatu keadaan memaksa diluar batas kemampuan kedua belah pihak yang
            dapat
            menggangu bahkan menggagalkan terlaksananya event, seperti bencana alam, pandemi penyakit berbahaya,
            peperangan, pemogokan, sabotase, pemberontakan masyarakat, blokade, kebijaksanaan pemerintah dan
            khususnya
            yang disebabkan diluar batas kemampuan manusia.</li>
        <li>Terhadap pembatalan akibat dari Force Majeure, PIHAK PERTAMA dan PIHAK KEDUA sepakat untuk menanggung
            kerugiannya masing – masing.</li>
    </ol>

    <p style="text-align: justify; margin-top: 10px;">
        Demikianlah Kontrak Kerjasama Paket Pernikahan ini dibuat dalam 2 (dua) rangkap dan ditandatangani oleh
        kedua
        belah pihak.
    </p>

    <!-- Signatures -->
    <div style="text-align: right; margin-bottom: 5px; margin-top: 20px;">
        Palembang,
        {{ $record->created_at
            ? $record->created_at->copy()->setTimezone('Asia/Jakarta')->translatedFormat('d F Y')
            : \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y') }}
    </div>
    <div class="signature-section" style="margin-top: 0;">
        <table class="signature-table">
            <tr>
                <td style="width: 35%;">
                    Menyetujui,<br>
                </td>
                <td colspan="2" style="width: 65%;">
                    Mengetahui,<br>
                    {{ $companyName }}
                </td>
            </tr>
            <tr>
                <td style="vertical-align: bottom; height: 120px;">
                    <div style="text-decoration: underline;">
                        {{ $record->name_ttd ?? '....................' }}
                    </div>
                    <b>{{ $record->title_ttd ?? 'Calon Pengantin' }}</b>
                </td>
                <td style="vertical-align: bottom; height: 100px;">
                    <div style="text-decoration: underline;">
                        {{ $companyOwnerName }}
                    </div>
                    <b>{{ $companyOwnerPosition }}</b>
                </td>
                <td style="vertical-align: bottom; height: 100px;">
                    <div style="text-decoration: underline;">
                        Syafira Putri Ramadhania
                    </div>
                    <b>Account Manager</b>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>
