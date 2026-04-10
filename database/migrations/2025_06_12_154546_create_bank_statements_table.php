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
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->onDelete('cascade');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('file_path');
            $table->string('source_type')
                ->nullable();
            $table->string('status')
                ->default('pending'); //
            $table->string('branch')
                ->nullable(); // Cabang pembuka rekening
            $table->unsignedBigInteger('opening_balance')
                ->nullable(); // Saldo awal rekening
            $table->unsignedBigInteger('closing_balance')
                ->nullable(); // Saldo akhir rekening
            $table->integer('no_of_debit')
                ->nullable(); // Total number of debit transactions
            $table->unsignedBigInteger('tot_debit')
                ->nullable(); // Total debit amount
            $table->integer('no_of_credit')
                ->nullable(); // Total number of credit transactions
            $table->unsignedBigInteger('tot_credit')
                ->nullable(); // Total cred
            $table->dateTime('uploaded_at')
                ->nullable(); //
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
