<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->toUnsignedBigInt('products', 'product_price', nullable: false, default: 0);
        $this->toUnsignedBigInt('products', 'pengurangan', nullable: false, default: 0);
        $this->toUnsignedBigInt('products', 'price', nullable: false, default: 0);
        $this->toUnsignedBigInt('products', 'penambahan', nullable: false, default: 0);
        $this->toUnsignedBigInt('products', 'penambahan_publish', nullable: false, default: 0);
        $this->toUnsignedBigInt('products', 'penambahan_vendor', nullable: false, default: 0);

        $this->toUnsignedBigInt('product_vendors', 'harga_publish', nullable: false, default: 0);
        $this->toUnsignedBigInt('product_vendors', 'harga_vendor', nullable: false, default: 0);
        $this->toUnsignedBigInt('product_vendors', 'price_public', nullable: false, default: 0);
        $this->toUnsignedBigInt('product_vendors', 'total_price', nullable: false, default: 0);

        $this->toUnsignedBigInt('order_products', 'unit_price', nullable: false, default: 0);

        $this->toUnsignedBigInt('product_pengurangans', 'amount', nullable: false, default: 0);

        $this->toUnsignedBigInt('product_penambahans', 'harga_publish', nullable: false, default: 0);
        $this->toUnsignedBigInt('product_penambahans', 'harga_vendor', nullable: false, default: 0);

        $this->toUnsignedBigInt('vendors', 'harga_publish', nullable: true, default: null);
        $this->toUnsignedBigInt('vendors', 'harga_vendor', nullable: true, default: null);
        $this->toBigInt('vendors', 'profit_amount', nullable: false, default: 0);
        $this->toInt('vendors', 'profit_margin', nullable: false, default: 0);

        $this->toUnsignedBigInt('vendor_price_histories', 'harga_publish', nullable: true, default: null);
        $this->toUnsignedBigInt('vendor_price_histories', 'harga_vendor', nullable: true, default: null);
        $this->toBigInt('vendor_price_histories', 'profit_amount', nullable: true, default: null);
        $this->toInt('vendor_price_histories', 'profit_margin', nullable: true, default: null);

        $this->toUnsignedBigInt('orders', 'total_price', nullable: false, default: 0);
        $this->toUnsignedBigInt('orders', 'paid_amount', nullable: true, default: 0);
        $this->toUnsignedBigInt('orders', 'promo', nullable: true, default: 0);
        $this->toUnsignedBigInt('orders', 'penambahan', nullable: true, default: 0);
        $this->toUnsignedBigInt('orders', 'pengurangan', nullable: true, default: 0);
        $this->toUnsignedBigInt('orders', 'change_amount', nullable: true, default: 0);
        $this->toUnsignedBigInt('orders', 'grand_total', nullable: true, default: null);

        $this->toUnsignedBigInt('simulasi_produks', 'total_price', nullable: false, default: 0);
        $this->toUnsignedBigInt('simulasi_produks', 'promo', nullable: false, default: 0);
        $this->toUnsignedBigInt('simulasi_produks', 'penambahan', nullable: false, default: 0);
        $this->toUnsignedBigInt('simulasi_produks', 'pengurangan', nullable: false, default: 0);
        $this->toUnsignedBigInt('simulasi_produks', 'grand_total', nullable: false, default: 0);
        $this->toUnsignedBigInt('simulasi_produks', 'payment_dp_amount', nullable: false, default: 0);
        $this->toUnsignedBigInt('simulasi_produks', 'total_simulation', nullable: false, default: 0);

        $this->toUnsignedBigInt('payment_methods', 'opening_balance', nullable: false, default: 0);

        $this->toUnsignedBigInt('bank_statements', 'opening_balance', nullable: true, default: 0);
        $this->toUnsignedBigInt('bank_statements', 'closing_balance', nullable: true, default: 0);
        $this->toUnsignedBigInt('bank_statements', 'tot_debit', nullable: true, default: 0);
        $this->toUnsignedBigInt('bank_statements', 'tot_credit', nullable: true, default: 0);
        $this->toUnsignedBigInt('bank_statements', 'total_debit_reconciliation', nullable: false, default: 0);
        $this->toUnsignedBigInt('bank_statements', 'total_credit_reconciliation', nullable: false, default: 0);

        $this->toUnsignedBigInt('bank_reconciliations', 'total_debit', nullable: false, default: 0);
        $this->toUnsignedBigInt('bank_reconciliations', 'total_credit', nullable: false, default: 0);

        $this->toUnsignedBigInt('bank_reconciliation_items', 'debit', nullable: false, default: 0);
        $this->toUnsignedBigInt('bank_reconciliation_items', 'credit', nullable: false, default: 0);

        $this->toUnsignedBigInt('bank_transactions', 'debit_amount', nullable: false, default: 0);
        $this->toUnsignedBigInt('bank_transactions', 'credit_amount', nullable: false, default: 0);
        $this->toUnsignedBigInt('bank_transactions', 'balance', nullable: true, default: null);
        $this->toUnsignedInt('bank_transactions', 'matching_confidence', nullable: true, default: null);

        $this->toUnsignedInt('data_pembayarans', 'match_confidence', nullable: true, default: null);
        $this->toUnsignedInt('pendapatan_lains', 'match_confidence', nullable: true, default: null);
        $this->toUnsignedInt('expenses', 'match_confidence', nullable: true, default: null);
        $this->toUnsignedInt('expense_ops', 'match_confidence', nullable: true, default: null);
        $this->toUnsignedInt('pengeluaran_lains', 'match_confidence', nullable: true, default: null);

        $this->toUnsignedBigInt('employees', 'salary', nullable: true, default: null);
        $this->toUnsignedBigInt('prospects', 'total_penawaran', nullable: true, default: null);
        $this->toUnsignedBigInt('data_pribadis', 'gaji', nullable: true, default: null);

        $this->toUnsignedBigInt('expenses', 'amount', nullable: false, default: 0);
        $this->toUnsignedBigInt('expense_ops', 'amount', nullable: false, default: 0);
        $this->toUnsignedBigInt('pendapatan_lains', 'nominal', nullable: false, default: 0);
        $this->toUnsignedBigInt('pengeluaran_lains', 'amount', nullable: false, default: 0);

        $this->toUnsignedBigInt('account_manager_targets', 'target_amount', nullable: false, default: 1000000000);
        $this->toUnsignedBigInt('account_manager_targets', 'achieved_amount', nullable: false, default: 0);

        $this->toUnsignedBigInt('payrolls', 'monthly_salary', nullable: false, default: 0);
        $this->toUnsignedBigInt('payrolls', 'annual_salary', nullable: true, default: null);
        $this->toUnsignedBigInt('payrolls', 'bonus', nullable: true, default: null);
        $this->toUnsignedBigInt('payrolls', 'gaji_pokok', nullable: true, default: null);
        $this->toUnsignedBigInt('payrolls', 'tunjangan', nullable: true, default: null);
        $this->toUnsignedBigInt('payrolls', 'pengurangan', nullable: true, default: null);

        $this->toUnsignedBigInt('piutangs', 'jumlah_pokok', nullable: false, default: 0);
        $this->toUnsignedInt('piutangs', 'persentase_bunga', nullable: false, default: 0);
        $this->toUnsignedBigInt('piutangs', 'total_piutang', nullable: false, default: 0);
        $this->toUnsignedBigInt('piutangs', 'sudah_dibayar', nullable: false, default: 0);
        $this->toUnsignedBigInt('piutangs', 'sisa_piutang', nullable: false, default: 0);

        $this->toUnsignedBigInt('pembayaran_piutangs', 'jumlah_pembayaran', nullable: false, default: 0);
        $this->toUnsignedBigInt('pembayaran_piutangs', 'jumlah_bunga', nullable: false, default: 0);
        $this->toUnsignedBigInt('pembayaran_piutangs', 'denda', nullable: false, default: 0);
        $this->toUnsignedBigInt('pembayaran_piutangs', 'total_pembayaran', nullable: false, default: 0);

        $this->toUnsignedBigInt('fixed_assets', 'purchase_price', nullable: false, default: 0);
        $this->toUnsignedBigInt('fixed_assets', 'accumulated_depreciation', nullable: false, default: 0);
        $this->toUnsignedBigInt('fixed_assets', 'salvage_value', nullable: false, default: 0);
        $this->toUnsignedBigInt('fixed_assets', 'current_book_value', nullable: false, default: 0);

        $this->toUnsignedBigInt('asset_depreciations', 'depreciation_amount', nullable: false, default: 0);
        $this->toUnsignedBigInt('asset_depreciations', 'accumulated_depreciation_before', nullable: false, default: 0);
        $this->toUnsignedBigInt('asset_depreciations', 'accumulated_depreciation_after', nullable: false, default: 0);
        $this->toUnsignedBigInt('asset_depreciations', 'book_value_before', nullable: false, default: 0);
        $this->toUnsignedBigInt('asset_depreciations', 'book_value_after', nullable: false, default: 0);

        $this->toUnsignedBigInt('journal_batches', 'total_debit', nullable: false, default: 0);
        $this->toUnsignedBigInt('journal_batches', 'total_credit', nullable: false, default: 0);

        $this->toUnsignedBigInt('journal_entries', 'debit_amount', nullable: false, default: 0);
        $this->toUnsignedBigInt('journal_entries', 'credit_amount', nullable: false, default: 0);

        $this->toUnsignedBigInt('nota_dinas_details', 'jumlah_transfer', nullable: false, default: 0);
        $this->toUnsignedBigInt('prospect_apps', 'sisa_bayar', nullable: true, default: null);
    }

    public function down(): void
    {
        return;
    }

    private function toUnsignedBigInt(string $table, string $column, bool $nullable, ?int $default): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("UPDATE `{$table}` SET `{$column}` = ROUND(`{$column}`) WHERE `{$column}` IS NOT NULL");

        if ($nullable) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NULL");

            return;
        }

        $defaultSql = $default ?? 0;
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NOT NULL DEFAULT {$defaultSql}");
    }

    private function toBigInt(string $table, string $column, bool $nullable, ?int $default): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("UPDATE `{$table}` SET `{$column}` = ROUND(`{$column}`) WHERE `{$column}` IS NOT NULL");

        if ($nullable) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT NULL");

            return;
        }

        $defaultSql = $default ?? 0;
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT NOT NULL DEFAULT {$defaultSql}");
    }

    private function toInt(string $table, string $column, bool $nullable, ?int $default): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("UPDATE `{$table}` SET `{$column}` = ROUND(`{$column}`) WHERE `{$column}` IS NOT NULL");

        if ($nullable) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` INT NULL");

            return;
        }

        $defaultSql = $default ?? 0;
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` INT NOT NULL DEFAULT {$defaultSql}");
    }

    private function toUnsignedInt(string $table, string $column, bool $nullable, ?int $default): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("UPDATE `{$table}` SET `{$column}` = ROUND(`{$column}`) WHERE `{$column}` IS NOT NULL");

        if ($nullable) {
            $defaultSql = $default ?? 0;
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` INT UNSIGNED NULL DEFAULT {$defaultSql}");

            return;
        }

        $defaultSql = $default ?? 0;
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` INT UNSIGNED NOT NULL DEFAULT {$defaultSql}");
    }
};
