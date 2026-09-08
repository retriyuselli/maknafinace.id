<?php

namespace Database\Seeders;

use App\Models\ProspectApp;
use App\Services\ItemPurchaseCodeService;
use Illuminate\Database\Seeder;

class ItemPurchaseCodeSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ItemPurchaseCodeService::class);
        $created = 0;

        ProspectApp::query()
            ->orderBy('id')
            ->get()
            ->each(function (ProspectApp $app) use ($service, &$created): void {
                if (! $service->isPaid($app)) {
                    return;
                }

                $hadCode = $app->currentCode() !== null;
                $code = $service->syncFromProspectApp($app);
                if ($code && ! $hadCode) {
                    $created++;
                }
            });

        $this->command->info("✅ ItemPurchaseCodeSeeder: {$created} kode diterbitkan untuk aplikasi yang sudah lunas.");
    }
}
