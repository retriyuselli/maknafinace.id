<?php

namespace App\Filament\Resources\Absensis\Pages;

use App\Exports\AbsensiExport;
use App\Filament\Resources\Absensis\AbsensiResource;
use App\Models\Absensi;
use App\Models\User;
use App\Services\AbsensiLaporanService;
use App\Services\AbsensiRekapService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListAbsensis extends ListRecords
{
    protected static string $resource = AbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->form($this->laporanFilterForm())
                ->action(function (array $data, AbsensiLaporanService $laporanService): BinaryFileResponse|StreamedResponse {
                    $records = $laporanService->koleksiDetail($this->normalizeFilters($data));

                    return Excel::download(
                        new AbsensiExport($records),
                        'laporan-absensi-'.now()->format('YmdHis').'.xlsx'
                    );
                }),
            Action::make('exportPdf')
                ->label('PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->form($this->laporanFilterForm())
                ->action(function (array $data) {
                    return redirect()->route('absensi.laporan.pdf', array_filter(
                        $this->normalizeFilters($data),
                        fn ($v) => $v !== null && $v !== ''
                    ));
                }),
            Action::make('rekapKemarin')
                ->label('Rekap Kemarin')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Jalankan rekap absensi kemarin?')
                ->modalDescription('Sistem akan menandai cuti, libur, libur mingguan, atau alfa untuk karyawan yang belum absen.')
                ->action(function (AbsensiRekapService $rekapService): void {
                    $tz = $rekapService->timezone();
                    $hasil = $rekapService->rekapTanggal(now($tz)->subDay());

                    Notification::make()
                        ->title('Rekap selesai')
                        ->body("Tanggal {$hasil['tanggal']}: {$hasil['dibuat']} dibuat, {$hasil['diubah']} diubah, {$hasil['dilewati']} dilewati.")
                        ->success()
                        ->send();
                }),
            Action::make('rekapHariIni')
                ->label('Rekap Hari Ini')
                ->icon('heroicon-o-calendar')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Jalankan rekap absensi hari ini?')
                ->modalDescription('Biasanya dijalankan di akhir hari. Karyawan yang sudah absen masuk tidak akan ditimpa.')
                ->action(function (AbsensiRekapService $rekapService): void {
                    $tz = $rekapService->timezone();
                    $hasil = $rekapService->rekapTanggal(now($tz));

                    Notification::make()
                        ->title('Rekap hari ini selesai')
                        ->body("Tanggal {$hasil['tanggal']}: {$hasil['dibuat']} dibuat, {$hasil['diubah']} diubah, {$hasil['dilewati']} dilewati.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected function laporanFilterForm(): array
    {
        return [
            Select::make('user_id')
                ->label('Karyawan')
                ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('bulan')
                ->label('Bulan')
                ->options([
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ])
                ->default(now()->month)
                ->nullable(),
            Select::make('tahun')
                ->label('Tahun')
                ->options(function () {
                    $years = [];
                    for ($year = now()->year - 2; $year <= now()->year + 1; $year++) {
                        $years[$year] = (string) $year;
                    }

                    return $years;
                })
                ->default(now()->year)
                ->nullable(),
            DatePicker::make('dari')
                ->label('Dari tanggal')
                ->native(false)
                ->helperText('Opsional jika tidak memakai bulan/tahun'),
            DatePicker::make('sampai')
                ->label('Sampai tanggal')
                ->native(false),
            Select::make('status')
                ->label('Status')
                ->options([
                    Absensi::STATUS_HADIR => 'Hadir',
                    Absensi::STATUS_TERLAMBAT => 'Terlambat',
                    Absensi::STATUS_ALFA => 'Alfa',
                    Absensi::STATUS_CUTI => 'Cuti',
                    Absensi::STATUS_LIBUR => 'Libur',
                    Absensi::STATUS_LIBUR_MINGGUAN => 'Libur Mingguan',
                    Absensi::STATUS_SETENGAH_HARI => 'Setengah Hari',
                    Absensi::STATUS_REMOTE => 'Remote',
                ])
                ->nullable(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{user_id?: int|null, bulan?: int|null, tahun?: int|null, dari?: string|null, sampai?: string|null, status?: string|null}
     */
    protected function normalizeFilters(array $data): array
    {
        return [
            'user_id' => isset($data['user_id']) ? (int) $data['user_id'] : null,
            'bulan' => isset($data['bulan']) ? (int) $data['bulan'] : null,
            'tahun' => isset($data['tahun']) ? (int) $data['tahun'] : null,
            'dari' => $data['dari'] ?? null,
            'sampai' => $data['sampai'] ?? null,
            'status' => $data['status'] ?? null,
        ];
    }
}
