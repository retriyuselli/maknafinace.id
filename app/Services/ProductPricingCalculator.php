<?php

namespace App\Services;

use App\Models\Product;

class ProductPricingCalculator
{
    public static function stripCurrency(mixed $val): int
    {
        if (is_int($val)) {
            return $val;
        }

        if (is_float($val)) {
            return (int) round($val);
        }

        if (! is_string($val)) {
            return (int) ($val ?? 0);
        }

        $val = trim($val);
        if ($val === '') {
            return 0;
        }

        // Plain integer string
        if (preg_match('/^-?\d+$/', $val)) {
            return (int) $val;
        }

        // Decimal with dot only (e.g. "4000000.00" from DB/casts) — jangan hapus titik
        if (preg_match('/^-?\d+\.\d+$/', $val)) {
            return (int) round((float) $val);
        }

        // Format Rupiah ID: 1.234.567,89 atau 1.234.567
        $normalized = str_replace('.', '', $val);
        $normalized = str_replace(',', '.', $normalized);

        return (int) round((float) $normalized);
    }

    public static function formatCurrency(int $value): string
    {
        return number_format($value, 0, '.', ',');
    }

    public static function normalizeVendorItems(array $items): array
    {
        foreach ($items as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            $quantity = (int) ($item['quantity'] ?? 1);
            $quantity = max(1, $quantity);

            $hargaPublish = self::stripCurrency($item['harga_publish'] ?? 0);
            $hargaVendor = self::stripCurrency($item['harga_vendor'] ?? 0);

            $pricePublic = self::stripCurrency($item['price_public'] ?? 0);
            if ($pricePublic <= 0 && $hargaPublish > 0) {
                $pricePublic = $hargaPublish * $quantity;
            }

            $totalPrice = self::stripCurrency($item['total_price'] ?? 0);
            if ($totalPrice <= 0 && $hargaVendor > 0) {
                $totalPrice = $hargaVendor * $quantity;
            }

            $items[$key]['quantity'] = $quantity;
            $items[$key]['harga_publish'] = $hargaPublish;
            $items[$key]['harga_vendor'] = $hargaVendor;
            $items[$key]['price_public'] = $pricePublic;
            $items[$key]['total_price'] = $totalPrice;
        }

        return $items;
    }

    public static function calculateVendorTotals(array $items): array
    {
        $normalized = self::normalizeVendorItems($items);

        $productPrice = 0;
        $vendorTotal = 0;

        foreach ($normalized as $item) {
            if (! is_array($item)) {
                continue;
            }

            $productPrice += self::stripCurrency($item['price_public'] ?? 0);
            $vendorTotal += self::stripCurrency($item['total_price'] ?? 0);
        }

        return [
            'items' => $normalized,
            'product_price' => $productPrice,
            'vendor_total' => $vendorTotal,
        ];
    }

    public static function calculateDiscountTotal(array $itemsPengurangan): int
    {
        $total = 0;

        foreach ($itemsPengurangan as $item) {
            if (! is_array($item)) {
                continue;
            }

            $total += self::stripCurrency($item['amount'] ?? 0);
        }

        return $total;
    }

    public static function normalizeAdditions(array $penambahanHarga): array
    {
        foreach ($penambahanHarga as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            $hargaPublish = self::stripCurrency($item['harga_publish'] ?? 0);
            $hargaVendor = self::stripCurrency($item['harga_vendor'] ?? 0);

            $penambahanHarga[$key]['harga_publish'] = $hargaPublish;
            $penambahanHarga[$key]['harga_vendor'] = $hargaVendor;
            $penambahanHarga[$key]['amount'] = $hargaPublish;
        }

        return $penambahanHarga;
    }

    public static function calculateAdditionTotals(array $penambahanHarga): array
    {
        $normalized = self::normalizeAdditions($penambahanHarga);

        $publish = 0;
        $vendor = 0;

        foreach ($normalized as $item) {
            if (! is_array($item)) {
                continue;
            }

            $publish += self::stripCurrency($item['harga_publish'] ?? 0);
            $vendor += self::stripCurrency($item['harga_vendor'] ?? 0);
        }

        return [
            'penambahanHarga' => $normalized,
            'penambahan_publish' => $publish,
            'penambahan_vendor' => $vendor,
        ];
    }

