<?php

namespace App\Services;

use App\Helpers\MarketplaceConfig;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Marketplace;
use App\Models\MarketplaceCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Exception;

class TrendyolCategoryAttributeService
{
    private const MARKETPLACE_SLUG = 'trendyol';
    private const API_BASE_URL = 'https://apigw.trendyol.com';
    
    private ?string $apiKey = null;
    private ?string $apiSecret = null;
    private bool $configLoaded = false;

    /**
     * Load configuration from database
     */
    private function loadConfiguration(): void
    {
        if ($this->configLoaded) {
            return;
        }

        try {
            $this->apiKey = MarketplaceConfig::get(self::MARKETPLACE_SLUG, 'api_key');
            $this->apiSecret = MarketplaceConfig::get(self::MARKETPLACE_SLUG, 'api_secret');
            $this->configLoaded = true;
        } catch (InvalidArgumentException $e) {
            Log::channel('imports')->error('Trendyol API configuration missing', [
                'error' => $e->getMessage(),
            ]);
            $this->configLoaded = true;
        }
    }

    /**
     * Validate that all required configuration is present
     */
    private function isConfigured(): bool
    {
        $this->loadConfiguration();
        return !empty($this->apiKey) && !empty($this->apiSecret);
    }

    /**
     * Fetch category attributes from Trendyol API
     *
     * @param int $trendyolCategoryId
     * @return array|null
     */
    public function getCategoryAttributes(int $trendyolCategoryId): ?array
    {
        if (!$this->isConfigured()) {
            Log::channel('imports')->error('Trendyol API credentials not configured');
            return null;
        }

        try {
            $url = self::API_BASE_URL . '/integration/product/product-categories/' . $trendyolCategoryId . '/attributes';
            
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->timeout(30)
                ->get($url);

            if (!$response->successful()) {
                Log::channel('imports')->warning('Trendyol category attributes fetch failed', [
                    'category_id' => $trendyolCategoryId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            
            return $data ?? null;

        } catch (Exception $e) {
            Log::channel('imports')->error('Trendyol category attributes exception', [
                'category_id' => $trendyolCategoryId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Import attributes for a mapped category
     *
     * @param int $globalCategoryId
     * @return array Statistics
     */
    public function importAttributesForCategory(int $globalCategoryId): array
    {
        $stats = [
            'attributes_created' => 0,
            'attributes_updated' => 0,
            'values_created' => 0,
            'errors' => [],
        ];

        $trendyolMarketplace = Marketplace::where('slug', self::MARKETPLACE_SLUG)->first();
        
        if (!$trendyolMarketplace) {
            $stats['errors'][] = 'Trendyol marketplace not found';
            return $stats;
        }

        // Find mapped Trendyol category
        $marketplaceCategory = MarketplaceCategory::where('marketplace_id', $trendyolMarketplace->id)
            ->where('global_category_id', $globalCategoryId)
            ->where('is_mapped', true)
            ->first();

        if (!$marketplaceCategory) {
            // Check if category exists
            $category = Category::find($globalCategoryId);
            $categoryName = $category ? $category->name : 'ID: ' . $globalCategoryId;
            
            // Check if there's an unmapped entry
            $unmappedCategory = MarketplaceCategory::where('marketplace_id', $trendyolMarketplace->id)
                ->where('global_category_id', $globalCategoryId)
                ->where('is_mapped', false)
                ->first();
            
            if ($unmappedCategory) {
                $stats['errors'][] = "Kategori '{$categoryName}' Trendyol ile eşleştirilmiş ancak aktif değil. Lütfen marketplace-category-mappings sayfasından eşleştirmeyi aktifleştirin.";
            } else {
                $stats['errors'][] = "Kategori '{$categoryName}' Trendyol ile eşleştirilmemiş. Lütfen önce marketplace-category-mappings sayfasından bu kategoriyi bir Trendyol kategorisi ile eşleştirin.";
            }
            
            Log::channel('imports')->warning('Category not mapped for attribute import', [
                'category_id' => $globalCategoryId,
                'category_name' => $categoryName,
                'has_unmapped_entry' => $unmappedCategory !== null,
            ]);
            
            return $stats;
        }

        // Fetch attributes from API
        $apiData = $this->getCategoryAttributes($marketplaceCategory->marketplace_category_id);

        if (!$apiData || !isset($apiData['categoryAttributes'])) {
            $stats['errors'][] = 'Failed to fetch attributes from Trendyol API';
            return $stats;
        }

        try {
            DB::beginTransaction();

            foreach ($apiData['categoryAttributes'] as $categoryAttribute) {
                $attributeData = $categoryAttribute['attribute'] ?? null;
                
                if (!$attributeData) {
                    continue;
                }

                $trendyolAttributeId = $attributeData['id'] ?? null;
                $trendyolAttributeName = $attributeData['name'] ?? '';

                if (!$trendyolAttributeId || !$trendyolAttributeName) {
                    continue;
                }

                // Skip "Menşei" attribute (ID: 1192) - it's handled separately via marketplace_country_mappings
                if ($trendyolAttributeId == 1192 || strtolower(trim($trendyolAttributeName)) === 'menşei') {
                    continue;
                }

                // Determine data type
                $attributeValues = $categoryAttribute['attributeValues'] ?? [];
                $allowCustom = $categoryAttribute['allowCustom'] ?? false;
                
                // If has predefined values, it's enum. If allowCustom is true and no values, it's string. Otherwise check if empty.
                if (!empty($attributeValues)) {
                    $dataType = 'enum';
                } elseif ($allowCustom) {
                    $dataType = 'string'; // Custom text input
                } else {
                    $dataType = 'string'; // Default
                }
                
                // Generate code
                $code = $this->generateCode($trendyolAttributeName);

                // Check if attribute already exists
                $attribute = Attribute::where('code', $code)->first();

                if ($attribute) {
                    // Update existing
                    $attribute->update([
                        'name' => $trendyolAttributeName,
                        'data_type' => $dataType,
                        'is_filterable' => $categoryAttribute['slicer'] ?? false,
                        'status' => 'active',
                    ]);
                    $stats['attributes_updated']++;
                } else {
                    // Create new
                    $attribute = Attribute::create([
                        'code' => $code,
                        'name' => $trendyolAttributeName,
                        'data_type' => $dataType,
                        'is_filterable' => $categoryAttribute['slicer'] ?? false,
                        'status' => 'active',
                    ]);
                    $stats['attributes_created']++;
                }

                // Handle enum values (skip if this is Menşei attribute)
                if ($dataType === 'enum' && !empty($attributeValues) && $trendyolAttributeId != 1192 && strtolower(trim($trendyolAttributeName)) !== 'menşei') {
                    $this->syncAttributeValues($attribute, $attributeValues, $stats);
                }

                // Link attribute to category (update or create)
                $category = Category::find($globalCategoryId);
                if ($category) {
                    if ($category->attributes()->where('attributes.id', $attribute->id)->exists()) {
                        // Update existing link
                        $category->attributes()->updateExistingPivot($attribute->id, [
                            'is_required' => $categoryAttribute['required'] ?? false,
                        ]);
                    } else {
                        // Create new link
                        $category->attributes()->attach($attribute->id, [
                            'is_required' => $categoryAttribute['required'] ?? false,
                        ]);
                    }
                }
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            $stats['errors'][] = $e->getMessage();
            Log::channel('imports')->error('Failed to import category attributes', [
                'category_id' => $globalCategoryId,
                'error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

    /**
     * Sync attribute values
     */
    private function syncAttributeValues(Attribute $attribute, array $attributeValues, array &$stats): void
    {
        // Skip if this is the "Menşei" attribute
        if ($attribute->name === 'Menşei' || strtolower(trim($attribute->name)) === 'menşei') {
            return;
        }

        $existingValues = $attribute->allValues()->pluck('normalized_value', 'id')->toArray();
        $processedValues = [];

        foreach ($attributeValues as $valueData) {
            $displayValue = $valueData['name'] ?? '';
            
            if (empty($displayValue)) {
                continue;
            }

            // Skip "Menşei" related values
            if (strtolower(trim($displayValue)) === 'menşei') {
                continue;
            }

            $normalizedValue = Str::slug($displayValue, '_');
            
            // Ensure uniqueness
            $baseNormalized = $normalizedValue;
            $counter = 1;
            while (in_array($normalizedValue, $processedValues) || 
                   in_array($normalizedValue, array_values($existingValues))) {
                $normalizedValue = $baseNormalized . '_' . $counter;
                $counter++;
            }

            $processedValues[] = $normalizedValue;

            // Check if value exists
            $existingValue = $attribute->allValues()
                ->where('normalized_value', $normalizedValue)
                ->first();

            if ($existingValue) {
                // Update if needed
                $existingValue->update([
                    'value' => $displayValue,
                    'status' => 'active',
                ]);
            } else {
                // Create new
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => $displayValue,
                    'normalized_value' => $normalizedValue,
                    'status' => 'active',
                ]);
                $stats['values_created']++;
            }
        }

        // Deactivate values that are no longer in API response
        $apiNormalizedValues = array_map(function($v) {
            return Str::slug($v['name'] ?? '', '_');
        }, $attributeValues);

        $attribute->allValues()
            ->whereNotIn('normalized_value', $apiNormalizedValues)
            ->update(['status' => 'passive']);
    }

    /**
     * Generate attribute code from name
     */
    private function generateCode(string $name): string
    {
        $code = Str::slug($name, '_');
        
        // Ensure starts with letter
        if (preg_match('/^[0-9]/', $code)) {
            $code = 'attr_' . $code;
        }

        // Ensure uniqueness
        $baseCode = $code;
        $counter = 1;
        while (Attribute::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;
        }

        return $code;
    }

    /**
     * Import attributes for all mapped categories
     *
     * @param int|null $limit
     * @return array Statistics
     */
    public function importAttributesForAllMappedCategories(?int $limit = null): array
    {
        $trendyolMarketplace = Marketplace::where('slug', self::MARKETPLACE_SLUG)->first();
        
        if (!$trendyolMarketplace) {
            return ['errors' => ['Trendyol marketplace not found']];
        }

        $query = MarketplaceCategory::where('marketplace_id', $trendyolMarketplace->id)
            ->where('is_mapped', true)
            ->whereNotNull('global_category_id')
            ->distinct('global_category_id');

        if ($limit) {
            $query->limit($limit);
        }

        $mappedCategories = $query->pluck('global_category_id')->unique();

        $totalStats = [
            'categories_processed' => 0,
            'attributes_created' => 0,
            'attributes_updated' => 0,
            'values_created' => 0,
            'errors' => [],
        ];

        foreach ($mappedCategories as $categoryId) {
            $stats = $this->importAttributesForCategory($categoryId);
            
            $totalStats['categories_processed']++;
            $totalStats['attributes_created'] += $stats['attributes_created'];
            $totalStats['attributes_updated'] += $stats['attributes_updated'];
            $totalStats['values_created'] += $stats['values_created'];
            $totalStats['errors'] = array_merge($totalStats['errors'], $stats['errors']);

            // Small delay to avoid rate limiting
            usleep(200000); // 0.2 seconds
        }

        return $totalStats;
    }
}

