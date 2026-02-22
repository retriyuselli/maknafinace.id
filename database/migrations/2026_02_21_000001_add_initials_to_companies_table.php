<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('inisial_wo')->nullable()->after('company_name');
            $table->string('inisial_kontak')->nullable()->after('inisial_wo');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['inisial_wo', 'inisial_kontak']);
        });
    }
};

