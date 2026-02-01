<div class="overflow-x-auto">
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-4 py-3">Nama Acara</th>
                <th scope="col" class="px-4 py-3">Closing Date</th>
                <th scope="col" class="px-4 py-3">Lokasi</th>
                <th scope="col" class="px-4 py-3 text-right">Total Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $order->prospect->name_event ?? '-' }}
                        <div class="text-xs text-gray-500">{{ $order->number }}</div>
                    </td>
                    <td class="px-4 py-3">
                        {{ $order->closing_date ? \Carbon\Carbon::parse($order->closing_date)->format('d M Y') : '-' }}
                    </td>
                    <td class="px-4 py-3">
                        {{ $order->prospect->venue ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        Rp {{ number_format($order->total_price, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                    <td colspan="4" class="px-4 py-3 text-center">Tidak ada data order</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="font-semibold text-gray-900 dark:text-white">
                <th scope="row" colspan="3" class="px-4 py-3 text-right">Total</th>
                <td class="px-4 py-3 text-right">
                    Rp {{ number_format($orders->sum('total_price'), 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
