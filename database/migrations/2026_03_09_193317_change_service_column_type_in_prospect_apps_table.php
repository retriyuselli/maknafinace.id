<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix invalid dates first
        DB::statement("UPDATE prospect_apps SET tgl_bayar = NULL WHERE CAST(tgl_bayar AS CHAR) = '0000-00-00'");
        
        Schema::table('prospect_apps', function (Blueprint $table) {
            $table->string('service', 255)->nullable()->change();
            $table->date('tgl_bayar')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prospect_apps', function (Blueprint $table) {
            // Reverting to ENUM is tricky if data contains non-enum values, so we might just leave it as string or try to revert if possible.
            // But usually making it string is a one-way safe upgrade.
            // $table->enum('service', ['basic', 'standard', 'premium', 'enterprise'])->nullable()->change();
        });
    }
};
