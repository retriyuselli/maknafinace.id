<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_kerjas', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode', 32)->nullable()->unique();
            $table->boolean('default')->default(false);
            $table->boolean('aktif')->default(true);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('hari_jadwal_kerjas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_kerja_id')->constrained('jadwal_kerjas')->cascadeOnDelete();
            $table->unsignedTinyInteger('hari'); // 0=Minggu ... 6=Sabtu
            $table->boolean('hari_kerja')->default(true);
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->unsignedInteger('menit_istirahat')->default(60);
            $table->timestamps();

            $table->unique(['jadwal_kerja_id', 'hari']);
        });

        Schema::create('penugasan_jadwals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jadwal_kerja_id')->constrained('jadwal_kerjas')->cascadeOnDelete();
            $table->date('berlaku_dari');
            $table->date('berlaku_sampai')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'berlaku_dari', 'berlaku_sampai']);
        });

        Schema::create('koreksi_absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absensi_id')->constrained('absensis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('jam_masuk_diajukan')->nullable();
            $table->dateTime('jam_pulang_diajukan')->nullable();
            $table->text('alasan');
            $table->string('status', 32)->default('menunggu');
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('ditinjau_pada')->nullable();
            $table->text('catatan_peninjau')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['absensi_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('koreksi_absensis');
        Schema::dropIfExists('penugasan_jadwals');
        Schema::dropIfExists('hari_jadwal_kerjas');
        Schema::dropIfExists('jadwal_kerjas');
    }
};
