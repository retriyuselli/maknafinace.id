<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_purchase_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_app_id')
                ->nullable()
                ->constrained('prospect_apps')
                ->nullOnDelete();
            $table->uuid('code')->unique();
            $table->string('company_name');
            $table->string('package')->nullable();
            $table->string('domain')->nullable();
            $table->string('activated_domain')->nullable();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->default('unused');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'ends_at']);
            $table->index('prospect_app_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_purchase_codes');
    }
};
