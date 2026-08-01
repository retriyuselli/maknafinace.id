<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_absensis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->time('jam_masuk')->default('09:00:00');
            $table->time('jam_pulang')->default('18:00:00');
            $table->unsignedInteger('toleransi_terlambat_menit')->default(15);
            $table->unsignedInteger('toleransi_pulang_cepat_menit')->default(10);
            $table->boolean('wajib_pulang')->default(true);
            $table->boolean('wajib_lokasi')->default(true);
            $table->boolean('wajib_foto')->default(true);
            $table->boolean('tolak_jika_di_luar_radius')->default(true);
            $table->unsignedInteger('akurasi_gps_maksimal_meter')->nullable()->default(100);
            $table->unsignedInteger('ukuran_foto_maks_kb')->default(2048);
            $table->string('zona_waktu')->default('Asia/Jakarta');
            $table->boolean('aktif')->default(true);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_absensis');
    }
};
