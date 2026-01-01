<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\CategoryMapping;
use App\Models\ImportItem;
use Illuminate\Support\Str;

class XmlAttributeAnalysisService
{
    /**
     * Discover XML attributes from import_items.payload
     */
    public function discoverXmlAttributes(int $limit = 1000, ?string $categorySearch = null): array
    {
        $xmlAttributes = [];
        $categoryMap = $this->getCategoryMap();

        // Get all mapped external category IDs (only process items with mapped categories)
        $mappedExternalCategoryIds = CategoryMapping::where('status', 'mapped')
            ->pluck('external_category_id')
            ->toArray();
        
        if (empty($mappedExternalCategoryIds)) {
            // No mapped categories found
            return [];
        }
        
        $query = ImportItem::whereNotNull('payload')
            ->whereIn('external_category_id', $mappedExternalCategoryIds);
        
        // Additional filter by category name if search provided
        if ($categorySearch) {
            $categoryIds = Category::where('name', 'like', '%' . $categorySearch . '%')
                ->pluck('id')
                ->toArray();
            
            if (!empty($categoryIds)) {
                $filteredExternalCategoryIds = CategoryMapping::whereIn('category_id', $categoryIds)
                    ->where('status', 'mapped')
                    ->pluck('external_category_id')
                    ->toArray();
                
                if (!empty($filteredExternalCategoryIds)) {
                    $query->whereIn('external_category_id', $filteredExternalCategoryIds);
                } else {
                    // No matching categories found
                    return [];
                }
            } else {
                // No categories found with search term
                return [];
            }
        }

        $importItems = $query->limit($limit)->get();

        foreach ($importItems as $importItem) {
            $payload = $importItem->payload;

            if (!is_array($payload)) {
                continue;
            }

            // Get category info
            $categoryInfo = $this->getCategoryInfo($importItem, $categoryMap);

            // Extract ONLY from TeknikOzellikler structure
            // XML ürün özellikleri için sadece TeknikOzellikler tag'inde gelen veriler kullanılacak
            $this->extractTeknikOzellikler($payload, $xmlAttributes, $categoryInfo);
        }

        return $xmlAttributes;
    }

    /**
     * Get category mapping cache
     */
    private function getCategoryMap(): array
    {
        static $cache = null;
        
        if ($cache === null) {
            $mappings = CategoryMapping::where('status', 'mapped')
                ->with('category')
                ->get();
            
            $cache = [];
            foreach ($mappings as $mapping) {
                if ($mapping->category) {
                    $cache[$mapping->external_category_id] = [
                        'id' => $mapping->category_id,
                        'name' => $mapping->category->name,
                    ];
                }
            }
        }
        
        return $cache;
    }

    /**
     * Get category info for import item
     */
    private function getCategoryInfo($importItem, array $categoryMap): ?array
    {
        $externalCategoryId = $importItem->external_category_id;
        
        if (!$externalCategoryId) {
            // Try to get from payload
            $payload = $importItem->payload;
            $externalCategoryId = $payload['external_category_id'] 
                ?? $payload['category']['external_category_id'] 
                ?? null;
        }
        
        if ($externalCategoryId && isset($categoryMap[$externalCategoryId])) {
            return $categoryMap[$externalCategoryId];
        }
        
        // Try to get category name from payload
        $payload = $importItem->payload;
        $categoryName = $payload['Kategoriler']['Kategori'] 
            ?? $payload['Kategori'] 
            ?? $payload['category']['name'] 
            ?? null;
        
        if ($categoryName) {
            return [
                'id' => null,
                'name' => $categoryName,
            ];
        }
        
        return null;
    }

