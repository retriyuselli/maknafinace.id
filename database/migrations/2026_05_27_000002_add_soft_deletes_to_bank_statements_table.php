<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom deleted_at ke tabel bank_statements.
 *
 * Kolom ini seharusnya sudah ada sejak migration awal (create_bank_statements_table
 * mendefinisikan $table->softDeletes()), namun tidak ada di DB karena migration
 * dijalankan sebelum softDeletes() ditambahkan.
 * Model BankStatement sekarang menggunakan trait SoftDeletes sehingga kolom ini wajib ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_statements', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
