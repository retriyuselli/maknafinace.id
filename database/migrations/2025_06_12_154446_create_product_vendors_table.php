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
        Schema::create('product_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');

            $table->foreignId('simulasi_produk_id')->nullable()->constrained('simulasi_produks')->onDelete('set null');

            $table->string('kontrak_kerjasama')->nullable();
            $table->text('description')->nullable();
            $table->integer('quantity');
            $table->unsignedBigInteger('harga_publish');
            $table->unsignedBigInteger('harga_vendor');
            $table->unsignedBigInteger('price_public'); // publish * qty
            $table->unsignedBigInteger('total_price'); // vendor * qty
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_vendors');
    }
};
