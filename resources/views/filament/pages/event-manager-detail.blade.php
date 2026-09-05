<x-filament-panels::page>
    @php
        $employee = $this->getEmployee();
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                <img
                    src="{{ $this->getPhotoUrl() }}"
                    alt="{{ $employee?->name ?? 'Event Manager' }}"
                    class="h-24 w-24 rounded-full object-cover ring-2 ring-emerald-500/30"
                >
                <div class="flex-1 space-y-2">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-950 dark:text-white">{{ $employee?->name ?? 'Event Manager' }}</h2>
                        <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Event Manager</p>
                    </div>
                    <div class="grid grid-cols-1 gap-2 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                        <p>Email: {{ $employee?->email ?: '-' }}</p>
                        <p>Telepon: {{ $employee?->phone ? '+62'.$employee->phone : '-' }}</p>
                        <p>Bergabung: {{ $employee?->date_of_join?->format('d M Y') ?: '-' }}</p>
                        <p>Instagram: {{ $employee?->instagram ? '@'.$employee->instagram : '-' }}</p>
                    </div>
                    @if ($employee?->address)
                        <p class="text-sm text-gray-600 dark:text-gray-300">Alamat: {{ $employee->address }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Event</p>
                <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($this->getTotalEvents()) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Nilai Proyek</p>
                <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">Rp {{ number_format($this->getTotalValue(), 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Terbayar</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($this->getTotalPaid(), 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Sisa Tagihan</p>
                <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">Rp {{ number_format($this->getOutstanding(), 0, ',', '.') }}</p>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
