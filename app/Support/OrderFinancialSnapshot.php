<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OrderFinancialSnapshot
{
    /**
     * @return array{
     *     total_price: int,
     *     promo: int,
     *     penambahan: int,
     *     pengurangan: int,
     *     grand_total: int,
     *     penambahan_lines: list<array{id: int|null, name: string, amount: int}>,
     *     pengurangan_lines: list<array{id: int|null, name: string, amount: int}>,
     *     items: list<array{product_id: int|null, quantity: int, unit_price: int, unit_penambahan: int, unit_pengurangan: int, penambahan_lines: list<array{id: int|null, name: string, amount: int}>, pengurangan_lines: list<array{id: int|null, name: string, amount: int}>}>
     * }
     */
    public static function fromOrder(Order $order): array
    {
        $order->loadMissing('items');

        $penambahanLines = [];
        $penguranganLines = [];

        $items = $order->items->map(function ($item) use (&$penambahanLines, &$penguranganLines) {
            $adds = self::normalizeLines($item->penambahan_lines ?? []);
            $cuts = self::normalizeLines($item->pengurangan_lines ?? []);
            array_push($penambahanLines, ...$adds);
            array_push($penguranganLines, ...$cuts);

            return [
                'product_id' => $item->product_id ? (int) $item->product_id : null,
                'quantity' => (int) ($item->quantity ?? 0),
                'unit_price' => (int) ($item->unit_price ?? 0),
                'unit_penambahan' => (int) ($item->unit_penambahan ?? 0),
                'unit_pengurangan' => (int) ($item->unit_pengurangan ?? 0),
                'penambahan_lines' => $adds,
                'pengurangan_lines' => $cuts,
            ];
        })->values()->all();

        return [
            'total_price' => (int) ($order->getRawOriginal('total_price') ?? $order->total_price ?? 0),
            'promo' => (int) ($order->promo ?? 0),
            'penambahan' => (int) ($order->penambahan ?? 0),
            'pengurangan' => (int) ($order->pengurangan ?? 0),
            'grand_total' => (int) ($order->getRawOriginal('grand_total') ?? $order->attributes['grand_total'] ?? 0),
            'penambahan_lines' => self::mergeLines($penambahanLines),
            'pengurangan_lines' => self::mergeLines($penguranganLines),
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $formData
     * @return array{
     *     total_price: int,
     *     promo: int,
     *     penambahan: int,
     *     pengurangan: int,
     *     grand_total: int,
     *     penambahan_lines: list<array{id: int|null, name: string, amount: int}>,
     *     pengurangan_lines: list<array{id: int|null, name: string, amount: int}>,
     *     items: list<array{product_id: int|null, quantity: int, unit_price: int, unit_penambahan: int, unit_pengurangan: int, penambahan_lines: list<array{id: int|null, name: string, amount: int}>, pengurangan_lines: list<array{id: int|null, name: string, amount: int}>}>
     * }
     */
    public static function fromFormData(array $formData): array
    {
        $penambahanLines = [];
        $penguranganLines = [];

        $items = collect(Arr::get($formData, 'items', []))
            ->filter(fn ($item) => is_array($item) && ! empty($item['product_id']))
            ->map(function ($item) use (&$penambahanLines, &$penguranganLines) {
                $adds = self::normalizeLines($item['penambahan_lines'] ?? []);
                $cuts = self::normalizeLines($item['pengurangan_lines'] ?? []);
                array_push($penambahanLines, ...$adds);
                array_push($penguranganLines, ...$cuts);

                return [
                    'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                    'quantity' => (int) ($item['quantity'] ?? 0),
                    'unit_price' => self::money($item['unit_price'] ?? 0),
                    'unit_penambahan' => self::money($item['unit_penambahan'] ?? 0),
                    'unit_pengurangan' => self::money($item['unit_pengurangan'] ?? 0),
                    'penambahan_lines' => $adds,
                    'pengurangan_lines' => $cuts,
                ];
            })
            ->values()
            ->all();

        return [
            'total_price' => self::money($formData['total_price'] ?? 0),
            'promo' => self::money($formData['promo'] ?? 0),
            'penambahan' => self::money($formData['penambahan'] ?? 0),
            'pengurangan' => self::money($formData['pengurangan'] ?? 0),
            'grand_total' => self::money($formData['grand_total'] ?? 0),
            'penambahan_lines' => self::mergeLines($penambahanLines),
            'pengurangan_lines' => self::mergeLines($penguranganLines),
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public static function hasChanged(array $before, array $after): bool
    {
        return json_encode(self::canonical($before)) !== json_encode(self::canonical($after));
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, mixed>
     */
    public static function diff(array $before, array $after): array
    {
        $diff = [];

        foreach (['total_price', 'promo', 'penambahan', 'pengurangan', 'grand_total'] as $field) {
            $b = (int) ($before[$field] ?? 0);
            $a = (int) ($after[$field] ?? 0);
            if ($b !== $a) {
                $diff[$field] = [
                    'before' => $b,
                    'after' => $a,
                ];
            }
        }

        $beforeAdds = self::mergeLines($before['penambahan_lines'] ?? []);
        $afterAdds = self::mergeLines($after['penambahan_lines'] ?? []);
        if ($beforeAdds !== $afterAdds) {
            $diff['penambahan_lines'] = [
                'before' => $beforeAdds,
                'after' => $afterAdds,
            ];
        }

        $beforeCuts = self::mergeLines($before['pengurangan_lines'] ?? []);
        $afterCuts = self::mergeLines($after['pengurangan_lines'] ?? []);
        if ($beforeCuts !== $afterCuts) {
            $diff['pengurangan_lines'] = [
                'before' => $beforeCuts,
                'after' => $afterCuts,
            ];
        }

        $beforeItems = self::normalizeItems($before['items'] ?? []);
        $afterItems = self::normalizeItems($after['items'] ?? []);

        if ($beforeItems !== $afterItems) {
            $diff['items'] = [
                'before' => array_values($beforeItems),
                'after' => array_values($afterItems),
            ];
        }

        return $diff;
    }

    /**
     * @param  mixed  $items
     * @return array<string, array{product_id: int|null, quantity: int, unit_price: int, unit_penambahan: int, unit_pengurangan: int}>
     */
    protected static function normalizeItems(mixed $items): array
    {
        return collect(is_array($items) ? $items : [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['product_id']))
            ->mapWithKeys(function (array $item) {
                $productId = (int) $item['product_id'];

                return [
                    (string) $productId => [
                        'product_id' => $productId,
                        'quantity' => (int) ($item['quantity'] ?? 0),
                        'unit_price' => self::money($item['unit_price'] ?? 0),
                        'unit_penambahan' => self::money($item['unit_penambahan'] ?? 0),
                        'unit_pengurangan' => self::money($item['unit_pengurangan'] ?? 0),
                    ],
                ];
            })
            ->sortKeys()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    protected static function canonical(array $snapshot): array
    {
        return [
            'total_price' => (int) ($snapshot['total_price'] ?? 0),
            'promo' => (int) ($snapshot['promo'] ?? 0),
            'penambahan' => (int) ($snapshot['penambahan'] ?? 0),
            'pengurangan' => (int) ($snapshot['pengurangan'] ?? 0),
            'grand_total' => (int) ($snapshot['grand_total'] ?? 0),
            'penambahan_lines' => self::mergeLines($snapshot['penambahan_lines'] ?? []),
            'pengurangan_lines' => self::mergeLines($snapshot['pengurangan_lines'] ?? []),
            'items' => self::normalizeItems($snapshot['items'] ?? []),
        ];
    }

    /**
     * @param  mixed  $lines
     * @return list<array{id: int|null, name: string, amount: int}>
     */
    public static function normalizeLines(mixed $lines): array
    {
        return collect(is_array($lines) ? $lines : [])
            ->filter(fn ($line) => is_array($line))
            ->map(function (array $line) {
                $name = trim(strip_tags((string) ($line['name'] ?? $line['description'] ?? '')));
                $name = preg_replace('/\s+/u', ' ', $name ?? '') ?: 'Tanpa nama';
                $amount = self::money($line['amount'] ?? $line['harga_publish'] ?? 0);

                return [
                    'id' => isset($line['id']) && is_numeric($line['id']) ? (int) $line['id'] : null,
                    'name' => Str::limit($name, 80),
                    'amount' => $amount,
                ];
            })
            ->filter(fn (array $line) => $line['amount'] !== 0)
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $lines
     * @return list<array{id: int|null, name: string, amount: int}>
     */
    public static function mergeLines(mixed $lines): array
    {
        $merged = [];

        foreach (self::normalizeLines($lines) as $line) {
            $key = $line['id'] !== null
                ? 'id:'.$line['id']
                : 'name:'.mb_strtolower($line['name']);

            if (! isset($merged[$key])) {
                $merged[$key] = $line;

                continue;
            }

            $merged[$key]['amount'] += $line['amount'];
        }

        return collect($merged)
            ->sortBy(fn (array $line) => mb_strtolower($line['name']))
            ->values()
            ->all();
    }

    public static function money(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }
}
