@extends('profile.layout')

@section('profile-page-title', 'Detail Nota Dinas')
@section('profile-page-subtitle', $notaDinas->no_nd)

@section('profile-content')
<div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-sm font-semibold text-gray-900">{{ $notaDinas->no_nd }}</div>
            <div class="mt-1 text-xs text-gray-600">
                Tanggal: {{ optional($notaDinas->tanggal)->format('d-m-Y') ?? '-' }}
                <span class="mx-2">•</span>
                Status: {{ $notaDinas->status ?? '-' }}
            </div>
            <div class="mt-1 text-xs text-gray-600">
                Hal: {{ $notaDinas->hal ?? '-' }}
            </div>
        </div>

        <a href="{{ route('profile.admin-tools.nota-dinas') }}"
            class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-200 text-gray-700 text-sm font-semibold hover:bg-gray-50 transition">
            Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Pengirim</div>
            <div class="mt-1 text-sm font-semibold text-gray-900">{{ $notaDinas->pengirim?->name ?? '-' }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Penerima</div>
            <div class="mt-1 text-sm font-semibold text-gray-900">{{ $notaDinas->penerima?->name ?? '-' }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Detail Dibayar</div>
            <div class="mt-1 text-sm font-semibold text-gray-900">{{ number_format($detailsPaidCount) }} / {{ number_format($detailsCount) }}</div>
        </div>
        <div class="border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Total Jumlah Transfer</div>
            <div class="mt-1 text-sm font-semibold text-gray-900">Rp {{ number_format((float) $detailsSum, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach($paymentStageSummary as $stage => $c)
            <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs">
                {{ $stage !== null && $stage !== '' ? $stage : '-' }}: {{ number_format((int) $c) }}
            </span>
        @endforeach
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari keperluan / vendor / invoice"
            class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">

        <select name="status_invoice" class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-200">
            <option value="">Semua Status Invoice</option>
            @foreach(['belum_dibayar','menunggu','sudah_dibayar'] as $opt)
                <option value="{{ $opt }}" @selected($statusInvoice === $opt)>{{ str_replace('_', ' ', ucfirst($opt)) }}</option>
            @endforeach
        </select>

        <div></div>

        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Terapkan</button>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 border-b">
                    <th class="py-3 pr-4">Keperluan</th>
                    <th class="py-3 pr-4">Vendor</th>
                    <th class="py-3 pr-4">Event</th>
                    <th class="py-3 pr-4">Jenis</th>
                    <th class="py-3 pr-4">Stage</th>
                    <th class="py-3 pr-4">Jumlah</th>
                    <th class="py-3 pr-4">Status Invoice</th>
                    <th class="py-3 pr-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($details as $d)
                    <tr class="text-gray-800">
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ $d->keperluan }}</td>
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ $d->vendor?->name ?? '-' }}</td>
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ $d->order?->name ?? ($d->event ?? '-') }}</td>
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ $d->jenis_pengeluaran ?? '-' }}</td>
                        <td class="py-3 pr-4 text-xs text-gray-700">
                            @php
                                $stage = (string) ($d->payment_stage ?? '');
                                $isLegacyStage = $stage === 'down_payment';
                            @endphp
                            <span class="px-2 py-1 rounded-full {{ $isLegacyStage }} text-xs">
                                {{ $stage !== '' ? $stage : '-' }}
                            </span>
                        </td>
                        <td class="py-3 pr-4 text-xs text-gray-700">{{ number_format((float) $d->jumlah_transfer, 0, ',', '.') }}</td>
                        <td class="py-3 pr-4">
                            <span class="px-2 py-1 rounded-full bg-gray-100 text-gray-700 text-xs">{{ $d->status_invoice ?? '-' }}</span>
                        </td>
                        <td class="py-3 pr-4 text-xs text-gray-700">
                            <div>{{ $d->invoice_number ?? '-' }}</div>
                            @if($d->invoice_file)
                                <div class="text-[11px] text-gray-500 truncate max-w-[220px]">{{ $d->invoice_file }}</div>
                            @endif
                        </td>
                        <td class="py-3 pr-4">
                            @if($d->invoice_file)
                                <button type="button"
                                    data-invoice-url="{{ route('profile.admin-tools.nota-dinas-details.invoice.view', $d) }}"
                                    data-invoice-ext="{{ strtolower(pathinfo((string) $d->invoice_file, PATHINFO_EXTENSION)) }}"
                                    class="js-invoice-view inline-flex items-center px-3 py-1.5 rounded-lg bg-gray-900 text-white text-xs font-semibold hover:bg-gray-800 transition">
                                    View
                                </button>
                            @else
                                <span class="text-xs text-gray-500">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-6 text-center text-sm text-gray-500">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $details->links() }}
    </div>
