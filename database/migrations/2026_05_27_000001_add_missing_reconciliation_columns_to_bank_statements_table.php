<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom-kolom rekonsiliasi yang hilang ke tabel bank_statements.
 *
 * Kolom-kolom ini sebelumnya hanya ada di tabel bank_reconciliations (legacy),
 * namun sudah di-fillable di model BankStatement tanpa ada migrasinya.
 * Migration ini melengkapi skema agar konsisten dengan model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_statements', 'title')) {
                $table->string('title')->nullable()->after('last_edited_by');
            }

            if (! Schema::hasColumn('bank_statements', 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            if (! Schema::hasColumn('bank_statements', 'reconciliation_file')) {
                $table->string('reconciliation_file')->nullable()->after('description');
            }

            if (! Schema::hasColumn('bank_statements', 'uploaded_by')) {
                $table->foreignId('uploaded_by')
                    ->nullable()
                    ->after('reconciliation_file')
                    ->constrained('users')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_statements', function (Blueprint $table) {
            // Drop foreign key sebelum drop column
            if (Schema::hasColumn('bank_statements', 'uploaded_by')) {
                $table->dropForeign(['uploaded_by']);
                $table->dropColumn('uploaded_by');
            }

            foreach (['reconciliation_file', 'description', 'title'] as $column) {
                if (Schema::hasColumn('bank_statements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
