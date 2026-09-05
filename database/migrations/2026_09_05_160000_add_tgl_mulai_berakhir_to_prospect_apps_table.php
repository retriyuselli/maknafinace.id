<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prospect_apps', function (Blueprint $table) {
            $table->date('tgl_mulai')
                ->nullable()
                ->after('submitted_at')
                ->comment('Tanggal mulai aplikasi');

            $table->date('tgl_berakhir')
                ->nullable()
                ->after('tgl_mulai')
                ->comment('Tanggal berakhir aplikasi');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_apps', function (Blueprint $table) {
            $table->dropColumn(['tgl_mulai', 'tgl_berakhir']);
        });
    }
};
