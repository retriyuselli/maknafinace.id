<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50 text-blue-600">
                    <x-heroicon-o-users class="w-6 h-6" />
                </span>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Prospek Bulan Ini</h1>
                    <div class="text-sm text-gray-600">Periode: {{ $from }} s/d {{ $until }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Acara</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Venue</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Manajer Akun</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Telepon</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($prospects as $p)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                                    <a class="text-primary-600 hover:underline"
                                       href="{{ \App\Filament\Resources\Prospects\ProspectResource::getUrl('edit', ['record' => $p]) }}">
                                        {{ $p->name_event ?? '-' }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $p->venue ?? '-' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ $p->user?->name ?? '-' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ $p->phone ? '+62 '.$p->phone : '-' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ optional($p->created_at)->format('d-m-Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-600">Tidak ada data untuk periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>

