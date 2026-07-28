<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6">
            <h2 class="text-xl font-semibold text-gray-900">{{ $detail['title'] ?? 'Detail Sumber Data' }}</h2>
            <p class="text-sm text-gray-600 mt-1">Periode: {{ $detail['period_label'] ?? '-' }}</p>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="p-4 rounded-lg bg-blue-50 border border-blue-200">
                    <p class="text-sm text-blue-700">Total Nilai</p>
                    <p class="text-2xl font-bold text-blue-900">
                        Rp {{ number_format((int) ($detail['total'] ?? 0), 0, ',', '.') }}
                    </p>
                </div>
                <div class="p-4 rounded-lg bg-gray-50 border border-gray-200">
                    <p class="text-sm text-gray-600">Jumlah Baris Ditampilkan</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ number_format((int) ($detail['row_count'] ?? 0), 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Rincian Data</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referensi</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Rekening</th>
                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pemilik Rekening</th>
                            <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse(($detail['rows'] ?? []) as $row)
                            <tr>
                                <td class="px-4 sm:px-6 py-4 text-sm text-gray-700">{{ $row['date'] ?? '-' }}</td>
                                <td class="px-4 sm:px-6 py-4 text-sm text-gray-900">{{ $row['description'] ?? '-' }}</td>
                                <td class="px-4 sm:px-6 py-4 text-sm text-gray-600">{{ $row['reference'] ?? '-' }}</td>
                                <td class="px-4 sm:px-6 py-4 text-sm text-gray-600">{{ $row['account_number'] ?? '-' }}</td>
                                <td class="px-4 sm:px-6 py-4 text-sm text-gray-600">{{ $row['account_holder'] ?? '-' }}</td>
                                <td class="px-4 sm:px-6 py-4 text-sm text-right font-semibold text-gray-900">
                                    Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 sm:px-6 py-8 text-center text-sm text-gray-500">Tidak ada data pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
