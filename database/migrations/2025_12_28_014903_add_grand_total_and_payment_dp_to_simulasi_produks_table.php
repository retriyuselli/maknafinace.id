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
        Schema::table('simulasi_produks', function (Blueprint $table) {
            $table->unsignedBigInteger('grand_total')->default(0)->after('pengurangan');
            $table->unsignedBigInteger('payment_dp_amount')->default(0)->after('grand_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simulasi_produks', function (Blueprint $table) {
            $table->dropColumn(['grand_total', 'payment_dp_amount']);
        });
    }
};
