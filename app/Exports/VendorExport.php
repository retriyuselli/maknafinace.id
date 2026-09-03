<?php

namespace App\Exports;

use App\Enums\StatusVendor;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class VendorExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    protected int $rowNumber = 0;

    public function query(): Builder
    {
        return Vendor::query()
            ->with(['category', 'parent'])
            ->withCount([
                'productVendors',
                'expenses',
                'notaDinasDetails',
                'productPenambahans',
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'No',
            'ID',
            'Nama Vendor',
            'Slug',
            'Kategori',
            'Vendor Induk',
            'PIC',
            'Telepon',
            'Alamat',
            'Status',
            'Master',
            'Published',
            'Stok',
            'Deskripsi',
            'Harga Publish',
            'Harga Vendor',
            'Profit',
            'Margin (%)',
            'Nama Bank',
            'Nomor Rekening',
            'Nama Pemilik Rekening',
            'Kontrak Kerjasama',
            'Status Penggunaan',
            'Jumlah Produk',
            'Jumlah Pengeluaran',
            'Jumlah Nota Dinas',
            'Jumlah Penambahan Produk',
            'Tanggal Dibuat',
            'Tanggal Diubah',
            'Tanggal Dihapus',
        ];
    }

    /**
     * @param  Vendor  $vendor
     */
    public function map($vendor): array
    {
        $this->rowNumber++;

        $status = $vendor->status;
        $statusLabel = $status instanceof StatusVendor
            ? $status->getLabel()
            : (string) $status;

        $margin = $vendor->profit_margin;
        $marginPercent = $margin === null
            ? ''
            : number_format(((float) $margin) / 100, 2, '.', ',');

        return [
            $this->rowNumber,
            $vendor->id,
            $vendor->name,
            $vendor->slug,
            $vendor->category?->name,
            $vendor->parent?->name,
            $vendor->pic_name,
            $vendor->phone ? '+62 '.$vendor->phone : '',
            $vendor->address,
            $statusLabel,
            $vendor->is_master ? 'Ya' : 'Tidak',
            $vendor->is_published ? 'Ya' : 'Tidak',
            $vendor->stock,
            $this->plainText($vendor->description),
            $vendor->harga_publish,
            $vendor->harga_vendor,
            $vendor->profit_amount,
            $marginPercent,
            $vendor->bank_name,
            $vendor->bank_account !== null ? (string) $vendor->bank_account : '',
            $vendor->account_holder,
            $vendor->kontrak_kerjasama,
            $vendor->usage_status,
            $vendor->product_vendors_count,
            $vendor->expenses_count,
            $vendor->nota_dinas_details_count,
            $vendor->product_penambahans_count,
            optional($vendor->created_at)?->format('Y-m-d H:i:s'),
            optional($vendor->updated_at)?->format('Y-m-d H:i:s'),
            optional($vendor->deleted_at)?->format('Y-m-d H:i:s'),
        ];
    }

    protected function plainText(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }
}
