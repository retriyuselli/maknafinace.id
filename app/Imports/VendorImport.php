<?php

namespace App\Imports;

use App\Enums\StatusVendor;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\ExcelRichText;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class VendorImport implements ToCollection, WithHeadingRow
{
    use Importable;

    protected int $created = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    /** @var array<int, string> */
    protected array $errors = [];

    public function collection(Collection $rows): void
    {
        $prepared = [];

        foreach ($rows as $index => $row) {
            $excelRow = $index + 2;
            $data = $this->normalizeRow($row->toArray());

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $name = $this->stringValue($data, ['nama_vendor', 'name']);
            $categoryName = $this->stringValue($data, ['kategori', 'category']);

            if (blank($name)) {
                $this->skipped++;
                $this->errors[] = "Baris {$excelRow}: Nama Vendor wajib diisi.";

                continue;
            }

            if (blank($categoryName)) {
                $this->skipped++;
                $this->errors[] = "Baris {$excelRow}: Kategori wajib diisi ({$name}).";

                continue;
            }

            $category = $this->findExistingCategory($categoryName);

            if (! $category) {
                $this->skipped++;
                $this->errors[] = "Baris {$excelRow}: Kategori \"{$categoryName}\" tidak ada di sistem. {$this->availableCategoriesHint()}";

                continue;
            }

            $prepared[] = [
                'excel_row' => $excelRow,
                'name' => $name,
                'phone' => $this->normalizePhone($this->stringValue($data, ['telepon', 'phone', 'no_telepon'])),
                'category' => $category,
                'pic_name' => $this->stringValue($data, ['pic', 'pic_name']),
                'address' => $this->stringValue($data, ['alamat', 'address']),
                'status' => $this->normalizeStatus($this->stringValue($data, ['status'])),
                'parent_name' => $this->stringValue($data, ['vendor_induk', 'parent', 'vendor_parent']),
                'is_master' => $this->normalizeBoolean($this->value($data, ['master', 'is_master'])),
                'is_published' => $this->normalizeBoolean($this->value($data, ['published', 'is_published'])),
                'stock' => $this->normalizeInteger($this->value($data, ['stok', 'stock']), 10),
                'description' => ExcelRichText::toHtml($this->stringValue($data, ['deskripsi', 'description'])),
                'harga_publish' => $this->normalizeMoney($this->value($data, ['harga_publish'])),
                'harga_vendor' => $this->normalizeMoney($this->value($data, ['harga_vendor'])),
                'bank_name' => $this->normalizeBankName($this->stringValue($data, ['nama_bank', 'bank_name'])),
                'bank_account' => $this->stringValue($data, ['nomor_rekening', 'bank_account', 'no_rekening']),
                'account_holder' => $this->stringValue($data, ['nama_pemilik_rekening', 'account_holder']),
            ];
        }

        foreach ($prepared as $item) {
            try {
                $this->upsertVendor($item, withParent: false);
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errors[] = "Baris {$item['excel_row']}: {$e->getMessage()}";
            }
        }

        foreach ($prepared as $item) {
            if (blank($item['parent_name'])) {
                continue;
            }

            try {
                $this->upsertVendor($item, withParent: true);
            } catch (\Throwable $e) {
                $this->errors[] = "Baris {$item['excel_row']}: Vendor Induk gagal dihubungkan — {$e->getMessage()}";
            }
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function upsertVendor(array $item, bool $withParent): Vendor
    {
        $category = $item['category'];

        $vendor = Vendor::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($item['name'])])
            ->first();

        $wasRecentlyCreated = false;

        if (! $vendor) {
            if ($withParent) {
                return Vendor::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($item['name'])])
                    ->firstOrFail();
            }

            $vendor = new Vendor;
            $vendor->slug = Vendor::generateUniqueSlug($item['name']);
            $wasRecentlyCreated = true;
        }

        $vendor->fill([
            'name' => $item['name'],
            'phone' => $item['phone'],
            'category_id' => $category->id,
            'pic_name' => $item['pic_name'],
            'address' => $item['address'],
            'status' => $item['status'],
            'is_master' => $item['is_master'],
            'is_published' => $item['is_published'],
            'stock' => $item['stock'],
            'description' => $item['description'],
            'harga_publish' => $item['harga_publish'],
            'harga_vendor' => $item['harga_vendor'],
            'bank_name' => $item['bank_name'],
            'bank_account' => $item['bank_account'],
            'account_holder' => $item['account_holder'],
        ]);

        if ($withParent) {
            $parent = Vendor::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($item['parent_name'])])
                ->where('id', '!=', $vendor->id)
                ->first();

            if (! $parent) {
                throw new \RuntimeException("Vendor Induk \"{$item['parent_name']}\" tidak ditemukan.");
            }

            $vendor->parent_id = $parent->id;
            $vendor->status = StatusVendor::PRODUCT;
        } elseif ($item['status'] !== StatusVendor::PRODUCT) {
            $vendor->parent_id = null;
        }

        $vendor->save();

        if (! $withParent) {
            if ($wasRecentlyCreated) {
                $this->created++;
            } else {
                $this->updated++;
            }
        }

        return $vendor;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[Str::slug((string) $key, '_')] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn ($value) => filled($value))->isEmpty();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    protected function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && filled($row[$key])) {
                return $row[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    protected function stringValue(array $row, array $keys): ?string
    {
        $value = $this->value($row, $keys);

        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    protected function findExistingCategory(string $name): ?Category
    {
        $normalized = mb_strtolower(trim($name));
        $slug = Str::slug($name);

        return Category::query()
            ->where(function ($query) use ($normalized, $slug) {
                $query->whereRaw('LOWER(name) = ?', [$normalized])
                    ->orWhere('slug', $slug);
            })
            ->first();
    }

    protected function availableCategoriesHint(): string
    {
        $names = Category::query()
            ->orderBy('name')
            ->limit(8)
            ->pluck('name')
            ->filter()
            ->all();

        if ($names === []) {
            return 'Belum ada kategori di sistem. Buat kategori terlebih dahulu.';
        }

        $hint = 'Gunakan kategori yang sudah ada, misalnya: '.implode(', ', $names);
        $remaining = Category::query()->count() - count($names);

        if ($remaining > 0) {
            $hint .= ', dan '.$remaining.' lainnya (lihat sheet Daftar Kategori pada template).';
        }

        return $hint;
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        return $digits !== '' ? $digits : null;
    }

    protected function normalizeStatus(?string $status): StatusVendor
    {
        $value = Str::lower(trim((string) $status));

        return match ($value) {
            'vendor' => StatusVendor::VENDOR,
            'master' => StatusVendor::MASTER,
            default => StatusVendor::PRODUCT,
        };
    }

    protected function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = Str::lower(trim((string) $value));

        return in_array($normalized, ['1', 'ya', 'yes', 'true', 'y'], true);
    }

    protected function normalizeInteger(mixed $value, int $default = 0): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return (int) preg_replace('/[^\d-]/', '', (string) $value);
    }

    protected function normalizeMoney(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) round((float) $value);
        }

        $digits = preg_replace('/[^\d]/', '', (string) $value);

        return (int) ($digits ?: 0);
    }

    protected function normalizeBankName(?string $name): ?string
    {
        if (blank($name)) {
            return null;
        }

        return Str::startsWith(Str::lower($name), 'bank ')
            ? trim(substr($name, 5))
            : $name;
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
