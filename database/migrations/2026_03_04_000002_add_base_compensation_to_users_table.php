<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'gaji_pokok_base')) {
                $table->integer('gaji_pokok_base')->default(0)->after('department');
            }
            if (! Schema::hasColumn('users', 'tunjangan_base')) {
                $table->integer('tunjangan_base')->default(0)->after('gaji_pokok_base');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tunjangan_base')) {
                $table->dropColumn('tunjangan_base');
            }
            if (Schema::hasColumn('users', 'gaji_pokok_base')) {
                $table->dropColumn('gaji_pokok_base');
            }
        });
    }
};
