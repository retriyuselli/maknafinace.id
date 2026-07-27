<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_absensis', function (Blueprint $table) {
            $table->unsignedInteger('denda_terlambat_per_menit')->default(0)->after('libur_minggu');
            $table->unsignedInteger('tarif_lembur_per_menit')->default(0)->after('denda_terlambat_per_menit');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_absensis', function (Blueprint $table) {
            $table->dropColumn(['denda_terlambat_per_menit', 'tarif_lembur_per_menit']);
        });
    }
};
