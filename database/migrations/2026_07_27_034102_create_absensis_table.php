<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status', 32)->default('alfa');
            $table->dateTime('jam_masuk')->nullable();
            $table->dateTime('jam_pulang')->nullable();
            $table->unsignedInteger('menit_kerja')->nullable();
            $table->unsignedInteger('menit_terlambat')->default(0);
            $table->unsignedInteger('menit_pulang_cepat')->default(0);
            $table->string('sumber', 32)->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('leave_request_id')->nullable()->constrained('leave_requests')->nullOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'tanggal']);
            $table->index(['tanggal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
