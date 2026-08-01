<?php

namespace App\Exports;

use App\Models\Absensi;
use App\Services\AbsensiLaporanService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AbsensiExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(public Collection $records) {}

    public function collection()
    {
        return $this->records;
    }

    /**
     * @param  Absensi  $absensi
     */
    public function map($absensi): array
    {
        $labels = app(AbsensiLaporanService::class);

        return [
            $absensi->user?->name,
            $absensi->user?->email,
            optional($absensi->tanggal)?->format('Y-m-d'),
            $labels->labelStatus($absensi->status),
            optional($absensi->jam_masuk)?->format('Y-m-d H:i'),
            optional($absensi->jam_pulang)?->format('Y-m-d H:i'),
            $absensi->menit_kerja,
            $absensi->menit_terlambat,
            $absensi->menit_pulang_cepat,
            $absensi->sumber,
            $absensi->catatan,
        ];
    }

    public function headings(): array
    {
        return [
            'Karyawan',
            'Email',
            'Tanggal',
            'Status',
            'Jam Masuk',
            'Jam Pulang',
            'Menit Kerja',
            'Menit Terlambat',
            'Menit Pulang Cepat',
            'Sumber',
            'Catatan',
        ];
    }
}
