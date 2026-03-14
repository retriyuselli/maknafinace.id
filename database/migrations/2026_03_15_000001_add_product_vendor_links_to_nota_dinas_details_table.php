<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nota_dinas_details')) {
            return;
        }

        Schema::table('nota_dinas_details', function (Blueprint $table) {
            if (! Schema::hasColumn('nota_dinas_details', 'order_product_id')) {
                $table->foreignId('order_product_id')
                    ->nullable()
                    ->after('order_id')
                    ->constrained('order_products')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('nota_dinas_details', 'product_vendor_id')) {
                $table->foreignId('product_vendor_id')
                    ->nullable()
                    ->after('order_product_id')
                    ->constrained('product_vendors')
                    ->nullOnDelete();
            }

            $table->index(['order_id', 'product_vendor_id'], 'idx_nd_details_order_product_vendor');
            $table->index(['order_id', 'product_vendor_id', 'payment_stage'], 'idx_nd_details_order_product_vendor_stage');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('nota_dinas_details')) {
            return;
        }

        Schema::table('nota_dinas_details', function (Blueprint $table) {
            $table->dropIndex('idx_nd_details_order_product_vendor');
            $table->dropIndex('idx_nd_details_order_product_vendor_stage');

            if (Schema::hasColumn('nota_dinas_details', 'product_vendor_id')) {
                $table->dropConstrainedForeignId('product_vendor_id');
            }

            if (Schema::hasColumn('nota_dinas_details', 'order_product_id')) {
                $table->dropConstrainedForeignId('order_product_id');
            }
        });
    }
};
