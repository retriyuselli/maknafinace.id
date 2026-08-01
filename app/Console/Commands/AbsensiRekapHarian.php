<?php

namespace App\Console\Commands;

use App\Services\AbsensiRekapService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AbsensiRekapHarian extends Command
{
    protected $signature = 'absensi:rekap-harian
                            {tanggal? : Tanggal Y-m-d (default: kemarin menurut zona absensi)}
                            {--hari-ini : Rekap untuk hari ini, bukan kemarin}';

    protected $description = 'Rekap absensi otomatis: cuti, libur, libur mingguan, atau alfa';

    public function handle(AbsensiRekapService $rekapService): int
    {
        $tz = $rekapService->timezone();
        $now = Carbon::now($tz);

        if ($this->argument('tanggal')) {
            $tanggal = Carbon::parse($this->argument('tanggal'), $tz);
        } elseif ($this->option('hari-ini')) {
            $tanggal = $now->copy();
        } else {
            $tanggal = $now->copy()->subDay();
        }

        $this->info('Rekap absensi untuk '.$tanggal->toDateString().' ('.$tz.')...');

        $hasil = $rekapService->rekapTanggal($tanggal);

        $this->table(
            ['Tanggal', 'Diproses', 'Dibuat', 'Diubah', 'Dilewati'],
            [[
                $hasil['tanggal'],
                $hasil['diproses'],
                $hasil['dibuat'],
                $hasil['diubah'],
                $hasil['dilewati'],
            ]]
        );

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
