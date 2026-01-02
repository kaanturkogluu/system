<?php

namespace Database\Seeders;

use App\Models\Marketplace;
use App\Models\MarketplaceShippingCompanyMapping;
use App\Models\ShippingCompany;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrendyolShippingCompanySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Seeds global shipping companies and Trendyol marketplace mappings.
     * This seeder is idempotent using firstOrCreate/updateOrCreate.
     */
    public function run(): void
    {
        $this->command->info('📦 Kargo şirketleri ekleniyor...');

        // Get Trendyol marketplace
        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();

        if (!$trendyolMarketplace) {
            $this->command->error('❌ Trendyol marketplace bulunamadı. Önce MarketplaceSeeder çalıştırın.');
            return;
        }

        // Global shipping companies data
        $shippingCompanies = [
            ['code' => 'kolay_gelsin', 'name' => 'Kolay Gelsin', 'status' => 'active'],
            ['code' => 'borusan', 'name' => 'Borusan Lojistik', 'status' => 'active'],
            ['code' => 'dhl', 'name' => 'DHL', 'status' => 'active'],
            ['code' => 'ptt', 'name' => 'PTT Kargo', 'status' => 'active'],
            ['code' => 'surat', 'name' => 'Sürat Kargo', 'status' => 'active'],
            ['code' => 'trendyol_express', 'name' => 'Trendyol Express', 'status' => 'active'],
            ['code' => 'horoz', 'name' => 'Horoz Kargo', 'status' => 'active'],
            ['code' => 'cevalog', 'name' => 'CEVA Lojistik', 'status' => 'active'],
            ['code' => 'yurtici', 'name' => 'Yurtiçi Kargo', 'status' => 'active'],
            ['code' => 'aras', 'name' => 'Aras Kargo', 'status' => 'active'],
        ];

        // Create global shipping companies
        $createdCompanies = 0;
        $updatedCompanies = 0;

        foreach ($shippingCompanies as $companyData) {
            $company = ShippingCompany::updateOrCreate(
                ['code' => $companyData['code']],
                $companyData
            );

            if ($company->wasRecentlyCreated) {
                $createdCompanies++;
            } else {
                $updatedCompanies++;
            }
        }

        $this->command->info("✅ {$createdCompanies} yeni kargo şirketi oluşturuldu.");
        if ($updatedCompanies > 0) {
            $this->command->info("🔄 {$updatedCompanies} kargo şirketi güncellendi.");
        }

        $this->command->newLine();
        $this->command->info('📦 Trendyol kargo şirketi eşleştirmeleri ekleniyor...');

        // Trendyol marketplace mappings data
        $trendyolMappings = [
            [
                'shipping_company_code' => 'kolay_gelsin',
                'external_id' => 38,
                'external_code' => 'SENDEOMP',
                'external_name' => 'Kolay Gelsin Marketplace',
                'tax_number' => '2910804196',
            ],
            [
                'shipping_company_code' => 'borusan',
                'external_id' => 30,
                'external_code' => 'BORMP',
                'external_name' => 'Borusan Lojistik Marketplace',
                'tax_number' => '1800038254',
            ],
            [
                'shipping_company_code' => 'dhl',
                'external_id' => 10,
                'external_code' => 'DHLECOMMP',
                'external_name' => 'DHL eCommerce Marketplace',
                'tax_number' => '6080712084',
            ],
            [
                'shipping_company_code' => 'ptt',
                'external_id' => 19,
                'external_code' => 'PTTMP',
                'external_name' => 'PTT Kargo Marketplace',
                'tax_number' => '7320068060',
            ],
            [
                'shipping_company_code' => 'surat',
                'external_id' => 9,
                'external_code' => 'SURATMP',
                'external_name' => 'Sürat Kargo Marketplace',
                'tax_number' => '7870233582',
            ],
            [
                'shipping_company_code' => 'trendyol_express',
                'external_id' => 17,
                'external_code' => 'TEXMP',
                'external_name' => 'Trendyol Express Marketplace',
                'tax_number' => '8590921777',
            ],
            [
                'shipping_company_code' => 'horoz',
                'external_id' => 6,
                'external_code' => 'HOROZMP',
                'external_name' => 'Horoz Kargo Marketplace',
                'tax_number' => '4630097122',
            ],
            [
                'shipping_company_code' => 'cevalog',
                'external_id' => 20,
                'external_code' => 'CEVAMP',
                'external_name' => 'CEVA Marketplace',
                'tax_number' => '8450298557',
            ],
            [
                'shipping_company_code' => 'yurtici',
                'external_id' => 4,
                'external_code' => 'YKMP',
                'external_name' => 'Yurtiçi Kargo Marketplace',
                'tax_number' => '3130557669',
            ],
            [
                'shipping_company_code' => 'aras',
                'external_id' => 7,
                'external_code' => 'ARASMP',
                'external_name' => 'Aras Kargo Marketplace',
                'tax_number' => '720039666',
            ],
        ];

        // Create Trendyol marketplace mappings
        $createdMappings = 0;
        $updatedMappings = 0;
        $skippedMappings = 0;

        foreach ($trendyolMappings as $mappingData) {
            $shippingCompany = ShippingCompany::where('code', $mappingData['shipping_company_code'])->first();

            if (!$shippingCompany) {
                $skippedMappings++;
                $this->command->warn("⚠️  Kargo şirketi bulunamadı: {$mappingData['shipping_company_code']}");
                continue;
            }

            $mapping = MarketplaceShippingCompanyMapping::updateOrCreate(
                [
                    'marketplace_id' => $trendyolMarketplace->id,
                    'shipping_company_id' => $shippingCompany->id,
                ],
                [
                    'external_id' => $mappingData['external_id'],
                    'external_code' => $mappingData['external_code'],
                    'external_name' => $mappingData['external_name'],
                    'tax_number' => $mappingData['tax_number'],
                    'status' => 'active',
                ]
            );

            if ($mapping->wasRecentlyCreated) {
                $createdMappings++;
            } else {
                $updatedMappings++;
            }
        }

        $this->command->info("✅ {$createdMappings} yeni Trendyol eşleştirmesi oluşturuldu.");
        if ($updatedMappings > 0) {
            $this->command->info("🔄 {$updatedMappings} Trendyol eşleştirmesi güncellendi.");
        }
        if ($skippedMappings > 0) {
            $this->command->warn("⚠️  {$skippedMappings} eşleştirme atlandı.");
        }

        $this->command->newLine();
        $this->command->info('✅ Kargo şirketleri ve Trendyol eşleştirmeleri başarıyla eklendi.');
    }
}
