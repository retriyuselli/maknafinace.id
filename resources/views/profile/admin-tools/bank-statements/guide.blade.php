@extends('profile.layout')

@section('profile-page-title', 'Panduan Bank Statement')
@section('profile-page-subtitle', 'Penjelasan istilah perbankan di menu Bank Statement')

@section('profile-content')
<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('profile.admin-tools.bank-statements') }}" class="text-sm font-semibold text-blue-700 hover:underline">
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <h3 class="text-lg font-semibold text-gray-900">Status Parsing</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Pending</div>
                <div class="text-gray-600 mt-1">Statement sudah terdaftar, tetapi proses parsing file mutasi belum dijalankan atau belum selesai.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Processing</div>
                <div class="text-gray-600 mt-1">File mutasi sedang diproses untuk dibaca dan diubah menjadi transaksi (hasil parsing).</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Parsed</div>
                <div class="text-gray-600 mt-1">Parsing selesai dan data transaksi hasil parsing sudah tersimpan.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Failed</div>
                <div class="text-gray-600 mt-1">Proses parsing gagal (format file tidak cocok, kolom tidak ditemukan, atau error saat import).</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <h3 class="text-lg font-semibold text-gray-900">Status Rekonsiliasi</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Uploaded</div>
                <div class="text-gray-600 mt-1">File/template rekonsiliasi sudah diunggah, tetapi proses import/matching belum dijalankan atau belum selesai.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Processing</div>
                <div class="text-gray-600 mt-1">Proses import item rekonsiliasi atau proses pencocokan (matching) sedang berjalan.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Completed</div>
                <div class="text-gray-600 mt-1">Proses import item rekonsiliasi selesai dan data siap dibandingkan dengan transaksi aplikasi.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Failed</div>
                <div class="text-gray-600 mt-1">Import/matching rekonsiliasi gagal.</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6">
        <h3 class="text-lg font-semibold text-gray-900">Istilah di Tabel</h3>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Rekening</div>
                <div class="text-gray-600 mt-1">Nama rekening/metode pembayaran yang dipakai sebagai sumber statement.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Periode</div>
                <div class="text-gray-600 mt-1">Rentang tanggal statement (awal–akhir) yang dicakup oleh file mutasi.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Debit</div>
                <div class="text-gray-600 mt-1">Total uang keluar pada periode statement.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Kredit</div>
                <div class="text-gray-600 mt-1">Total uang masuk pada periode statement.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Saldo Akhir</div>
                <div class="text-gray-600 mt-1">Saldo terakhir pada akhir periode statement (closing balance).</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">trx</div>
                <div class="text-gray-600 mt-1">Jumlah transaksi hasil parsing pada statement tersebut.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">item</div>
                <div class="text-gray-600 mt-1">Jumlah baris item rekonsiliasi yang ter-import untuk statement tersebut.</div>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="font-semibold text-gray-900">Kenapa debit/kredit bisa 0?</div>
                <div class="text-gray-600 mt-1">Biasanya karena statement masih pending/processing atau transaksi hasil parsing belum ada (trx = 0), sehingga total debit/kredit belum terisi.</div>
            </div>
        </div>
    </div>
</div>
@endsection

