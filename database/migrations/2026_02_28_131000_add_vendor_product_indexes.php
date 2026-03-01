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

        // Vendors table indexes
        if (Schema::hasTable('vendors')) {
            $hasIdxCategory = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'vendors')->where('index_name', 'idx_vendors_category')->exists();
            $hasIdxStatus = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'vendors')->where('index_name', 'idx_vendors_status')->exists();
            $hasIdxIsMaster = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'vendors')->where('index_name', 'idx_vendors_is_master')->exists();
            $hasIdxCreatedAt = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'vendors')->where('index_name', 'idx_vendors_created_at')->exists();
            $hasIdxDeletedAt = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'vendors')->where('index_name', 'idx_vendors_deleted_at')->exists();

            Schema::table('vendors', function (Blueprint $table) use ($hasIdxCategory, $hasIdxStatus, $hasIdxIsMaster, $hasIdxCreatedAt, $hasIdxDeletedAt) {
                if (! $hasIdxCategory) {
                    $table->index(['category_id'], 'idx_vendors_category');
                }
                if (! $hasIdxStatus) {
                    $table->index(['status'], 'idx_vendors_status');
                }
                if (! $hasIdxIsMaster) {
                    $table->index(['is_master'], 'idx_vendors_is_master');
                }
                if (! $hasIdxCreatedAt) {
                    $table->index(['created_at'], 'idx_vendors_created_at');
                }
                if (! $hasIdxDeletedAt) {
                    $table->index(['deleted_at'], 'idx_vendors_deleted_at');
                }
            });
        }

        // Products table indexes
        if (Schema::hasTable('products')) {
            $hasIdxCategory = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_category')->exists();
            $hasIdxIsActive = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_is_active')->exists();
            $hasIdxIsApproved = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_is_approved')->exists();
            $hasIdxCreatedAt = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_created_at')->exists();
            $hasIdxDeletedAt = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_deleted_at')->exists();
            $hasIdxPrice = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_price')->exists();

            Schema::table('products', function (Blueprint $table) use ($hasIdxCategory, $hasIdxIsActive, $hasIdxIsApproved, $hasIdxCreatedAt, $hasIdxDeletedAt, $hasIdxPrice) {
                if (! $hasIdxCategory) {
                    $table->index(['category_id'], 'idx_products_category');
                }
                if (! $hasIdxIsActive) {
                    $table->index(['is_active'], 'idx_products_is_active');
                }
                if (! $hasIdxIsApproved) {
                    $table->index(['is_approved'], 'idx_products_is_approved');
                }
                if (! $hasIdxCreatedAt) {
                    $table->index(['created_at'], 'idx_products_created_at');
                }
                if (! $hasIdxDeletedAt) {
                    $table->index(['deleted_at'], 'idx_products_deleted_at');
                }
                if (! $hasIdxPrice) {
                    $table->index(['price'], 'idx_products_price');
                }
            });
        }
    }

    public function down(): void
    {
        $db = DB::getDatabaseName();

        if (Schema::hasTable('vendors')) {
            $hasIdxCategory = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'vendors')->where('index_name', 'idx_vendors_category')->exists();
            $hasIdxStatus = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'vendors')->where('index_name', 'idx_vendors_status')->exists();
            $hasIdxIsMaster = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'vendors')->where('index_name', 'idx_vendors_is_master')->exists();
            $hasIdxCreatedAt = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'vendors')->where('index_name', 'idx_vendors_created_at')->exists();
            $hasIdxDeletedAt = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'vendors')->where('index_name', 'idx_vendors_deleted_at')->exists();

            Schema::table('vendors', function (Blueprint $table) use ($hasIdxCategory, $hasIdxStatus, $hasIdxIsMaster, $hasIdxCreatedAt, $hasIdxDeletedAt) {
                if ($hasIdxCategory) {
                    $table->dropIndex('idx_vendors_category');
                }
                if ($hasIdxStatus) {
                    $table->dropIndex('idx_vendors_status');
                }
                if ($hasIdxIsMaster) {
                    $table->dropIndex('idx_vendors_is_master');
                }
                if ($hasIdxCreatedAt) {
                    $table->dropIndex('idx_vendors_created_at');
                }
                if ($hasIdxDeletedAt) {
                    $table->dropIndex('idx_vendors_deleted_at');
                }
            });
        }

        if (Schema::hasTable('products')) {
            $hasIdxCategory = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_category')->exists();
            $hasIdxIsActive = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_is_active')->exists();
            $hasIdxIsApproved = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_is_approved')->exists();
            $hasIdxCreatedAt = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_created_at')->exists();
            $hasIdxDeletedAt = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_deleted_at')->exists();
            $hasIdxPrice = DB::table('information_schema.statistics')->where('table_schema', $db)->where('table_name', 'products')->where('index_name', 'idx_products_price')->exists();

            Schema::table('products', function (Blueprint $table) use ($hasIdxCategory, $hasIdxIsActive, $hasIdxIsApproved, $hasIdxCreatedAt, $hasIdxDeletedAt, $hasIdxPrice) {
                if ($hasIdxCategory) {
                    $table->dropIndex('idx_products_category');
                }
                if ($hasIdxIsActive) {
                    $table->dropIndex('idx_products_is_active');
                }
                if ($hasIdxIsApproved) {
                    $table->dropIndex('idx_products_is_approved');
                }
                if ($hasIdxCreatedAt) {
                    $table->dropIndex('idx_products_created_at');
                }
                if ($hasIdxDeletedAt) {
                    $table->dropIndex('idx_products_deleted_at');
                }
                if ($hasIdxPrice) {
                    $table->dropIndex('idx_products_price');
                }
            });
        }
    }
};
