<?php

namespace App\Exports;

use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class VendorImportTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $categories = Category::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values();

        return [
            new VendorImportTemplateSheet($categories),
            new VendorImportCategorySheet($categories),
        ];
    }
}

class VendorImportTemplateSheet implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    /**
     * @param  Collection<int, string>  $categories
     */
    public function __construct(protected Collection $categories) {}

    public function title(): string
    {
        return 'Import Vendor';
    }

    public function headings(): array
    {
        return [
            'Nama Vendor',
            'Telepon',
            'Kategori',
            'PIC',
            'Alamat',
            'Status',
            'Vendor Induk',
            'Master',
            'Published',
            'Stok',
            'Deskripsi',
            'Harga Publish',
            'Harga Vendor',
            'Nama Bank',
            'Nomor Rekening',
            'Nama Pemilik Rekening',
        ];
    }

    public function array(): array
    {
        $exampleCategory = $this->categories->first() ?: '';

        return [
            [
                'Contoh Vendor Catering',
                '81234567890',
                $exampleCategory,
                'Budi Santoso',
                'Palembang',
                'vendor',
                '',
                'Tidak',
                'Ya',
                10,
                "Paket lengkap:\n- Dekorasi pelaminan\n- Bunga meja\n- Lighting\n\nTermasuk:\n1. Setup H-1\n2. Breakdown H+1",
                15000000,
                12000000,
                'BCA',
                '1234567890',
                'Budi Santoso',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastDataRow = 1000;
                $categoryCount = max($this->categories->count(), 1);

                $categoryValidation = $sheet->getCell('C2')->getDataValidation();
                $categoryValidation->setType(DataValidation::TYPE_LIST);
                $categoryValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $categoryValidation->setAllowBlank(false);
                $categoryValidation->setShowInputMessage(true);
                $categoryValidation->setShowErrorMessage(true);
                $categoryValidation->setShowDropDown(true);
                $categoryValidation->setErrorTitle('Kategori tidak valid');
                $categoryValidation->setError('Pilih kategori dari daftar yang sudah terdaftar. Kategori baru tidak boleh diketik manual.');
                $categoryValidation->setPromptTitle('Kategori');
                $categoryValidation->setPrompt('Pilih salah satu kategori yang sudah ada di sistem.');
                $categoryValidation->setFormula1("='Daftar Kategori'!\$A\$2:\$A\$".($categoryCount + 1));
                $sheet->setDataValidation("C2:C{$lastDataRow}", $categoryValidation);

                $statusValidation = $sheet->getCell('F2')->getDataValidation();
                $statusValidation->setType(DataValidation::TYPE_LIST);
                $statusValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $statusValidation->setAllowBlank(false);
                $statusValidation->setShowDropDown(true);
                $statusValidation->setShowErrorMessage(true);
                $statusValidation->setErrorTitle('Status tidak valid');
                $statusValidation->setError('Pilih vendor atau product.');
                $statusValidation->setFormula1('"vendor,product"');
                $sheet->setDataValidation("F2:F{$lastDataRow}", $statusValidation);

                $booleanValidation = $sheet->getCell('H2')->getDataValidation();
                $booleanValidation->setType(DataValidation::TYPE_LIST);
                $booleanValidation->setErrorStyle(DataValidation::STYLE_STOP);
                $booleanValidation->setShowDropDown(true);
                $booleanValidation->setFormula1('"Ya,Tidak"');
                $sheet->setDataValidation("H2:H{$lastDataRow}", clone $booleanValidation);
                $sheet->setDataValidation("I2:I{$lastDataRow}", clone $booleanValidation);

                $sheet->getStyle("K2:K{$lastDataRow}")->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension('K')->setWidth(48);
                $sheet->getComment('K1')->getText()->createTextRun(
                    "Gunakan Alt+Enter untuk baris baru.\nBullet: - item\nNumbered: 1. item\nSistem akan mengubahnya menjadi daftar di form."
                );

                $sheet->freezePane('A2');
                $sheet->getComment('C1')->getText()->createTextRun(
                    'Wajib dipilih dari daftar kategori yang sudah ada. Jangan mengetik kategori baru.'
                );
            },
        ];
    }
}

class VendorImportCategorySheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  Collection<int, string>  $categories
     */
    public function __construct(protected Collection $categories) {}

    public function title(): string
    {
        return 'Daftar Kategori';
    }

    public function headings(): array
    {
        return ['Kategori'];
    }

    public function array(): array
    {
        if ($this->categories->isEmpty()) {
            return [['(Belum ada kategori. Buat kategori di sistem sebelum import vendor.)']];
        }

        return $this->categories
            ->map(fn (string $name): array => [$name])
            ->all();
    }
}
