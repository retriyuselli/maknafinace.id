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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->unsignedBigInteger('gaji_pokok')->nullable()->after('user_id');
            $table->unsignedBigInteger('tunjangan')->nullable()->after('gaji_pokok');
            $table->unsignedBigInteger('pengurangan')->nullable()->after('tunjangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['gaji_pokok', 'tunjangan', 'pengurangan']);
        });
    }
};
