<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FixedAssetSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Fixed Assets...');

        $assetAccounts = [
            'EQUIPMENT' => ChartOfAccount::where('account_code', '1520')->first(),
            'VEHICLE' => ChartOfAccount::where('account_code', '1530')->first(),
            'COMPUTER' => ChartOfAccount::where('account_code', '1540')->first(),
        ];

        $accumAccounts = [
            'EQUIPMENT' => ChartOfAccount::where('account_code', '1620')->first(),
            'VEHICLE' => ChartOfAccount::where('account_code', '1630')->first(),
            'COMPUTER' => ChartOfAccount::where('account_code', '1640')->first(),
        ];

        if (! $assetAccounts['EQUIPMENT'] || ! $assetAccounts['VEHICLE'] || ! $assetAccounts['COMPUTER']) {
            $this->command->error('Fixed Asset accounts not found. Please run FixedAssetChartOfAccountsSeeder first.');

            return;
        }

        $data = [
            [
                'category' => 'EQUIPMENT',
                'asset_name' => 'Peralatan Kantor - Set Meja & Kursi',
                'purchase_date' => Carbon::parse('2024-01-15'),
                'purchase_price' => 25000000,
                'salvage_value' => 3000000,
                'useful_life_years' => 5,
                'useful_life_months' => 0,
                'location' => 'Kantor Pusat Jakarta',
                'condition' => 'GOOD',
                'supplier' => 'PT Furni Jaya',
                'invoice_number' => 'INV-FA-001',
                'warranty_expiry' => Carbon::parse('2026-01-15'),
                'notes' => null,
            ],
            [
                'category' => 'VEHICLE',
                'asset_name' => 'Kendaraan Operasional - MPV',
                'purchase_date' => Carbon::parse('2024-03-20'),
                'purchase_price' => 320000000,
                'salvage_value' => 50000000,
                'useful_life_years' => 8,
                'useful_life_months' => 0,
                'location' => 'Garasi Kantor',
                'condition' => 'GOOD',
                'supplier' => 'PT Mobil Nusantara',
                'invoice_number' => 'INV-FA-002',
                'warranty_expiry' => Carbon::parse('2027-03-20'),
                'notes' => null,
            ],
            [
                'category' => 'COMPUTER',
                'asset_name' => 'Komputer Editing - Workstation',
                'purchase_date' => Carbon::parse('2024-05-05'),
                'purchase_price' => 45000000,
                'salvage_value' => 5000000,
                'useful_life_years' => 4,
                'useful_life_months' => 0,
                'location' => 'Studio Editing',
                'condition' => 'EXCELLENT',
                'supplier' => 'PT Tekno Mandiri',
                'invoice_number' => 'INV-FA-003',
                'warranty_expiry' => Carbon::parse('2026-05-05'),
                'notes' => null,
            ],
            [
                'category' => 'COMPUTER',
                'asset_name' => 'Laptop MacBook Pro M3',
                'purchase_date' => Carbon::parse('2024-06-10'),
                'purchase_price' => 35000000,
                'salvage_value' => 8000000,
                'useful_life_years' => 3,
                'useful_life_months' => 0,
                'location' => 'Ruang Direktur',
                'condition' => 'EXCELLENT',
                'supplier' => 'iBox Official',
                'invoice_number' => 'INV-FA-004',
                'warranty_expiry' => Carbon::parse('2025-06-10'),
                'notes' => 'Aset untuk Direktur Utama',
            ],
            [
                'category' => 'EQUIPMENT',
                'asset_name' => 'Mesin Fotokopi Canon',
                'purchase_date' => Carbon::parse('2024-02-01'),
                'purchase_price' => 18000000,
                'salvage_value' => 2000000,
                'useful_life_years' => 5,
                'useful_life_months' => 0,
                'location' => 'Ruang Administrasi',
                'condition' => 'GOOD',
                'supplier' => 'CV Mitra Abadi',
                'invoice_number' => 'INV-FA-005',
                'warranty_expiry' => Carbon::parse('2025-02-01'),
                'notes' => null,
            ],
            [
                'category' => 'VEHICLE',
                'asset_name' => 'Motor Honda Beat - Kurir',
                'purchase_date' => Carbon::parse('2024-04-12'),
                'purchase_price' => 19500000,
                'salvage_value' => 5000000,
                'useful_life_years' => 5,
                'useful_life_months' => 0,
                'location' => 'Parkiran Motor',
                'condition' => 'GOOD',
                'supplier' => 'Dealer Honda Resmi',
                'invoice_number' => 'INV-FA-006',
                'warranty_expiry' => Carbon::parse('2025-04-12'),
                'notes' => 'Kendaraan operasional kurir dokumen',
            ],
            [
                'category' => 'EQUIPMENT',
                'asset_name' => 'AC Daikin 2PK - Ruang Meeting',
                'purchase_date' => Carbon::parse('2024-01-20'),
                'purchase_price' => 8500000,
                'salvage_value' => 500000,
                'useful_life_years' => 5,
                'useful_life_months' => 0,
                'location' => 'Ruang Meeting Utama',
                'condition' => 'GOOD',
                'supplier' => 'Toko Elektronik Murah',
                'invoice_number' => 'INV-FA-007',
                'warranty_expiry' => Carbon::parse('2025-01-20'),
                'notes' => null,
            ],
            [
                'category' => 'COMPUTER',
                'asset_name' => 'Server Dell PowerEdge',
                'purchase_date' => Carbon::parse('2024-07-01'),
                'purchase_price' => 65000000,
                'salvage_value' => 10000000,
                'useful_life_years' => 5,
                'useful_life_months' => 0,
                'location' => 'Ruang Server',
                'condition' => 'EXCELLENT',
                'supplier' => 'PT Data Center Solusi',
                'invoice_number' => 'INV-FA-008',
                'warranty_expiry' => Carbon::parse('2027-07-01'),
                'notes' => 'Server utama aplikasi internal',
            ],
            [
                'category' => 'EQUIPMENT',
                'asset_name' => 'Proyektor Epson EB-X500',
                'purchase_date' => Carbon::parse('2024-02-15'),
                'purchase_price' => 7500000,
                'salvage_value' => 1000000,
                'useful_life_years' => 4,
                'useful_life_months' => 0,
                'location' => 'Ruang Meeting Kecil',
                'condition' => 'GOOD',
                'supplier' => 'Toko Komputer Jaya',
                'invoice_number' => 'INV-FA-009',
                'warranty_expiry' => Carbon::parse('2025-02-15'),
                'notes' => null,
            ],
            [
                'category' => 'VEHICLE',
                'asset_name' => 'Mobil Box Grand Max',
                'purchase_date' => Carbon::parse('2024-03-01'),
                'purchase_price' => 165000000,
                'salvage_value' => 30000000,
                'useful_life_years' => 8,
                'useful_life_months' => 0,
                'location' => 'Gudang Logistik',
                'condition' => 'GOOD',
                'supplier' => 'Dealer Daihatsu',
                'invoice_number' => 'INV-FA-010',
                'warranty_expiry' => Carbon::parse('2027-03-01'),
                'notes' => 'Kendaraan angkut barang logistik',
            ],
        ];

        $created = 0;

        foreach ($data as $item) {
            $category = $item['category'];
            $assetAccount = $assetAccounts[$category];
            $accumAccount = $accumAccounts[$category];

            $asset = FixedAsset::firstOrCreate(
                [
                    'asset_name' => $item['asset_name'],
                    'purchase_date' => $item['purchase_date'],
                ],
                [
                    'asset_code' => FixedAsset::generateAssetCode($category),
                    'category' => $category,
                    'purchase_price' => $item['purchase_price'],
                    'accumulated_depreciation' => 0,
                    'depreciation_method' => 'STRAIGHT_LINE',
                    'useful_life_years' => $item['useful_life_years'],
                    'useful_life_months' => $item['useful_life_months'],
                    'salvage_value' => $item['salvage_value'],
                    'current_book_value' => $item['purchase_price'],
                    'location' => $item['location'],
                    'condition' => $item['condition'],
                    'supplier' => $item['supplier'],
                    'invoice_number' => $item['invoice_number'],
                    'warranty_expiry' => $item['warranty_expiry'],
                    'notes' => $item['notes'],
                    'chart_of_account_id' => $assetAccount->id,
                    'depreciation_account_id' => $accumAccount->id,
                    'is_active' => true,
                ]
            );

            $asset->updateBookValue();
            $created++;
            $this->command->line("- {$asset->asset_name} ({$asset->asset_code})");
        }

        $this->command->info("Created {$created} fixed assets.");
    }
}
