<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\Marketplace;
use App\Models\MarketplaceCountryMapping;
use App\Services\TrendyolCategoryAttributeService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncTrendyolOriginMappings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trendyol:sync-origin-mappings 
                            {--category-id=2671 : Trendyol category ID to fetch attributes from}
                            {--attribute-id=1192 : Trendyol attribute ID for "Menşei"}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Trendyol origin (Menşei) attribute values to marketplace_country_mappings table';

    /**
     * Execute the console command.
     */
    public function handle(TrendyolCategoryAttributeService $service)
    {
        $this->info('=== Trendyol Menşei Eşleştirmeleri Senkronizasyonu ===');
        $this->newLine();

        // Get Trendyol marketplace
        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();
        
        if (!$trendyolMarketplace) {
            $this->error('Trendyol marketplace bulunamadı!');
            return Command::FAILURE;
        }

        $this->info("Trendyol Marketplace ID: {$trendyolMarketplace->id}");
        $this->newLine();

        // Get category ID and attribute ID from options
        $categoryId = (int) $this->option('category-id');
        $attributeId = (int) $this->option('attribute-id');

        $this->info("Kategori ID: {$categoryId}");
        $this->info("Özellik ID (Menşei): {$attributeId}");
        $this->newLine();

        // Fetch category attributes from Trendyol API
        $this->info('Trendyol API\'den özellikler çekiliyor...');
        $apiData = $service->getCategoryAttributes($categoryId);

        if (!$apiData || !isset($apiData['categoryAttributes'])) {
            $this->error('Trendyol API\'den veri alınamadı!');
            return Command::FAILURE;
        }

        // Find "Menşei" attribute
        $originAttribute = null;
        foreach ($apiData['categoryAttributes'] as $categoryAttribute) {
            $attr = $categoryAttribute['attribute'] ?? null;
            if ($attr && isset($attr['id']) && $attr['id'] == $attributeId) {
                $originAttribute = $categoryAttribute;
                break;
            }
        }

        if (!$originAttribute) {
            $this->error("Menşei özelliği (ID: {$attributeId}) bulunamadı!");
            return Command::FAILURE;
        }

        $this->info("Menşei özelliği bulundu: {$originAttribute['attribute']['name']}");
        $this->newLine();

        // Get attribute values
        $attributeValues = $originAttribute['attributeValues'] ?? [];

        if (empty($attributeValues)) {
            $this->warn('Menşei özelliği için değer bulunamadı!');
            return Command::FAILURE;
        }

        $this->info("Toplam " . count($attributeValues) . " menşei değeri bulundu.");
        $this->newLine();

        // Get all countries for matching
        $countries = Country::where('status', 'active')->get();
        $this->info("Toplam {$countries->count()} aktif ülke bulundu.");
        $this->newLine();

        $stats = [
            'processed' => 0,
            'matched' => 0,
            'created' => 0,
            'updated' => 0,
            'not_matched' => [],
        ];

        $this->info('Eşleştirmeler yapılıyor...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($attributeValues));
        $progressBar->start();

        foreach ($attributeValues as $value) {
            $trendyolValueName = $value['name'] ?? '';
            $trendyolValueId = $value['id'] ?? null;

            if (empty($trendyolValueName)) {
                $progressBar->advance();
                continue;
            }

            $stats['processed']++;

            // Try to match with country
            $matchedCountry = $this->matchCountry($trendyolValueName, $countries);

            if (!$matchedCountry) {
                $stats['not_matched'][] = $trendyolValueName;
                $progressBar->advance();
                continue;
            }

            $stats['matched']++;

            // Create or update marketplace country mapping
            $mapping = MarketplaceCountryMapping::updateOrCreate(
                [
                    'marketplace_id' => $trendyolMarketplace->id,
                    'country_id' => $matchedCountry->id,
                ],
                [
                    'external_country_id' => $trendyolValueId,
                    'external_country_code' => $trendyolValueName,
                    'external_country_name' => $trendyolValueName,
                    'status' => 'active',
                ]
            );

            if ($mapping->wasRecentlyCreated) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->info('=== Sonuçlar ===');
        $this->table(
            ['Metrik', 'Değer'],
            [
                ['İşlenen Değer', $stats['processed']],
                ['Eşleştirilen', $stats['matched']],
                ['Oluşturulan', $stats['created']],
                ['Güncellenen', $stats['updated']],
                ['Eşleştirilemeyen', count($stats['not_matched'])],
            ]
        );

        if (!empty($stats['not_matched'])) {
            $this->newLine();
            $this->warn('Eşleştirilemeyen Değerler:');
            foreach ($stats['not_matched'] as $value) {
                $this->line("  - {$value}");
            }
        }

        $this->newLine();
        $this->info('İşlem tamamlandı!');

        return Command::SUCCESS;
    }

    /**
     * Match Trendyol value with country
     *
     * @param string $trendyolValue
     * @param \Illuminate\Database\Eloquent\Collection $countries
     * @return Country|null
     */
    private function matchCountry(string $trendyolValue, $countries): ?Country
    {
        $normalizedTrendyol = $this->normalize($trendyolValue);

        // First try exact match with country code
        foreach ($countries as $country) {
            if (strtoupper($country->code) === strtoupper($trendyolValue)) {
                return $country;
            }
        }

        // Try normalized name match
        foreach ($countries as $country) {
            $normalizedCountryName = $this->normalize($country->name);
            $normalizedCountryCode = $this->normalize($country->code);

            if ($normalizedCountryName === $normalizedTrendyol || 
                $normalizedCountryCode === $normalizedTrendyol) {
                return $country;
            }
        }

        // Try partial match
        foreach ($countries as $country) {
            $normalizedCountryName = $this->normalize($country->name);
            
            if (str_contains($normalizedCountryName, $normalizedTrendyol) || 
                str_contains($normalizedTrendyol, $normalizedCountryName)) {
                return $country;
            }
        }

        // Special mappings for common cases
        $specialMappings = [
            'UM' => 'US', // United States Minor Outlying Islands -> US
            'TR' => 'TR',
            'CN' => 'CN',
            'US' => 'US',
            'DE' => 'DE',
            'JP' => 'JP',
            'KR' => 'KR',
            'TW' => 'TW',
        ];

        if (isset($specialMappings[$trendyolValue])) {
            return $countries->firstWhere('code', $specialMappings[$trendyolValue]);
        }

        return null;
    }

    /**
     * Normalize string for comparison
     *
     * @param string $value
     * @return string
     */
    private function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^a-z0-9]/', '', $value);
        return $value;
    }
}
