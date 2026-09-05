<x-filament-panels::page>
    @php
        $accountManager = $this->getAccountManager();
        $employee = $this->getRelatedEmployee();
        $phone = $accountManager?->phone_number ?: $employee?->phone;
        $joinDate = $employee?->date_of_join ?? $accountManager?->hire_date;
        $address = $accountManager?->address ?: $employee?->address;
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                <img
                    src="{{ $this->getPhotoUrl() }}"
                    alt="{{ $accountManager?->name ?? 'Account Manager' }}"
                    class="h-24 w-24 rounded-full object-cover ring-2 ring-blue-500/30"
                >
                <div class="flex-1 space-y-2">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-950 dark:text-white">{{ $accountManager?->name ?? 'Account Manager' }}</h2>
                        <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Account Manager</p>
                    </div>
                    <div class="grid grid-cols-1 gap-2 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-2">
                        <p>Email: {{ $accountManager?->email ?: '-' }}</p>
                        <p>Telepon: {{ $phone ? '+62'.ltrim($phone, '0') : '-' }}</p>
                        <p>Bergabung: {{ $joinDate?->format('d M Y') ?: '-' }}</p>
                        <p>Departemen: {{ $accountManager?->department ?: '-' }}</p>
                    </div>
                    @if ($address)
                        <p class="text-sm text-gray-600 dark:text-gray-300">Alamat: {{ $address }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->getStatCards() as $stat)
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold {{ $stat['valueClass'] }}">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $stat['helper'] }}</p>
                </div>
            @endforeach
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
