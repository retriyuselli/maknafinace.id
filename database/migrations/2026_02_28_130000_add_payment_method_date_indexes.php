<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $db = DB::getDatabaseName();

        $existsExpenses = DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', 'expenses')
            ->where('index_name', 'idx_expenses_pm_date')
            ->exists();
        if (Schema::hasTable('expenses') && ! $existsExpenses) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->index(['payment_method_id', 'date_expense'], 'idx_expenses_pm_date');
            });
        }

        $existsPengeluaran = DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', 'pengeluaran_lains')
            ->where('index_name', 'idx_pengeluaran_pm_date')
            ->exists();
        if (Schema::hasTable('pengeluaran_lains') && ! $existsPengeluaran) {
            Schema::table('pengeluaran_lains', function (Blueprint $table) {
                $table->index(['payment_method_id', 'date_expense'], 'idx_pengeluaran_pm_date');
            });
        }

        $existsPembayaran = DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', 'data_pembayarans')
            ->where('index_name', 'idx_pembayaran_pm_date')
            ->exists();
        if (Schema::hasTable('data_pembayarans') && ! $existsPembayaran) {
            Schema::table('data_pembayarans', function (Blueprint $table) {
                $table->index(['payment_method_id', 'tgl_bayar'], 'idx_pembayaran_pm_date');
            });
        }
    }

    public function down(): void
    {
        $db = DB::getDatabaseName();

        $existsExpenses = DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', 'expenses')
            ->where('index_name', 'idx_expenses_pm_date')
            ->exists();
        if (Schema::hasTable('expenses') && $existsExpenses) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropIndex('idx_expenses_pm_date');
            });
        }

        $existsPengeluaran = DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', 'pengeluaran_lains')
            ->where('index_name', 'idx_pengeluaran_pm_date')
            ->exists();
        if (Schema::hasTable('pengeluaran_lains') && $existsPengeluaran) {
            Schema::table('pengeluaran_lains', function (Blueprint $table) {
                $table->dropIndex('idx_pengeluaran_pm_date');
            });
        }

        $existsPembayaran = DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', 'data_pembayarans')
            ->where('index_name', 'idx_pembayaran_pm_date')
            ->exists();
        if (Schema::hasTable('data_pembayarans') && $existsPembayaran) {
            Schema::table('data_pembayarans', function (Blueprint $table) {
                $table->dropIndex('idx_pembayaran_pm_date');
            });
        }
    }
};
