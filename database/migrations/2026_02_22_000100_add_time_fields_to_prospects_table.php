<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->time('time_lamaran')->nullable()->after('date_lamaran');
            $table->time('time_akad')->nullable()->after('date_akad');
            $table->time('time_resepsi')->nullable()->after('date_resepsi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn(['time_lamaran', 'time_akad', 'time_resepsi']);
        });
    }
};

