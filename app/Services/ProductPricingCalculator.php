<?php

namespace App\Services;

class ProductPricingCalculator
{
    public static function stripCurrency(mixed $val): int
    {
        if (is_string($val)) {
            $val = str_replace(['.', ','], '', $val);
        }

        return (int) ($val ?? 0);
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