</div>

<div id="invoice-preview-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative mx-auto mt-12 w-full max-w-5xl px-4">
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-4 flex items-center justify-between gap-3 border-b border-gray-100">
            </div>
            <div class="p-0">
                <iframe id="invoice-preview-frame" src="" class="w-full h-[75vh] bg-white"></iframe>
            </div>
        </div>
    </div>
</div>

<div id="invoice-not-found-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative mx-auto mt-24 w-full max-w-md px-4">
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-5">
                <div class="text-sm font-semibold text-gray-900">Invoice Tidak Ditemukan</div>
                <div id="invoice-not-found-message" class="mt-2 text-sm text-gray-700">File invoice tidak ditemukan atau tidak dapat diakses.</div>
            </div>
            <div class="px-5 py-4 bg-gray-50 flex items-center justify-end gap-2">
                <button type="button" id="invoice-not-found-close"
                    class="px-4 py-2 rounded-lg bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const previewModal = document.getElementById('invoice-preview-modal');
        const previewFrame = document.getElementById('invoice-preview-frame');
        const previewCloseBtn = document.getElementById('invoice-preview-close');
        const notFoundModal = document.getElementById('invoice-not-found-modal');
        const messageEl = document.getElementById('invoice-not-found-message');
        const closeBtn = document.getElementById('invoice-not-found-close');

        function openNotFoundModal(message) {
            if (messageEl) messageEl.textContent = message || 'File invoice tidak ditemukan atau tidak dapat diakses.';
            notFoundModal.classList.remove('hidden');
        }

        function closeNotFoundModal() {
            notFoundModal.classList.add('hidden');
        }

        function openPreviewModal(url) {
            if (!previewFrame) return;
            previewFrame.src = url;
            previewModal.classList.remove('hidden');
        }

        function closePreviewModal() {
            previewModal.classList.add('hidden');
            if (previewFrame) previewFrame.src = '';
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeNotFoundModal);
        }

        if (notFoundModal) {
            notFoundModal.addEventListener('click', function (e) {
                if (e.target === notFoundModal || (e.target && e.target.classList && e.target.classList.contains('bg-black/40'))) {
                    closeNotFoundModal();
                }
            });
        }

        if (previewCloseBtn) {
            previewCloseBtn.addEventListener('click', closePreviewModal);
        }

        if (previewModal) {
            previewModal.addEventListener('click', function (e) {
                if (e.target === previewModal || (e.target && e.target.classList && e.target.classList.contains('bg-black/40'))) {
                    closePreviewModal();
                }
            });
        }

        document.querySelectorAll('.js-invoice-view').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const url = btn.getAttribute('data-invoice-url');
                const ext = (btn.getAttribute('data-invoice-ext') || '').toLowerCase();
                if (!url) {
                    openNotFoundModal('File invoice tidak ditemukan atau tidak dapat diakses.');
                    return;
                }

                const allowed = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
                if (ext !== '' && !allowed.includes(ext)) {
                    openNotFoundModal('Preview invoice belum didukung untuk tipe file ini.');
                    return;
                }

                try {
                    const res = await fetch(url, { method: 'HEAD', redirect: 'manual', credentials: 'same-origin' });
                    if (res && res.ok) {
                        openPreviewModal(url);
                        return;
                    }
                } catch (e) {}

                openNotFoundModal('File invoice tidak ditemukan atau tidak dapat diakses.');
            });
        });
    })();
</script>
@endsection
