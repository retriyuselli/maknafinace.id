<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_pembayarans', fn (Blueprint $table) => $table->index('tgl_bayar', 'dp_report_date_idx'));
        Schema::table('pendapatan_lains', fn (Blueprint $table) => $table->index('tgl_bayar', 'pl_report_date_idx'));
        Schema::table('expenses', fn (Blueprint $table) => $table->index('date_expense', 'expenses_report_date_idx'));
        Schema::table('expense_ops', fn (Blueprint $table) => $table->index('date_expense', 'expense_ops_report_date_idx'));
        Schema::table('pengeluaran_lains', fn (Blueprint $table) => $table->index('date_expense', 'pengeluaran_report_date_idx'));
        Schema::table('orders', fn (Blueprint $table) => $table->index(['status', 'closing_date'], 'orders_status_closing_idx'));
    }

    public function down(): void
    {
        Schema::table('data_pembayarans', fn (Blueprint $table) => $table->dropIndex('dp_report_date_idx'));
        Schema::table('pendapatan_lains', fn (Blueprint $table) => $table->dropIndex('pl_report_date_idx'));
        Schema::table('expenses', fn (Blueprint $table) => $table->dropIndex('expenses_report_date_idx'));
        Schema::table('expense_ops', fn (Blueprint $table) => $table->dropIndex('expense_ops_report_date_idx'));
        Schema::table('pengeluaran_lains', fn (Blueprint $table) => $table->dropIndex('pengeluaran_report_date_idx'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropIndex('orders_status_closing_idx'));
    }
};
