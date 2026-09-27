<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_pengurangans')) {
            return;
        }

        if (Schema::hasColumn('product_pengurangans', 'publish_only')) {
            return;
        }

        Schema::table('product_pengurangans', function (Blueprint $table) {
            $table->boolean('publish_only')->default(false)->after('amount');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_pengurangans') || ! Schema::hasColumn('product_pengurangans', 'publish_only')) {
            return;
        }

        Schema::table('product_pengurangans', function (Blueprint $table) {
            $table->dropColumn('publish_only');
        });
    }
};
