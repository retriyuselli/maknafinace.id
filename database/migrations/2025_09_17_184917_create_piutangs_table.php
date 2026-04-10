<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('piutangs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_piutang')->unique(); // PT/001/2025
            $table->enum('jenis_piutang', ['operasional', 'pribadi', 'bisnis']);

            // Relasi
            $table->foreignId('dibuat_oleh')->constrained('users');

            // Data Piutang
            $table->string('nama_debitur'); // Nama yang berhutang ke kita
            $table->text('keterangan'); // Keterangan piutang
            $table->unsignedBigInteger('jumlah_pokok'); // Pokok piutang
            $table->unsignedInteger('persentase_bunga')->default(0); // Bunga %
            $table->unsignedBigInteger('total_piutang'); // Total piutang
            $table->unsignedBigInteger('sudah_dibayar')->default(0); // Sudah dibayar
            $table->unsignedBigInteger('sisa_piutang'); // Sisa piutang

            // Tanggal
            $table->date('tanggal_piutang'); // Tanggal terjadi piutang
            $table->date('tanggal_jatuh_tempo'); // Jatuh tempo
            $table->date('tanggal_lunas')->nullable(); // Tanggal lunas

            // Status & Prioritas
            $table->enum('status', ['aktif', 'dibayar_sebagian', 'lunas', 'jatuh_tempo', 'dibatalkan']);
            $table->enum('prioritas', ['rendah', 'sedang', 'tinggi', 'mendesak']);

            // Lampiran & Catatan
            $table->json('lampiran')->nullable(); // File lampiran
            $table->text('catatan')->nullable(); // Catatan tambahan

            $table->timestamps();

            // Index untuk performa
            $table->index(['status', 'tanggal_jatuh_tempo']);
            $table->index(['dibuat_oleh', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('piutangs');
    }
};
