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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_id')->constrained('prospects')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null');

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('number')->unique();
            $table->string('no_kontrak')->nullable();
            $table->string('doc_kontrak')->nullable();
            $table->integer('pax');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('total_price');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('promo')->default(0);
            $table->unsignedBigInteger('penambahan')->default(0);
            $table->unsignedBigInteger('pengurangan')->default(0);
            $table->unsignedBigInteger('change_amount')->default(0);
            $table->unsignedBigInteger('grand_total')->nullable();
            $table->json('bukti')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->date('closing_date')->nullable();
            $table->string('status');
            $table->string('kategori_transaksi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
