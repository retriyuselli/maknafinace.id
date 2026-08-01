<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_absensis', function (Blueprint $table) {
            $table->boolean('libur_sabtu')->default(true)->after('zona_waktu');
            $table->boolean('libur_minggu')->default(true)->after('libur_sabtu');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_absensis', function (Blueprint $table) {
            $table->dropColumn(['libur_sabtu', 'libur_minggu']);
        });
    }
};
