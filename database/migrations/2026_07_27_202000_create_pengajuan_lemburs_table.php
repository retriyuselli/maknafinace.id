<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_lemburs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('absensi_id')->nullable()->constrained('absensis')->nullOnDelete();
            $table->date('tanggal');
            $table->dateTime('mulai_pada');
            $table->dateTime('selesai_pada');
            $table->unsignedInteger('menit')->default(0);
            $table->text('alasan');
            $table->string('status', 32)->default('menunggu');
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('disetujui_pada')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['tanggal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_lemburs');
    }
};