    /**
     * Extract attributes from TeknikOzellikler structure
     * Format: TeknikOzellikler.UrunTeknikOzellikler[].Ozellik = "Marka", Deger = "ZEBEX"
     */
    private function extractTeknikOzellikler(array $payload, array &$xmlAttributes, ?array $categoryInfo = null): void
    {
        // Find TeknikOzellikler in payload
        $teknikOzellikler = $payload['TeknikOzellikler'] ?? null;

        if (!is_array($teknikOzellikler)) {
            return;
        }

        // Handle UrunTeknikOzellikler array
        $urunTeknikOzellikler = $teknikOzellikler['UrunTeknikOzellikler'] ?? null;

        if (is_array($urunTeknikOzellikler)) {
            // Check if it's a list (indexed array) or single object
            if (isset($urunTeknikOzellikler[0])) {
                // It's an array of objects
                foreach ($urunTeknikOzellikler as $item) {
                    if (is_array($item) && isset($item['Ozellik']) && isset($item['Deger'])) {
                        $ozellik = trim($item['Ozellik']);
                        $deger = $item['Deger'];

                        // Skip "Marka" attribute
                        if (strtolower($ozellik) === 'marka') {
                            continue;
                        }

                        if (empty($ozellik)) {
                            continue;
                        }

                        // Use Ozellik as the attribute key
                        if (!isset($xmlAttributes[$ozellik])) {
                            $xmlAttributes[$ozellik] = [
                                'xml_attribute_key' => $ozellik,
                                'example_values' => [],
                                'product_count' => 0,
                                'categories' => [],
                                'category_names' => [],
                            ];
                        }

                        // Collect example values
                        if (count($xmlAttributes[$ozellik]['example_values']) < 5) {
                            $valueStr = $this->normalizeValue($deger);
                            if (!empty($valueStr) && !in_array($valueStr, $xmlAttributes[$ozellik]['example_values'])) {
                                $xmlAttributes[$ozellik]['example_values'][] = $valueStr;
                            }
                        }

                        $xmlAttributes[$ozellik]['product_count']++;
                        
                        // Add category info
                        if ($categoryInfo && $categoryInfo['id'] && !in_array($categoryInfo['id'], $xmlAttributes[$ozellik]['categories'])) {
                            $xmlAttributes[$ozellik]['categories'][] = $categoryInfo['id'];
                            $xmlAttributes[$ozellik]['category_names'][$categoryInfo['id']] = $categoryInfo['name'];
                        }
                    }
                }
            } else {
                // Single object
                if (isset($urunTeknikOzellikler['Ozellik']) && isset($urunTeknikOzellikler['Deger'])) {
                    $ozellik = trim($urunTeknikOzellikler['Ozellik']);
                    $deger = $urunTeknikOzellikler['Deger'];

                    // Skip "Marka" attribute
                    if (strtolower($ozellik) === 'marka') {
                        return;
                    }

                    if (!empty($ozellik)) {
                        if (!isset($xmlAttributes[$ozellik])) {
                            $xmlAttributes[$ozellik] = [
                                'xml_attribute_key' => $ozellik,
                                'example_values' => [],
                                'product_count' => 0,
                                'categories' => [],
                                'category_names' => [],
                            ];
                        }

                        if (count($xmlAttributes[$ozellik]['example_values']) < 5) {
                            $valueStr = $this->normalizeValue($deger);
                            if (!empty($valueStr) && !in_array($valueStr, $xmlAttributes[$ozellik]['example_values'])) {
                                $xmlAttributes[$ozellik]['example_values'][] = $valueStr;
                            }
                        }

                        $xmlAttributes[$ozellik]['product_count']++;
                        
                        // Add category info
                        if ($categoryInfo && $categoryInfo['id'] && !in_array($categoryInfo['id'], $xmlAttributes[$ozellik]['categories'])) {
                            $xmlAttributes[$ozellik]['categories'][] = $categoryInfo['id'];
                            $xmlAttributes[$ozellik]['category_names'][$categoryInfo['id']] = $categoryInfo['name'];
                        }
                    }
                }
            }
        }
    }

