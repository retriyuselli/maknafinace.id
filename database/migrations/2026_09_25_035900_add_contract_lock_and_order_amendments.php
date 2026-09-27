<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'contract_signed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('contract_signed_at')->nullable()->after('doc_kontrak');
            });
        }

        if (! Schema::hasColumn('order_products', 'unit_penambahan')) {
            Schema::table('order_products', function (Blueprint $table) {
                $table->unsignedBigInteger('unit_penambahan')->default(0)->after('unit_price');
                $table->unsignedBigInteger('unit_pengurangan')->default(0)->after('unit_penambahan');
            });
        }

        if (! Schema::hasTable('order_amendments')) {
            Schema::create('order_amendments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('reason');
                $table->json('before');
                $table->json('after');
                $table->json('diff')->nullable();
                $table->timestamps();
            });
        }

        $this->backfillContractSignedAt();
        $this->backfillItemSnapshots();
    }

    public function down(): void
    {
        Schema::dropIfExists('order_amendments');

        if (Schema::hasColumn('order_products', 'unit_penambahan')) {
            Schema::table('order_products', function (Blueprint $table) {
                $table->dropColumn(['unit_penambahan', 'unit_pengurangan']);
            });
        }

        if (Schema::hasColumn('orders', 'contract_signed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('contract_signed_at');
            });
        }
    }

    private function backfillContractSignedAt(): void
    {
        DB::table('orders')
            ->whereNull('contract_signed_at')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('doc_kontrak')->where('doc_kontrak', '!=', '');
                })->orWhere(function ($q) {
                    $q->whereNotNull('agreement_product')->where('agreement_product', '!=', '');
                })->orWhere('status', OrderStatus::Done->value);
            })
            ->orderBy('id')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    $signedAt = $order->closing_date
                        ?? $order->updated_at
                        ?? $order->created_at
                        ?? now();

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['contract_signed_at' => $signedAt]);
                }
            });
    }

    private function backfillItemSnapshots(): void
    {
        $orders = DB::table('orders')->select('id', 'penambahan', 'pengurangan')->orderBy('id')->get();
        $hasSoftDeletes = Schema::hasColumn('order_products', 'deleted_at');

        foreach ($orders as $order) {
            $itemsQuery = DB::table('order_products')->where('order_id', $order->id);
            if ($hasSoftDeletes) {
                $itemsQuery->whereNull('deleted_at');
            }

            $items = $itemsQuery->get(['id', 'quantity', 'unit_price']);

            if ($items->isEmpty()) {
                continue;
            }

            $totalQty = (int) $items->sum(fn ($item) => max(1, (int) $item->quantity));
            $unitPenambahan = (int) round(((int) ($order->penambahan ?? 0)) / max(1, $totalQty));
            $unitPengurangan = (int) round(((int) ($order->pengurangan ?? 0)) / max(1, $totalQty));

            foreach ($items as $item) {
                DB::table('order_products')
                    ->where('id', $item->id)
                    ->update([
                        'unit_penambahan' => $unitPenambahan,
                        'unit_pengurangan' => $unitPengurangan,
                    ]);
            }
        }
    }
};
