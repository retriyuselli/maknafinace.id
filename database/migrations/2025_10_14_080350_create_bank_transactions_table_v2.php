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
        // Check if table already exists
        if (Schema::hasTable('bank_transactions')) {
            return;
        }

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained()->onDelete('cascade');

            // Transaction details from bank statement
            $table->date('transaction_date');
            $table->date('value_date')->nullable();
            $table->text('description');
            $table->string('reference_number')->nullable();

            // Amounts
            $table->unsignedBigInteger('debit_amount')->default(0);
            $table->unsignedBigInteger('credit_amount')->default(0);
            $table->unsignedBigInteger('balance')->nullable();

            // Classification
            $table->enum('transaction_type', ['debit', 'credit']);
            $table->enum('category', [
                'transfer', 'deposit', 'withdrawal', 'fee',
                'interest', 'charge', 'correction', 'other',
            ])->default('other');

            // Matching information
            $table->boolean('is_matched')->default(false);
            $table->unsignedBigInteger('matched_with_transaction_id')->nullable();
            $table->unsignedInteger('matching_confidence')->nullable(); // 0 to 100
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance with shorter names
            $table->index(['bank_statement_id', 'transaction_date'], 'bt_stmt_date_idx');
            $table->index(['transaction_date', 'debit_amount'], 'bt_date_amt_idx');
            $table->index(['is_matched', 'matching_confidence'], 'bt_match_idx');
            $table->index('reference_number', 'bt_ref_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
