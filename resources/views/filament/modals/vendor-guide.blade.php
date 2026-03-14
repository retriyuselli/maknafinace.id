<div class="space-y-6">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center space-x-2">
            <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600" />
            <h3 class="text-lg font-semibold text-blue-800">Panduan Vendor</h3>
        </div>
        <p class="text-sm text-blue-700 mt-2">
            Gunakan vendor induk untuk menyatukan data supplier yang sama, lalu turunkan menjadi vendor item (produk) untuk variasi paket/jenis/keterangan.
        </p>
    </div>

    <div class="space-y-3">
        <h4 class="text-base font-semibold text-gray-900">Konsep</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-lg border border-gray-200 p-4">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-building-storefront class="w-5 h-5 text-gray-700" />
                    <p class="font-semibold text-gray-900">Vendor Induk</p>
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    Satu entitas vendor untuk supplier yang sama (kontak, rekening, PIC, alamat). Vendor induk tidak memiliki vendor induk lain.
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 p-4">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-tag class="w-5 h-5 text-gray-700" />
                    <p class="font-semibold text-gray-900">Vendor Item (Product)</p>
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    Turunan dari vendor induk untuk membedakan paket/keterangan (mis. 500 pax, 1000 pax, varian, terms, dll).
                </p>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        <h4 class="text-base font-semibold text-gray-900">Cara Membuat</h4>
        <ol class="list-decimal pl-5 space-y-2 text-sm text-gray-700">
            <li>Buat vendor induk dulu: pilih <span class="font-semibold">Status = Vendor</span>, biarkan <span class="font-semibold">Vendor Induk</span> kosong.</li>
            <li>Buat vendor item: pilih <span class="font-semibold">Status = Product</span>, lalu pilih <span class="font-semibold">Vendor Induk</span> yang sesuai.</li>
            <li>Gunakan nama vendor item yang jelas, contoh: <span class="font-semibold">Golden Sriwijaya - 1000 pax</span>.</li>
        </ol>
    </div>

    <div class="space-y-3">
        <h4 class="text-base font-semibold text-gray-900">Catatan</h4>
        <ul class="list-disc pl-5 space-y-2 text-sm text-gray-700">
            <li>Data lama tetap aman. Jika tidak diisi, <span class="font-semibold">Vendor Induk</span> dianggap tidak ada.</li>
            <li>Jika vendor induk memiliki turunan, vendor induk tidak bisa dihapus sebelum semua turunannya dipindahkan/dihapus.</li>
        </ul>
    </div>
</div>

