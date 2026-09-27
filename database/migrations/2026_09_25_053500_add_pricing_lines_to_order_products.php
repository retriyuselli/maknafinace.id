<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            if (! Schema::hasColumn('order_products', 'penambahan_lines')) {
                $table->json('penambahan_lines')->nullable();
            }
            if (! Schema::hasColumn('order_products', 'pengurangan_lines')) {
                $table->json('pengurangan_lines')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_products', function (Blueprint $table) {
            if (Schema::hasColumn('order_products', 'pengurangan_lines')) {
                $table->dropColumn('pengurangan_lines');
            }
            if (Schema::hasColumn('order_products', 'penambahan_lines')) {
                $table->dropColumn('penambahan_lines');
            }
        });
    }
};
