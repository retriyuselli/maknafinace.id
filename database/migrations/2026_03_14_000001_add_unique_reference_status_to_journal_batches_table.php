<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('journal_batches')
            ->select('reference_type', 'reference_id', 'status', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('reference_type')
            ->whereNotNull('reference_id')
            ->groupBy('reference_type', 'reference_id', 'status')
            ->having('cnt', '>', 1)
            ->limit(10)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $examples = $duplicates
                ->map(fn ($row) => "{$row->reference_type}:{$row->reference_id} ({$row->status}) x{$row->cnt}")
                ->implode(', ');

            throw new RuntimeException("Cannot add unique constraint journal_batches_reference_status_unique due to duplicates: {$examples}");
        }

        Schema::table('journal_batches', function (Blueprint $table) {
            $table->dropIndex(['reference_type', 'reference_id']);
            $table->unique(['reference_type', 'reference_id', 'status'], 'journal_batches_reference_status_unique');
            $table->dropIndex(['batch_number']);
        });
    }

    public function down(): void
    {
        Schema::table('journal_batches', function (Blueprint $table) {
            $table->dropUnique('journal_batches_reference_status_unique');
            $table->index(['reference_type', 'reference_id']);
            $table->index(['batch_number']);
        });
    }
};
