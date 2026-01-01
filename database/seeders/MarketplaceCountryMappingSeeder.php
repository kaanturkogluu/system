<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Marketplace;
use App\Models\MarketplaceCountryMapping;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarketplaceCountryMappingSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Trendyol marketplace
        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();
        
        if (!$trendyolMarketplace) {
            $this->command->warn('⚠️  Trendyol marketplace bulunamadı. Önce MarketplaceSeeder çalıştırın.');
            return;
        }

        $this->command->info('📦 Trendyol menşei eşleştirmeleri ekleniyor...');

        // Get all active countries
        $countries = Country::where('status', 'active')->get();
        
        if ($countries->isEmpty()) {
            $this->command->warn('⚠️  Aktif ülke bulunamadı. Önce CountrySeeder çalıştırın.');
            return;
        }

        // Trendyol menşei eşleştirmeleri (bilinen mapping'ler)
        // Bu mapping'ler Trendyol API'den alınan menşei değerleri ile ülkeler arasındaki eşleştirmelerdir
        $mappings = [
            // Turkey
            ['country_code' => 'TR', 'external_name' => 'Türkiye', 'external_id' => null],
            ['country_code' => 'TR', 'external_name' => 'Turkey', 'external_id' => null],
            
            // China
            ['country_code' => 'CN', 'external_name' => 'Çin', 'external_id' => null],
            ['country_code' => 'CN', 'external_name' => 'China', 'external_id' => null],
            
            // United States
            ['country_code' => 'US', 'external_name' => 'Amerika Birleşik Devletleri', 'external_id' => null],
            ['country_code' => 'US', 'external_name' => 'United States', 'external_id' => null],
            ['country_code' => 'US', 'external_name' => 'USA', 'external_id' => null],
            ['country_code' => 'US', 'external_name' => 'ABD', 'external_id' => null],
            
            // Taiwan
            ['country_code' => 'TW', 'external_name' => 'Tayvan', 'external_id' => null],
            ['country_code' => 'TW', 'external_name' => 'Taiwan', 'external_id' => null],
            
            // Japan
            ['country_code' => 'JP', 'external_name' => 'Japonya', 'external_id' => null],
            ['country_code' => 'JP', 'external_name' => 'Japan', 'external_id' => null],
            
            // South Korea
            ['country_code' => 'KR', 'external_name' => 'Güney Kore', 'external_id' => null],
            ['country_code' => 'KR', 'external_name' => 'South Korea', 'external_id' => null],
            ['country_code' => 'KR', 'external_name' => 'Korea', 'external_id' => null],
            
            // Germany
            ['country_code' => 'DE', 'external_name' => 'Almanya', 'external_id' => null],
            ['country_code' => 'DE', 'external_name' => 'Germany', 'external_id' => null],
            
            // Netherlands
            ['country_code' => 'NL', 'external_name' => 'Hollanda', 'external_id' => null],
            ['country_code' => 'NL', 'external_name' => 'Netherlands', 'external_id' => null],
            
            // Switzerland
            ['country_code' => 'CH', 'external_name' => 'İsviçre', 'external_id' => null],
            ['country_code' => 'CH', 'external_name' => 'Switzerland', 'external_id' => null],
            
            // Italy
            ['country_code' => 'IT', 'external_name' => 'İtalya', 'external_id' => null],
            ['country_code' => 'IT', 'external_name' => 'Italy', 'external_id' => null],
        ];

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($mappings as $mappingData) {
            $country = $countries->firstWhere('code', $mappingData['country_code']);
            
            if (!$country) {
                $skipped++;
                continue;
            }

            $mapping = MarketplaceCountryMapping::updateOrCreate(
                [
                    'marketplace_id' => $trendyolMarketplace->id,
                    'country_id' => $country->id,
                ],
                [
                    'external_country_id' => $mappingData['external_id'],
                    'external_country_code' => $mappingData['external_name'],
                    'external_country_name' => $mappingData['external_name'],
                    'status' => 'active',
                ]
            );

            if ($mapping->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->command->info("✅ {$created} yeni eşleştirme oluşturuldu.");
        if ($updated > 0) {
            $this->command->info("🔄 {$updated} eşleştirme güncellendi.");
        }
        if ($skipped > 0) {
            $this->command->warn("⚠️  {$skipped} eşleştirme atlandı (ülke bulunamadı).");
        }
    }
}