    /**
     * Extract attributes from payload
     */
    private function extractAttributesFromPayload(array $payload, string $prefix = '', bool $skipTeknikOzellikler = false): array
    {
        $attributes = [];

        foreach ($payload as $key => $value) {
            if ($this->isSystemKey($key)) {
                continue;
            }

            // Skip TeknikOzellikler structure (already processed separately)
            if ($skipTeknikOzellikler && $key === 'TeknikOzellikler') {
                continue;
            }

            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $nested = $this->extractAttributesFromPayload($value, $fullKey, $skipTeknikOzellikler);
                $attributes = array_merge($attributes, $nested);
            } else {
                $attributes[$fullKey] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Check if key is a system key
     */
    private function isSystemKey(string $key): bool
    {
        $systemKeys = [
            'external_id', 'external_category_id', 'raw_path',
            'sku', 'barcode', 'product_code', 'hash',
            'Ad', 'Title', 'ProductTitle', 'Description',
            'Marka', 'Brand', 'Category', 'category',
            'Fiyat', 'Price', 'Fiyat_SK', 'Fiyat_Bayi',
            'Miktar', 'Stock', 'Stok',
            'Kod', 'ProductId', 'Id',
        ];

        $normalizedKey = strtolower($key);
        foreach ($systemKeys as $systemKey) {
            if ($normalizedKey === strtolower($systemKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize value for display
     */
    private function normalizeValue($value): string
    {
        if (is_null($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $str = (string) $value;
        return mb_substr(trim($str), 0, 100);
    }

    /**
     * Analyze and match with global attributes
     */
    public function analyzeAndMatch(array $xmlAttributes): array
    {
        $globalAttributes = Attribute::where('status', 'active')
            ->pluck('code', 'id')
            ->toArray();

        $analysis = [];

        foreach ($xmlAttributes as $xmlKey => $data) {
            $suggestedCode = $this->suggestNormalizedCode($xmlKey);
            $match = $this->findMatchingAttribute($xmlKey, $suggestedCode, $globalAttributes);

            $analysis[] = [
                'xml_attribute_key' => $xmlKey,
                'suggested_global_code' => $suggestedCode,
                'matched_attribute_id' => $match['attribute_id'] ?? null,
                'matched_attribute_code' => $match['code'] ?? null,
                'confidence' => $match['confidence'] ?? 'LOW',
                'example_values' => $data['example_values'],
                'product_count' => $data['product_count'],
                'categories' => $data['categories'] ?? [],
                'category_names' => $data['category_names'] ?? [],
            ];
        }

        return $analysis;
    }

    /**
     * Suggest normalized code
     */
    private function suggestNormalizedCode(string $xmlKey): string
    {
        $key = $xmlKey;
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $key = end($parts);
        }

        $code = Str::slug($key, '_');
        $code = strtolower($code);

        if (preg_match('/^[0-9]/', $code)) {
            $code = 'attr_' . $code;
        }

        return $code;
    }

    /**
     * Find matching global attribute
     */
    private function findMatchingAttribute(string $xmlKey, string $suggestedCode, array $globalAttributes): array
    {
        // Exact match
        foreach ($globalAttributes as $attributeId => $code) {
            if ($code === $suggestedCode) {
                return [
                    'attribute_id' => $attributeId,
                    'code' => $code,
                    'confidence' => 'HIGH',
                ];
            }
        }

        // Partial match
        foreach ($globalAttributes as $attributeId => $code) {
            if (str_contains($code, $suggestedCode) || str_contains($suggestedCode, $code)) {
                return [
                    'attribute_id' => $attributeId,
                    'code' => $code,
                    'confidence' => 'MEDIUM',
                ];
            }
        }

        // Similarity check
        $bestMatch = null;
        $bestScore = 0;

        foreach ($globalAttributes as $attributeId => $code) {
            similar_text($suggestedCode, $code, $score);
            if ($score > $bestScore && $score > 70) {
                $bestScore = $score;
                $bestMatch = [
                    'attribute_id' => $attributeId,
                    'code' => $code,
                    'confidence' => 'MEDIUM',
                ];
            }
        }

        if ($bestMatch) {
            return $bestMatch;
        }

        return ['confidence' => 'LOW'];
    }

    /**
     * Prepare mappings
     */
    public function prepareMappings(array $analysis): array
    {
        $mappings = [];

        foreach ($analysis as $item) {
            if ($item['confidence'] === 'HIGH' && $item['matched_attribute_id']) {
                $mappings[] = [
                    'source_type' => 'xml',
                    'source_attribute_key' => $item['xml_attribute_key'],
                    'attribute_id' => $item['matched_attribute_id'],
                    'status' => 'active',
                    'confidence' => $item['confidence'],
                ];
            }
        }

        return $mappings;
    }
}

