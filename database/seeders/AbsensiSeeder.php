<?php

namespace Database\Seeders;

use App\Models\HariJadwalKerja;
use App\Models\HariLibur;
use App\Models\JadwalKerja;
use App\Models\LokasiAbsensi;
use App\Models\PengaturanAbsensi;
use Illuminate\Database\Seeder;

class AbsensiSeeder extends Seeder
{
    public function run(): void
    {
        PengaturanAbsensi::query()->updateOrCreate(
            ['nama' => 'Aturan Kantor Default'],
            [
                'jam_masuk' => '09:00:00',
                'jam_pulang' => '18:00:00',
                'toleransi_terlambat_menit' => 15,
                'toleransi_pulang_cepat_menit' => 10,
                'wajib_pulang' => true,
                'wajib_lokasi' => true,
                'wajib_foto' => true,
                'tolak_jika_di_luar_radius' => true,
                'akurasi_gps_maksimal_meter' => 100,
                'ukuran_foto_maks_kb' => 2048,
                'zona_waktu' => 'Asia/Jakarta',
                'libur_sabtu' => true,
                'libur_minggu' => true,
                'aktif' => true,
                'catatan' => 'Pengaturan awal modul absensi. Sesuaikan koordinat lokasi kantor.',
            ]
        );

        LokasiAbsensi::query()->updateOrCreate(
            ['nama' => 'Kantor HQ'],
            [
                // Default map Palembang (Jl. Sintraman). Verifikasi di peta Filament sebelum produksi.
                'lintang' => -2.9909340,
                'bujur' => 104.7565540,
                'radius_meter' => 150,
                'aktif' => true,
                'alamat' => 'Jl. Sintraman Jaya I No. 2148, 20 Ilir D II, Kec. Kemuning, Kota Palembang, Sumatera Selatan 30137',
                'urutan' => 1,
            ]
        );

        $jadwal = JadwalKerja::query()->updateOrCreate(
            ['kode' => 'REG'],
            [
                'nama' => 'Kantor Reguler',
                'default' => true,
                'aktif' => true,
                'deskripsi' => 'Senin–Jumat 09:00–18:00, Sabtu/Minggu libur',
            ]
        );

        foreach (HariJadwalKerja::HARI_LABEL as $hari => $label) {
            $isWeekend = in_array($hari, [0, 6], true);

            HariJadwalKerja::query()->updateOrCreate(
                [
                    'jadwal_kerja_id' => $jadwal->id,
                    'hari' => $hari,
                ],
                [
                    'hari_kerja' => ! $isWeekend,
                    'jam_masuk' => $isWeekend ? null : '09:00:00',
                    'jam_pulang' => $isWeekend ? null : '18:00:00',
                    'menit_istirahat' => 60,
                ]
            );
        }

        $tahun = (int) now('Asia/Jakarta')->year;

        foreach ([
            ["{$tahun}-01-01", 'Tahun Baru'],
            ["{$tahun}-08-17", 'Hari Kemerdekaan RI'],
            ["{$tahun}-12-25", 'Hari Natal'],
        ] as [$tanggal, $nama]) {
            HariLibur::query()->updateOrCreate(
                ['tanggal' => $tanggal],
                [
                    'nama' => $nama,
                    'nasional' => true,
                    'tetap_masuk' => false,
                    'catatan' => 'Seed awal — sesuaikan daftar libur resmi tahun berjalan.',
                ]
            );
        }
    }
}