    public static function calculateFinalPrice(int $productPrice, int $pengurangan, int $penambahanPublish): int
    {
        return $productPrice - $pengurangan + $penambahanPublish;
    }

    /**
     * Kalkulasi harga untuk model Product (PDF / preview).
     * Satu sumber kebenaran dengan recalculateFormData() di Filament.
     *
     * @return array{
     *     total_public_price: int,
     *     total_vendor_price: int,
     *     total_discount_amount: int,
     *     total_addition_publish: int,
     *     total_addition_vendor: int,
     *     subtotal_publish: int,
     *     subtotal_vendor: int,
     *     final_publish: int,
     *     final_vendor: int,
     *     profit_and_loss: int
     * }
     */
    public static function calculateForProduct(Product $product): array
    {
        $items = $product->relationLoaded('items')
            ? $product->items
            : $product->items()->get();

        $pengurangans = $product->relationLoaded('pengurangans')
            ? $product->pengurangans
            : $product->pengurangans()->get();

        $penambahans = $product->relationLoaded('penambahanHarga')
            ? $product->penambahanHarga
            : $product->penambahanHarga()->get();

        $vendor = self::calculateVendorTotals(
            $items->map(fn ($item) => [
                'quantity' => $item->quantity,
                'harga_publish' => $item->harga_publish,
                'harga_vendor' => $item->harga_vendor,
                'price_public' => $item->price_public,
                'total_price' => $item->total_price,
            ])->all()
        );

        $discount = self::calculateDiscountTotal(
            $pengurangans->map(fn ($row) => [
                'amount' => $row->amount,
            ])->all()
        );

        $addition = self::calculateAdditionTotals(
            $penambahans->map(fn ($row) => [
                'harga_publish' => $row->harga_publish,
                'harga_vendor' => $row->harga_vendor,
            ])->all()
        );

        $totalPublicPrice = (int) $vendor['product_price'];
        $totalVendorPrice = (int) $vendor['vendor_total'];
        $totalAdditionPublish = (int) $addition['penambahan_publish'];
        $totalAdditionVendor = (int) $addition['penambahan_vendor'];

        $finalPublish = self::calculateFinalPrice($totalPublicPrice, $discount, $totalAdditionPublish);
        $finalVendor = self::calculateFinalPrice($totalVendorPrice, $discount, $totalAdditionVendor);

        return [
            'total_public_price' => $totalPublicPrice,
            'total_vendor_price' => $totalVendorPrice,
            'total_discount_amount' => $discount,
            'total_addition_publish' => $totalAdditionPublish,
            'total_addition_vendor' => $totalAdditionVendor,
            'subtotal_publish' => $totalPublicPrice + $totalAdditionPublish,
            'subtotal_vendor' => $totalVendorPrice + $totalAdditionVendor,
            'final_publish' => $finalPublish,
            'final_vendor' => $finalVendor,
            'profit_and_loss' => $finalPublish - $finalVendor,
        ];
    }

    public static function recalculateFormData(array $data): array
    {
        $vendor = self::calculateVendorTotals($data['items'] ?? []);
        $data['items'] = $vendor['items'];
        $data['product_price'] = $vendor['product_price'];

        $pengurangan = self::calculateDiscountTotal($data['itemsPengurangan'] ?? []);
        $data['pengurangan'] = $pengurangan;

        $addition = self::calculateAdditionTotals($data['penambahanHarga'] ?? []);
        $data['penambahanHarga'] = $addition['penambahanHarga'];
        $data['penambahan_publish'] = $addition['penambahan_publish'];
        $data['penambahan_vendor'] = $addition['penambahan_vendor'];

        $data['price'] = self::calculateFinalPrice(
            (int) $data['product_price'],
            (int) $data['pengurangan'],
            (int) $data['penambahan_publish'],
        );

        return $data;
    }
}

