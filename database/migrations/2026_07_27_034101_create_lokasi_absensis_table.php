<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lokasi_absensis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->decimal('lintang', 10, 7);
            $table->decimal('bujur', 10, 7);
            $table->unsignedInteger('radius_meter')->default(150);
            $table->boolean('aktif')->default(true);
            $table->text('alamat')->nullable();
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lokasi_absensis');
    }
};
