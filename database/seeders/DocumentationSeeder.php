<?php

namespace Database\Seeders;

use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Illuminate\Database\Seeder;

class DocumentationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================
        // 0. KATEGORI: MANAJEMEN PENJUALAN (CRM)
        // ==========================================
        $crmCategory = DocumentationCategory::updateOrCreate(
            ['slug' => 'manajemen-penjualan'],
            [
                'name' => 'Manajemen Penjualan (CRM)',
                'icon' => 'heroicon-o-user-group',
                'order' => 0,
                'is_active' => true,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'mengelola-data-prospect'],
            [
                'documentation_category_id' => $crmCategory->id,
                'title' => 'Mengelola Data Prospect',
                'content' => '
<h2>Mengelola Data Prospect (Calon Klien)</h2>
<p>Prospect adalah calon pelanggan potensial yang telah menunjukkan ketertarikan pada layanan Wedding Organizer kita, baik melalui pameran, media sosial, atau referensi.</p>

<h3>Fungsi Fitur Prospect:</h3>
<ul>
    <li>Mencatat database calon pengantin.</li>
    <li>Melacak status pendekatan (Follow-up, Meeting, Deal/Lost).</li>
    <li>Menyimpan preferensi awal (Budget, Rencana Tanggal, Venue).</li>
</ul>

<h3>Cara Menambahkan Prospect Baru:</h3>
<ol>
    <li>Masuk ke menu <strong>Prospects</strong>.</li>
    <li>Klik tombol <strong>New Prospect</strong>.</li>
    <li>Isi data utama:
        <ul>
            <li><strong>Nama Calon Pengantin</strong> (Pria & Wanita).</li>
            <li><strong>Kontak (No. HP/WA)</strong>.</li>
            <li><strong>Sumber Info</strong> (Instagram, Website, Referral).</li>
            <li><strong>Estimasi Budget</strong>.</li>
        </ul>
    </li>
    <li>Tentukan <strong>Status Awal</strong> (biasanya "New" atau "Contacted").</li>
    <li>Klik <strong>Simpan</strong>.</li>
</ol>

<div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mt-4">
    <strong>Tips:</strong> Jangan lupa mencatat setiap hasil komunikasi di kolom "Notes" atau "Activity Log" agar tim sales lain mengetahui progress-nya.
</div>
                ',
                'is_published' => true,
                'keywords' => 'prospect, calon klien, crm, leads, sales',
                'related_resource' => 'ProspectResource',
                'order' => 1,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'konversi-prospect-ke-order'],
            [
                'documentation_category_id' => $crmCategory->id,
                'title' => 'Konversi Prospect Menjadi Order',
                'content' => '
<h2>Konversi Prospect Menjadi Order</h2>
<p>Ketika prospect setuju untuk menggunakan jasa kita (Deal), Anda dapat langsung mengonversinya menjadi Order tanpa mengetik ulang data.</p>

<h3>Langkah-langkah:</h3>
<ol>
    <li>Buka detail data <strong>Prospect</strong> yang sudah Deal.</li>
    <li>Ubah status menjadi <strong>"Won"</strong> atau <strong>"Deal"</strong>.</li>
    <li>Klik tombol Action <strong>"Convert to Order"</strong> (jika tersedia) atau buat Order baru dan pilih nama prospect tersebut di kolom Customer.</li>
</ol>

<h3>Jika Prospect Gagal (Lost):</h3>
<p>Jika calon klien membatalkan atau memilih vendor lain:</p>
<ol>
    <li>Ubah status menjadi <strong>"Lost"</strong>.</li>
    <li>Isi alasan pembatalan (misal: "Budget tidak masuk", "Pilih kompetitor A").</li>
</ol>
<p>Data ini penting untuk evaluasi strategi pemasaran ke depannya.</p>
                ',
                'is_published' => true,
                'keywords' => 'convert, deal, lost, won, order',
                'related_resource' => 'ProspectResource',
                'order' => 2,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'target-manajer-akun'],
            [
                'documentation_category_id' => $crmCategory->id,
                'title' => 'Target Manajer Akun (Sales Target)',
                'content' => '
<h2>Mengelola Target Penjualan Tim</h2>
<p>Fitur <strong>Target Manajer Akun</strong> digunakan untuk menetapkan target omzet penjualan yang harus dicapai oleh setiap Account Manager (Sales) dalam periode tertentu.</p>

<h3>Cara Setting Target:</h3>
<ol>
    <li>Masuk menu <strong>Target Manajer Akun</strong>.</li>
    <li>Klik <strong>New Target</strong>.</li>
    <li>Pilih <strong>Nama Karyawan/Sales</strong>.</li>
    <li>Tentukan <strong>Periode</strong> (Bulan & Tahun).</li>
    <li>Isi nominal <strong>Target Omzet</strong> (misal: Rp 500.000.000).</li>
</ol>

<div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mt-4">
    <strong>Monitoring:</strong> Progres pencapaian target dapat dilihat di Dashboard Utama atau Laporan Sales. Persentase akan otomatis terupdate setiap kali ada Order yang statusnya "Confirmed" atau "Paid".
</div>
                ',
                'is_published' => true,
                'keywords' => 'target, sales, omzet, kpi',
                'related_resource' => 'TargetResource',
                'order' => 3,
            ]
        );

        // ==========================================
        // 0.5. KATEGORI: TOOLS & UTILITAS
        // ==========================================
        $toolsCategory = DocumentationCategory::updateOrCreate(
            ['slug' => 'tools-utilitas'],
            [
                'name' => 'Tools & Utilitas',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'order' => 1, // Setelah CRM
                'is_active' => true,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'fitur-simulasi-budget'],
            [
                'documentation_category_id' => $toolsCategory->id,
                'title' => 'Menggunakan Fitur Simulasi',
                'content' => '
<h2>Simulasi Budget Pernikahan</h2>
<p>Fitur <strong>Simulasi</strong> sangat berguna saat pertemuan awal dengan klien. Anda dapat membuat rancangan anggaran kasar tanpa harus membuat Order resmi.</p>

<h3>Fungsi Simulasi:</h3>
<ul>
    <li>Memberikan estimasi biaya cepat kepada calon klien.</li>
    <li>Membandingkan beberapa opsi paket (Paket A vs Paket B).</li>
    <li>Drafting kebutuhan vendor sebelum deal.</li>
</ul>

<h3>Cara Membuat Simulasi:</h3>
<ol>
    <li>Masuk menu <strong>Simulasi</strong>.</li>
    <li>Klik <strong>Buat Simulasi Baru</strong>.</li>
    <li>Masukkan parameter dasar:
        <ul>
            <li>Jumlah Tamu (Pax).</li>
            <li>Lokasi/Venue.</li>
            <li>Tema Dekorasi.</li>
        </ul>
    </li>
    <li>Sistem akan menarik harga dari Katalog Produk untuk memberikan estimasi total.</li>
    <li>Anda bisa mencetak (Print) atau mengirim PDF simulasi ini ke klien sebagai bahan pertimbangan.</li>
</ol>
                ',
                'is_published' => true,
                'keywords' => 'simulasi, estimasi, budget, draft',
                'related_resource' => 'SimulationResource',
                'order' => 1,
            ]
        );

        // ==========================================
        // 1. KATEGORI: MANAJEMEN ORDER (PROYEK)
        // ==========================================
        $orderCategory = DocumentationCategory::updateOrCreate(
            ['slug' => 'manajemen-order'],
            [
                'name' => 'Manajemen Order (Event)',
                'icon' => 'heroicon-o-shopping-cart',
                'order' => 1,
                'is_active' => true,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'membuat-order-baru'],
            [
                'documentation_category_id' => $orderCategory->id,
                'title' => 'Cara Membuat Order Event Baru',
                'content' => '
<h2>Panduan Membuat Order Event Baru</h2>
<p>Fitur ini digunakan untuk mencatat pesanan baru dari klien (calon pengantin). Order ini akan menjadi induk dari seluruh transaksi keuangan dan operasional event.</p>

<h3>Langkah-langkah:</h3>
<ol>
    <li>Masuk ke menu <strong>Orders</strong> di sidebar.</li>
    <li>Klik tombol <strong>+ New Order</strong> di pojok kanan atas.</li>
    <li>Isi <strong>Informasi Klien</strong>:
        <ul>
            <li><strong>Customer</strong>: Pilih nama klien yang sudah terdaftar. Jika belum ada, klik tanda (+) untuk membuat data customer baru.</li>
            <li><strong>Event Date</strong>: Tentukan tanggal resepsi/acara.</li>
            <li><strong>Venue</strong>: Lokasi acara berlangsung.</li>
        </ul>
    </li>
    <li>Pilih <strong>Paket & Produk</strong>:
        <ul>
            <li>Klik "Add Item" untuk memasukkan paket pernikahan atau item satuan.</li>
            <li>Sesuaikan kuantitas (Qty) dan harga jika ada diskon khusus.</li>
        </ul>
    </li>
    <li><strong>Informasi Pembayaran</strong>:
        <ul>
            <li>Sistem akan otomatis menghitung <strong>Grand Total</strong>.</li>
            <li>Tentukan termin pembayaran (misal: DP 30%, Termin 1 50%, Pelunasan 20%).</li>
        </ul>
    </li>
    <li>Klik <strong>Create Order</strong> untuk menyimpan.</li>
</ol>

<div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mt-4">
    <strong>Tips:</strong> Pastikan status order di-set ke "Draft" jika masih berupa penawaran, dan ubah ke "Confirmed" setelah klien membayar Booking Fee.
</div>
                ',
                'is_published' => true,
                'keywords' => 'order, event, buat pesanan, booking, invoice',
                'related_resource' => 'OrderResource',
                'order' => 1,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'order-penambahan-add-ons'],
            [
                'documentation_category_id' => $orderCategory->id,
                'title' => 'Mengelola Order Penambahan (Add-Ons)',
                'content' => '
<h2>Order Penambahan (Add-Ons)</h2>
<p>Seringkali klien meminta tambahan item di tengah jalan setelah kontrak utama disepakati. Fitur <strong>Order Penambahan</strong> memungkinkan Anda mencatat tagihan tambahan tanpa mengubah Invoice Utama.</p>

<h3>Kapan Menggunakan Fitur Ini?</h3>
<ul>
    <li>Klien menambah porsi catering H-7 acara.</li>
    <li>Penambahan sewa tenda atau kursi di luar paket awal.</li>
    <li>Biaya overtime gedung atau vendor.</li>
</ul>

<h3>Cara Input Order Penambahan:</h3>
<ol>
    <li>Buka detail <strong>Order Utama</strong> yang bersangkutan.</li>
    <li>Cari tab atau bagian <strong>"Order Penambahan"</strong>.</li>
    <li>Klik <strong>Create Penambahan</strong>.</li>
    <li>Isi detail item tambahan:
        <ul>
            <li><strong>Item</strong>: Pilih produk/jasa tambahan.</li>
            <li><strong>Harga & Qty</strong>: Masukkan nominal yang disepakati.</li>
            <li><strong>Keterangan</strong>: Catat alasan penambahan (misal: "Tambahan 50 pax buffet").</li>
        </ul>
    </li>
    <li>Simpan data.</li>
</ol>

<p>Sistem akan membuat tagihan terpisah (Invoice Tambahan) yang tidak mengganggu jadwal pembayaran invoice utama.</p>
                ',
                'is_published' => true,
                'keywords' => 'add on, tambahan, order penambahan, biaya tambahan',
                'related_resource' => 'OrderPenambahanResource',
                'order' => 2,
            ]
        );

        // ==========================================
        // 2. KATEGORI: MANAJEMEN VENDOR
        // ==========================================
        $vendorCategory = DocumentationCategory::updateOrCreate(
            ['slug' => 'manajemen-vendor'],
            [
                'name' => 'Manajemen Vendor',
                'icon' => 'heroicon-o-users',
                'order' => 2,
                'is_active' => true,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'registrasi-vendor-baru'],
            [
                'documentation_category_id' => $vendorCategory->id,
                'title' => 'Registrasi & Profiling Vendor',
                'content' => '
<h2>Mendaftarkan Vendor Baru</h2>
<p>Agar operasional tertata, semua mitra kerja (Katering, Dekorasi, MUA, Fotografer) harus terdaftar dalam database sistem.</p>

<h3>Data yang Diperlukan:</h3>
<ul>
    <li><strong>Nama Vendor</strong>: Nama bisnis/perusahaan.</li>
    <li><strong>Kategori</strong>: Jenis layanan (misal: Venue, Catering, Attire).</li>
    <li><strong>Kontak PIC</strong>: Nama penanggung jawab dan No. WhatsApp.</li>
    <li><strong>Informasi Rekening</strong>: Nama Bank dan No. Rekening (Penting untuk pembayaran otomatis).</li>
</ul>

<h3>Cara Input:</h3>
<ol>
    <li>Masuk menu <strong>Vendors</strong>.</li>
    <li>Klik <strong>New Vendor</strong>.</li>
    <li>Lengkapi formulir yang tersedia.</li>
    <li>Upload <strong>Dokumen Kerjasama</strong> (MOU/Kontrak) pada tab "Documents" jika ada.</li>
</ol>
                ',
                'is_published' => true,
                'keywords' => 'vendor, supplier, mitra, pendaftaran vendor',
                'related_resource' => 'VendorResource',
                'order' => 1,
            ]
        );

        // ==========================================
        // 3. KATEGORI: PRODUK & LAYANAN
        // ==========================================
        $productCategory = DocumentationCategory::updateOrCreate(
            ['slug' => 'produk-layanan'],
            [
                'name' => 'Produk & Layanan',
                'icon' => 'heroicon-o-tag',
                'order' => 3,
                'is_active' => true,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'manajemen-katalog-produk'],
            [
                'documentation_category_id' => $productCategory->id,
                'title' => 'Manajemen Katalog Produk',
                'content' => '
<h2>Mengelola Katalog Produk & Jasa</h2>
<p>Katalog produk memudahkan Anda saat membuat penawaran (Order). Anda tidak perlu mengetik ulang nama item dan harga setiap kali ada klien baru.</p>

<h3>Jenis Produk:</h3>
<ol>
    <li><strong>Service (Jasa)</strong>: Item yang tidak memiliki stok fisik (contoh: Jasa WO, MC, Hiburan).</li>
    <li><strong>Goods (Barang)</strong>: Item fisik yang mungkin memiliki stok (contoh: Souvenir, Undangan).</li>
    <li><strong>Package (Paket)</strong>: Bundling dari beberapa item.</li>
</ol>

<h3>Menentukan Harga:</h3>
<ul>
    <li><strong>Base Price</strong>: Harga modal (HPP) dari vendor.</li>
    <li><strong>Sell Price</strong>: Harga jual ke klien (setelah margin).</li>
</ul>
<p>Pastikan Anda mengupdate harga secara berkala mengikuti kenaikan harga dari vendor.</p>
                ',
                'is_published' => true,
                'keywords' => 'produk, jasa, katalog, harga, paket',
                'related_resource' => 'ProductResource',
                'order' => 1,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'manajemen-kategori'],
            [
                'documentation_category_id' => $productCategory->id,
                'title' => 'Manajemen Kategori Produk & Vendor',
                'content' => '
<h2>Mengelola Master Kategori</h2>
<p>Kategori berfungsi untuk mengelompokkan data agar lebih terstruktur dan mudah dicari. Kategori digunakan di dua modul utama: <strong>Produk</strong> dan <strong>Vendor</strong>.</p>

<h3>Jenis Kategori Umum:</h3>
<ul>
    <li><strong>Venue</strong>: Gedung, Hotel, Aula, Garden.</li>
    <li><strong>Catering</strong>: Buffet, Stall, Minuman.</li>
    <li><strong>Decoration</strong>: Pelaminan, Dekorasi Lorong, Photo Booth.</li>
    <li><strong>Attire & MUA</strong>: Rias Pengantin, Busana, Aksesoris.</li>
    <li><strong>Documentation</strong>: Foto, Video, Cinematic.</li>
    <li><strong>Entertainment</strong>: Band, MC, Sound System.</li>
</ul>

<h3>Cara Menambah Kategori Baru:</h3>
<ol>
    <li>Masuk menu <strong>Kategori</strong>.</li>
    <li>Klik <strong>New Category</strong>.</li>
    <li>Isi <strong>Nama Kategori</strong> (misal: "Souvenir").</li>
    <li>(Opsional) Upload icon atau gambar representatif.</li>
    <li>Simpan.</li>
</ol>
<p>Kategori yang sudah dibuat akan muncul sebagai pilihan saat Anda menginput Produk atau Vendor baru.</p>
                ',
                'is_published' => true,
                'keywords' => 'kategori, master data, grouping, jenis',
                'related_resource' => 'CategoryResource',
                'order' => 2,
            ]
        );

        // ==========================================
        // 4. KATEGORI: ASET TETAP (Existing Refined)
        // ==========================================
        $asetCategory = DocumentationCategory::updateOrCreate(
            ['slug' => 'aset-tetap'],
            [
                'name' => 'Aset Tetap',
                'icon' => 'heroicon-o-building-office-2',
                'order' => 4,
                'is_active' => true,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'metode-penyusutan-aset-tetap'],
            [
                'documentation_category_id' => $asetCategory->id,
                'title' => 'Metode Penyusutan Aset Tetap',
                'content' => '
<h2>Metode Penyusutan Aset Tetap</h2>
<p>Sistem mendukung perhitungan penyusutan otomatis untuk aset inventaris kantor (Laptop, Kendaraan, Furniture).</p>

<h3>1. Metode Garis Lurus (Straight Line)</h3>
<p>Nilai aset disusutkan rata setiap bulan. <strong>(Recommended)</strong></p>
<pre>Rumus: (Harga Beli - Nilai Sisa) / Umur Ekonomis (Bulan)</pre>

<h3>2. Metode Saldo Menurun</h3>
<p>Penyusutan besar di awal tahun, mengecil di tahun-tahun berikutnya. Cocok untuk kendaraan atau elektronik yang cepat turun harganya.</p>

<div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
    <strong>Penting:</strong> Penyusutan akan otomatis menjurnal beban penyusutan setiap akhir bulan jika fitur auto-journal diaktifkan.
</div>
                ',
                'is_published' => true,
                'keywords' => 'penyusutan, depresiasi, aset, fixed asset',
                'related_resource' => 'FixedAssetResource',
                'order' => 1,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'cara-input-aset-baru'],
            [
                'documentation_category_id' => $asetCategory->id,
                'title' => 'Cara Input Aset Baru',
                'content' => '
<h2>Cara Menginput Aset Tetap Baru</h2>
<ol>
    <li>Masuk ke menu <strong>Aset Tetap</strong>.</li>
    <li>Klik <strong>Tambah Aset</strong>.</li>
    <li>Isi Tab <strong>Informasi Aset</strong> (Nama, Kategori, Kondisi).</li>
    <li>Isi Tab <strong>Informasi Pembelian</strong> (Tanggal, Harga, Nilai Sisa).</li>
    <li>Isi Tab <strong>Akuntansi</strong>:
        <ul>
            <li><strong>Akun Aset</strong>: Pilih akun Harta (misal: 1-1001 Peralatan Kantor).</li>
            <li><strong>Akun Akumulasi</strong>: Pilih akun kontra aset (misal: 1-1002 Akum. Peny. Peralatan).</li>
        </ul>
    </li>
    <li>Simpan.</li>
</ol>
                ',
                'is_published' => true,
                'keywords' => 'input aset, tambah aset',
                'related_resource' => 'FixedAssetResource',
                'order' => 2,
            ]
        );

        // ==========================================
        // 5. KATEGORI: KEUANGAN (FINANCE)
        // ==========================================
        $financeCategory = DocumentationCategory::updateOrCreate(
            ['slug' => 'keuangan-akuntansi'],
            [
                'name' => 'Keuangan & Akuntansi',
                'icon' => 'heroicon-o-banknotes',
                'order' => 5,
                'is_active' => true,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'memahami-bagan-akun'],
            [
                'documentation_category_id' => $financeCategory->id,
                'title' => 'Memahami Bagan Akun (COA)',
                'content' => '
<h2>Struktur Bagan Akun (Chart of Accounts)</h2>
<p>Bagan Akun adalah fondasi dari laporan keuangan sistem WOFINS.</p>
<ul>
    <li><strong>1-xxxx ASET</strong>: Kas, Bank, Piutang, Perlengkapan.</li>
    <li><strong>2-xxxx KEWAJIBAN</strong>: Utang Vendor, Uang Muka Klien (Pendapatan Diterima Dimuka).</li>
    <li><strong>3-xxxx EKUITAS</strong>: Modal Owner, Laba Ditahan.</li>
    <li><strong>4-xxxx PENDAPATAN</strong>: Pendapatan Jasa WO, Pendapatan Dekorasi.</li>
    <li><strong>5-xxxx BEBAN POKOK</strong>: Biaya Vendor (HPP Event).</li>
    <li><strong>6-xxxx BEBAN OPERASIONAL</strong>: Gaji Tim, Listrik, Sewa Kantor.</li>
</ul>
                ',
                'is_published' => true,
                'keywords' => 'coa, akun, chart of accounts',
                'related_resource' => 'ChartOfAccountResource',
                'order' => 1,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'laporan-keuangan-utama'],
            [
                'documentation_category_id' => $financeCategory->id,
                'title' => 'Laporan Keuangan Utama',
                'content' => '
<h2>Jenis Laporan Keuangan</h2>
<p>Sistem WOFINS menyediakan laporan keuangan standar akuntansi yang terbentuk otomatis dari transaksi harian.</p>

<h3>1. Neraca (Balance Sheet)</h3>
<p>Menampilkan posisi keuangan perusahaan pada saat tertentu.</p>
<ul>
    <li><strong>Aset</strong>: Apa yang kita miliki (Kas, Bank, Piutang, Inventaris).</li>
    <li><strong>Kewajiban</strong>: Apa yang kita hutangkan (Utang Vendor, DP Klien).</li>
    <li><strong>Ekuitas</strong>: Modal bersih pemilik.</li>
</ul>

<h3>2. Laba Rugi (Profit & Loss)</h3>
<p>Menampilkan kinerja perusahaan dalam periode tertentu.</p>
<ul>
    <li><strong>Pendapatan</strong>: Total omzet dari jasa WO dan dekorasi.</li>
    <li><strong>HPP (Cost of Revenue)</strong>: Biaya langsung event (Vendor catering, venue, dll).</li>
    <li><strong>Gross Profit</strong>: Pendapatan dikurangi HPP.</li>
    <li><strong>Biaya Operasional</strong>: Gaji, listrik, internet.</li>
    <li><strong>Net Profit</strong>: Laba bersih akhir.</li>
</ul>

<h3>3. Arus Kas (Cash Flow)</h3>
<p>Laporan detail keluar-masuk uang di setiap akun Kas & Bank. Berguna untuk memantau likuiditas perusahaan.</p>
                ',
                'is_published' => true,
                'keywords' => 'neraca, laba rugi, profit loss, balance sheet, cash flow',
                'related_resource' => 'FinancialReportResource',
                'order' => 2,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'pencatatan-pendapatan'],
            [
                'documentation_category_id' => $financeCategory->id,
                'title' => 'Pencatatan Pendapatan (Wedding & Lainnya)',
                'content' => '
<h2>Mencatat Pemasukan Uang</h2>
<p>Semua uang yang masuk ke rekening perusahaan harus dicatat agar laporan keuangan akurat.</p>

<h3>1. Pendapatan Wedding</h3>
<p>Biasanya tercatat <strong>otomatis</strong> saat Anda membuat Invoice di modul Order. Namun jika ada pemasukan tambahan di luar invoice resmi, gunakan menu ini.</p>

<h3>2. Pendapatan Lain-lain</h3>
<p>Gunakan menu <strong>Pendapatan Lain</strong> untuk mencatat pemasukan non-operasional, seperti:</p>
<ul>
    <li>Bunga Bank (Jasa Giro).</li>
    <li>Penjualan aset bekas (Barang bekas kantor).</li>
    <li>Komisi/Referral fee dari partner.</li>
</ul>
                ',
                'is_published' => true,
                'keywords' => 'pendapatan, income, revenue, uang masuk',
                'related_resource' => 'RevenueResource',
                'order' => 3,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'pencatatan-pengeluaran'],
            [
                'documentation_category_id' => $financeCategory->id,
                'title' => 'Pencatatan Pengeluaran (Expense)',
                'content' => '
<h2>Mengelola Biaya & Pengeluaran</h2>
<p>Pengeluaran dibagi menjadi tiga kategori utama untuk memudahkan analisis profitabilitas.</p>

<h3>1. Pengeluaran Wedding (HPP)</h3>
<p>Biaya yang dikeluarkan KHUSUS untuk proyek klien tertentu. Contoh: Bayar Catering, Sewa Gedung, Bayar MC.</p>
<div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mt-2">
    <strong>Penting:</strong> Selalu hubungkan pengeluaran ini dengan <strong>Nomor Order</strong> terkait agar Anda bisa melihat Laba/Rugi per Event.
</div>

<h3>2. Pengeluaran Operasional (Opex)</h3>
<p>Biaya rutin bulanan kantor yang tidak tergantung ada/tidaknya event.</p>
<ul>
    <li>Gaji Karyawan Tetap.</li>
    <li>Listrik, Air, Internet.</li>
    <li>Sewa Kantor.</li>
    <li>ATK & Keperluan Dapur.</li>
</ul>

<h3>3. Pengeluaran Lain</h3>
<p>Biaya non-rutin atau luar biasa, seperti: Biaya administrasi bank, Pajak, Sumbangan/Donasi.</p>
                ',
                'is_published' => true,
                'keywords' => 'pengeluaran, expense, biaya, hpp, operasional',
                'related_resource' => 'ExpenseResource',
                'order' => 4,
            ]
        );

        // ==========================================
        // 6. KATEGORI: ADMINISTRASI & LEGAL
        // ==========================================
        $adminCategory = DocumentationCategory::updateOrCreate(
            ['slug' => 'administrasi-legal'],
            [
                'name' => 'Administrasi & Legal',
                'icon' => 'heroicon-o-briefcase',
                'order' => 6,
                'is_active' => true,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'manajemen-perusahaan'],
            [
                'documentation_category_id' => $adminCategory->id,
                'title' => 'Manajemen Perusahaan (Companies)',
                'content' => '
<h2>Pengaturan Data Perusahaan</h2>
<p>Fitur <strong>Companies</strong> digunakan untuk menyimpan informasi legalitas perusahaan, alamat, dan logo yang akan tampil di Kop Surat atau Invoice.</p>

<h3>Cara Update Data Perusahaan:</h3>
<ol>
    <li>Masuk menu <strong>Companies</strong>.</li>
    <li>Pilih perusahaan yang ingin diedit (atau buat baru jika Multi-Company).</li>
    <li>Isi data lengkap:
        <ul>
            <li><strong>Nama Perusahaan</strong>.</li>
            <li><strong>Alamat Lengkap</strong> (akan muncul di footer invoice).</li>
            <li><strong>NPWP & Legalitas</strong>.</li>
            <li><strong>Logo</strong>: Upload logo dengan resolusi tinggi (PNG transparan disarankan).</li>
        </ul>
    </li>
    <li>Simpan perubahan.</li>
</ol>
                ',
                'is_published' => true,
                'keywords' => 'perusahaan, company, profil, alamat, logo',
                'related_resource' => 'CompanyResource',
                'order' => 1,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'arsip-dokumen-internal'],
            [
                'documentation_category_id' => $adminCategory->id,
                'title' => 'Arsip Dokumen Internal',
                'content' => '
<h2>Mengelola Arsip Dokumen</h2>
<p>Modul <strong>Documents</strong> berfungsi sebagai penyimpanan digital (filing cabinet) untuk surat-menyurat, kontrak kerja, dan dokumen penting lainnya.</p>

<h3>Langkah-langkah:</h3>
<ol>
    <li><strong>Buat Kategori Dokumen</strong>: Masuk ke menu <em>Document Categories</em>. Buat kategori seperti "Kontrak Klien", "Surat Masuk", "Surat Keluar", atau "Legalitas".</li>
    <li><strong>Upload Dokumen</strong>:
        <ul>
            <li>Masuk ke menu <strong>Documents</strong>.</li>
            <li>Klik <strong>New Document</strong>.</li>
            <li>Isi Judul Dokumen.</li>
            <li>Pilih Kategori.</li>
            <li>Upload file (PDF/JPG/DOCX).</li>
            <li>Tambahkan Keterangan jika perlu.</li>
        </ul>
    </li>
</ol>
<p>Dokumen yang diupload aman tersimpan dan bisa didownload kembali kapan saja oleh user yang memiliki akses.</p>
                ',
                'is_published' => true,
                'keywords' => 'dokumen, arsip, file, surat, kontrak',
                'related_resource' => 'DocumentResource',
                'order' => 2,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'standar-operasional-prosedur'],
            [
                'documentation_category_id' => $adminCategory->id,
                'title' => 'Standar Operasional Prosedur (SOP)',
                'content' => '
<h2>Pusat Informasi SOP</h2>
<p>Fitur <strong>SOP</strong> digunakan untuk mendistribusikan prosedur kerja standar kepada seluruh karyawan. Berbeda dengan dokumen biasa, SOP biasanya bersifat instruksional dan wajib dibaca.</p>

<h3>Mengelola SOP:</h3>
<ol>
    <li><strong>Kategori SOP</strong>: Kelompokkan SOP berdasarkan departemen (misal: "SOP Marketing", "SOP Operasional Event", "SOP Keuangan").</li>
    <li><strong>Input SOP</strong>:
        <ul>
            <li>Judul SOP (misal: "Prosedur Loading Barang H-1").</li>
            <li>Isi konten SOP (bisa berupa teks langsung atau file PDF lampiran).</li>
            <li>Tentukan siapa yang boleh mengakses (jika ada pembatasan role).</li>
        </ul>
    </li>
</ol>
<div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mt-4">
    <strong>Manfaat:</strong> Dengan adanya modul SOP terpusat, manajemen tidak perlu berulang kali mengirim file SOP via WhatsApp/Email. Karyawan cukup login ke sistem untuk melihat prosedur terbaru.
</div>
                ',
                'is_published' => true,
                'keywords' => 'sop, prosedur, standar, instruksi kerja',
                'related_resource' => 'SopResource',
                'order' => 3,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'manajemen-logo-mitra'],
            [
                'documentation_category_id' => $adminCategory->id,
                'title' => 'Manajemen Logo Mitra/Perusahaan',
                'content' => '
<h2>Logo Mitra & Klien</h2>
<p>Menu <strong>Logo Perusahaan</strong> digunakan untuk mengelola daftar logo partner, klien, atau media partner yang pernah bekerjasama.</p>

<h3>Fungsi:</h3>
<ul>
    <li>Menampilkan logo-logo ini di halaman depan website (Landing Page) bagian "Trusted By" atau "Our Partners".</li>
    <li>Sebagai database portofolio kerjasama.</li>
</ul>

<h3>Cara Upload:</h3>
<ol>
    <li>Masuk menu <strong>Logo Perusahaan</strong>.</li>
    <li>Upload gambar logo (pastikan dimensi seragam agar rapi di website).</li>
    <li>Isi Nama Mitra.</li>
    <li>Aktifkan status "Active" agar tampil di website.</li>
</ol>
                ',
                'is_published' => true,
                'keywords' => 'logo, mitra, partner, website, frontend',
                'related_resource' => 'CompanyLogoResource',
                'order' => 4,
            ]
        );

        // ==========================================
        // 7. KATEGORI: MANAJEMEN SDM & AKSES
        // ==========================================
        $hrCategory = DocumentationCategory::updateOrCreate(
            ['slug' => 'sdm-akses-user'],
            [
                'name' => 'Manajemen SDM & Akses',
                'icon' => 'heroicon-o-users',
                'order' => 7,
                'is_active' => true,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'manajemen-karyawan-tim'],
            [
                'documentation_category_id' => $hrCategory->id,
                'title' => 'Manajemen Data Karyawan & Tim',
                'content' => '
<h2>Database Karyawan</h2>
<p>Fitur ini digunakan untuk menyimpan biodata lengkap seluruh karyawan perusahaan, baik staf tetap maupun freelance.</p>

<h3>Data Tim vs Karyawan:</h3>
<ul>
    <li><strong>Data Tim</strong>: Mengelola pembagian divisi atau departemen (misal: Tim Marketing, Tim Produksi, Tim Finance).</li>
    <li><strong>Karyawan</strong>: Data individu personel (Nama, NIK, Kontak, Alamat, Tanggal Lahir).</li>
</ul>

<h3>Status Kepegawaian:</h3>
<p>Anda dapat mengelompokkan karyawan berdasarkan statusnya (Menu <strong>Status Karyawan</strong>):</p>
<ul>
    <li>Tetap (Permanent)</li>
    <li>Kontrak (PKWT)</li>
    <li>Magang (Internship)</li>
    <li>Freelance/Part-time</li>
</ul>
                ',
                'is_published' => true,
                'keywords' => 'karyawan, pegawai, staf, tim, divisi',
                'related_resource' => 'EmployeeResource',
                'order' => 1,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'penggajian-payroll'],
            [
                'documentation_category_id' => $hrCategory->id,
                'title' => 'Manajemen Penggajian (Payroll)',
                'content' => '
<h2>Proses Penggajian Bulanan</h2>
<p>Modul <strong>Payrolls</strong> memudahkan perhitungan dan pencatatan gaji karyawan setiap bulannya.</p>

<h3>Komponen Gaji:</h3>
<ul>
    <li><strong>Gaji Pokok</strong>: Upah dasar sesuai kontrak.</li>
    <li><strong>Tunjangan</strong>: Transport, Makan, Jabatan.</li>
    <li><strong>Potongan</strong>: BPJS, Kasbon, Keterlambatan.</li>
    <li><strong>Bonus/Insentif</strong>: Komisi penjualan (jika ada).</li>
</ul>

<h3>Alur Proses Payroll:</h3>
<ol>
    <li>Buat Periode Gaji (misal: Januari 2024).</li>
    <li>Generate Slip Gaji untuk seluruh karyawan aktif.</li>
    <li>Review total pengeluaran gaji.</li>
    <li>Set status ke "Paid" setelah transfer dilakukan.</li>
</ol>
<div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mt-2">
    <strong>Integrasi Keuangan:</strong> Saat status payroll berubah menjadi "Paid", sistem akan otomatis mencatatnya sebagai <strong>Pengeluaran Operasional</strong> di laporan keuangan.
</div>
                ',
                'is_published' => true,
                'keywords' => 'gaji, payroll, slip gaji, tunjangan, bonus',
                'related_resource' => 'PayrollResource',
                'order' => 2,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'manajemen-pengguna-role'],
            [
                'documentation_category_id' => $hrCategory->id,
                'title' => 'Manajemen Pengguna & Hak Akses',
                'content' => '
<h2>Keamanan & Hak Akses Sistem</h2>
<p>Tidak semua karyawan perlu mengakses seluruh menu. Fitur <strong>Pengguna (Users)</strong> dan <strong>Role</strong> berfungsi membatasi akses sesuai jabatan.</p>

<h3>1. Role (Peran)</h3>
<p>Tentukan template hak akses terlebih dahulu. Contoh:</p>
<ul>
    <li><strong>Super Admin</strong>: Akses penuh ke semua fitur.</li>
    <li><strong>Finance</strong>: Hanya akses modul Keuangan, Payroll, dan Order (View Only).</li>
    <li><strong>Sales</strong>: Hanya akses modul Prospect, Order, dan Katalog Produk.</li>
    <li><strong>HRD</strong>: Hanya akses modul SDM.</li>
</ul>

<h3>2. Pengguna (Users)</h3>
<p>Akun untuk login ke sistem.</p>
<ul>
    <li><strong>Nama & Email</strong>: Digunakan untuk login.</li>
    <li><strong>Password</strong>: Kata sandi akun.</li>
    <li><strong>Assign Role</strong>: Pilih Role yang sesuai untuk user ini.</li>
</ul>
<p>Jika karyawan resign, cukup non-aktifkan user-nya tanpa perlu menghapus data historisnya.</p>
                ',
                'is_published' => true,
                'keywords' => 'user, login, password, role, permission, akses',
                'related_resource' => 'UserResource',
                'order' => 3,
            ]
        );

        // ==========================================
        // 8. KATEGORI: MANAJEMEN CUTI (HRIS)
        // ==========================================
        $leaveCategory = DocumentationCategory::updateOrCreate(
            ['slug' => 'manajemen-cuti-hris'],
            [
                'name' => 'Manajemen Cuti (HRIS)',
                'icon' => 'heroicon-o-calendar-days',
                'order' => 8,
                'is_active' => true,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'pengajuan-cuti-karyawan'],
            [
                'documentation_category_id' => $leaveCategory->id,
                'title' => 'Pengajuan & Persetujuan Cuti',
                'content' => '
<h2>Sistem Cuti Online</h2>
<p>Modul ini memudahkan karyawan mengajukan cuti dan HRD mengelola saldo cuti tahunan.</p>

<h3>Alur Pengajuan Cuti:</h3>
<ol>
    <li>Karyawan login dan masuk menu <strong>Leave Requests</strong>.</li>
    <li>Klik <strong>New Request</strong>.</li>
    <li>Pilih <strong>Jenis Cuti</strong> (Tahunan, Sakit, Izin Khusus).</li>
    <li>Tentukan Tanggal Mulai dan Selesai.</li>
    <li>Upload Bukti (Surat Dokter) jika sakit.</li>
    <li>Submit.</li>
</ol>

<h3>Persetujuan (Approval):</h3>
<p>Atasan atau HRD akan menerima notifikasi dan bisa melakukan <strong>Approve</strong> atau <strong>Reject</strong>. Saldo cuti karyawan akan berkurang otomatis setelah disetujui.</p>
                ',
                'is_published' => true,
                'keywords' => 'cuti, leave, izin, sakit, approval',
                'related_resource' => 'LeaveRequestResource',
                'order' => 1,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'saldo-cuti-tipe-cuti'],
            [
                'documentation_category_id' => $leaveCategory->id,
                'title' => 'Mengatur Saldo & Tipe Cuti',
                'content' => '
<h2>Konfigurasi Master Cuti</h2>
<p>Sebelum karyawan bisa mengajukan cuti, HRD harus mengatur parameter dasarnya terlebih dahulu.</p>

<h3>1. Leave Types (Jenis Cuti)</h3>
<p>Buat kategori cuti, misal:</p>
<ul>
    <li><strong>Cuti Tahunan</strong>: Memotong saldo (Quota: 12 hari).</li>
    <li><strong>Cuti Sakit</strong>: Tidak memotong saldo (perlu bukti medis).</li>
    <li><strong>Cuti Menikah</strong>: Hak khusus (3 hari).</li>
</ul>

<h3>2. Leave Balances (Saldo Cuti)</h3>
<p>Setiap awal tahun atau saat karyawan baru masuk, HRD perlu menginput saldo awal mereka di menu ini.</p>
                ',
                'is_published' => true,
                'keywords' => 'saldo cuti, kuota, leave balance, leave type',
                'related_resource' => 'LeaveBalanceResource',
                'order' => 2,
            ]
        );

        // ==========================================
        // 9. UPDATE KEUANGAN: PIUTANG & JURNAL
        // ==========================================
        
        Documentation::updateOrCreate(
            ['slug' => 'manajemen-piutang'],
            [
                'documentation_category_id' => $financeCategory->id,
                'title' => 'Manajemen Piutang Usaha',
                'content' => '
<h2>Monitoring Piutang Klien</h2>
<p>Piutang tercatat otomatis saat Invoice diterbitkan namun belum lunas (Status: Unpaid/Partial). Namun Anda juga bisa mengelola piutang manual di menu <strong>Piutang</strong>.</p>

<h3>Pencatatan Pembayaran Piutang:</h3>
<p>Saat klien membayar cicilan:</p>
<ol>
    <li>Masuk menu <strong>Pembayaran Piutang</strong>.</li>
    <li>Klik <strong>New Payment</strong>.</li>
    <li>Pilih Klien/Invoice yang dibayar.</li>
    <li>Masukkan nominal pembayaran.</li>
    <li>Sistem akan mengupdate sisa tagihan secara otomatis.</li>
</ol>
                ',
                'is_published' => true,
                'keywords' => 'piutang, ar, account receivable, tagihan, invoice',
                'related_resource' => 'PiutangResource',
                'order' => 5,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'jurnal-umum-manual'],
            [
                'documentation_category_id' => $financeCategory->id,
                'title' => 'Jurnal Umum (General Journal)',
                'content' => '
<h2>Pencatatan Akuntansi Manual</h2>
<p>Fitur <strong>Journal Batches</strong> digunakan oleh akuntan untuk mencatat transaksi yang tidak tercover oleh modul otomatis (seperti penyesuaian akhir bulan, koreksi kesalahan, atau depresiasi manual).</p>

<h3>Cara Input Jurnal:</h3>
<ol>
    <li>Buat Batch Baru (misal: "Penyesuaian Jan 2024").</li>
    <li>Tambah Item Jurnal (Debit & Kredit).</li>
    <li>Pastikan Total Debit = Total Kredit (Balance).</li>
    <li>Post/Posting jurnal agar masuk ke Buku Besar.</li>
</ol>
                ',
                'is_published' => true,
                'keywords' => 'jurnal, journal entry, akuntansi, debit kredit',
                'related_resource' => 'JournalBatchResource',
                'order' => 6,
            ]
        );

        // ==========================================
        // 10. UPDATE ADMINISTRASI: SURAT & BLOG
        // ==========================================

        Documentation::updateOrCreate(
            ['slug' => 'nota-dinas-internal'],
            [
                'documentation_category_id' => $adminCategory->id,
                'title' => 'Nota Dinas & Memo Internal',
                'content' => '
<h2>Persuratan Resmi Kantor</h2>
<p>Fitur <strong>Nota Dinas</strong> digunakan untuk membuat surat perintah, memo, atau pengumuman resmi internal yang memiliki nomor surat otomatis.</p>

<h3>Fitur Utama:</h3>
<ul>
    <li><strong>Penomoran Otomatis</strong>: Format No. Surat terstandarisasi.</li>
    <li><strong>Approval</strong>: Tanda tangan digital atau persetujuan pimpinan.</li>
    <li><strong>Lampiran</strong>: Detail lampiran atau tembusan.</li>
</ul>
                ',
                'is_published' => true,
                'keywords' => 'surat, nota dinas, memo, surat keluar',
                'related_resource' => 'NotaDinasResource',
                'order' => 5,
            ]
        );

        Documentation::updateOrCreate(
            ['slug' => 'manajemen-blog-artikel'],
            [
                'documentation_category_id' => $adminCategory->id,
                'title' => 'Manajemen Blog Website',
                'content' => '
<h2>Publikasi Artikel & Berita</h2>
<p>Menu <strong>Blogs</strong> digunakan untuk mengelola konten artikel yang akan tampil di halaman "Blog" pada website utama (frontend).</p>

<h3>Tips Penulisan SEO:</h3>
<ul>
    <li>Gunakan Judul yang menarik.</li>
    <li>Isi "Slug" dengan kata kunci yang relevan.</li>
    <li>Upload "Featured Image" (Gambar Utama) dengan kualitas bagus.</li>
    <li>Gunakan kategori dan tags untuk memudahkan pencarian.</li>
</ul>
                ',
                'is_published' => true,
                'keywords' => 'blog, artikel, berita, seo, konten',
                'related_resource' => 'BlogResource',
                'order' => 6,
            ]
        );


    }
}
