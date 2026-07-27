<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absensi_id')->constrained('absensis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('jenis', 32);
            $table->dateTime('waktu');
            $table->foreignId('lokasi_absensi_id')->nullable()->constrained('lokasi_absensis')->nullOnDelete();
            $table->decimal('lintang', 10, 7)->nullable();
            $table->decimal('bujur', 10, 7)->nullable();
            $table->float('akurasi_meter')->nullable();
            $table->unsignedInteger('jarak_ke_kantor_meter')->nullable();
            $table->boolean('dalam_radius')->default(false);
            $table->string('path_foto')->nullable();
            $table->string('nama_perangkat')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->json('meta')->nullable();
            $table->boolean('valid')->default(true);
            $table->string('alasan_tolak')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'waktu']);
            $table->index(['absensi_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_absensis');
    }
};
