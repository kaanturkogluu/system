<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\CategoryAttribute;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\XmlAttributeMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductAttributePersistenceService
{
    /**
     * Process attributes for a product
     * 
     * @param Product $product
     * @param array $payload Import item payload
     * @return array Statistics and errors
     */
    public function processProduct(Product $product, array $payload): array
    {
        $stats = [
            'processed' => 0,
            'skipped' => 0,
            'errors' => [],
            'missing_mappings' => [],
        ];

        try {

            // 1. Extract TeknikOzellikler
            $teknikOzellikler = $this->extractTeknikOzellikler($payload);
            
            if (empty($teknikOzellikler)) {
                $stats['skipped']++;
                return $stats;
            }

            // 2. Get all active XML attribute mappings
            $mappings = XmlAttributeMapping::where('source_type', 'xml')
                ->where('status', 'active')
                ->with('attribute')
                ->get()
                ->keyBy('source_attribute_key');

            // 3. Process each XML attribute
            DB::transaction(function () use ($product, $teknikOzellikler, $mappings, &$stats) {
                foreach ($teknikOzellikler as $xmlKey => $xmlValue) {
                    // 3. Resolve xml_attribute_mapping
                    if (!isset($mappings[$xmlKey])) {
                        // Missing mapping
                        $stats['missing_mappings'][] = $xmlKey;
                        $stats['errors'][] = "Missing mapping for XML key: {$xmlKey}";
                        continue;
                    }

                    $mapping = $mappings[$xmlKey];
                    $attribute = $mapping->attribute;

                    if (!$attribute) {
                        $stats['errors'][] = "Attribute not found for mapping: {$xmlKey}";
                        continue;
                    }

                    // 4. Resolve global attribute (already done via mapping)
                    // 5. Process based on data_type
                    $result = $this->processAttributeValue($product, $attribute, $xmlValue);
                    
                    if ($result['success']) {
                        $stats['processed']++;
                    } else {
                        $stats['errors'][] = $result['error'];
                        $stats['skipped']++;
                    }
                }
            });

        } catch (\Exception $e) {
            $stats['errors'][] = "Exception: " . $e->getMessage();
            Log::channel('imports')->error('ProductAttributePersistenceService error', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $stats;
    }

    /**
     * Extract TeknikOzellikler from payload
     * 
     * @param array $payload
     * @return array [xml_key => value]
     */
    private function extractTeknikOzellikler(array $payload): array
    {
        $attributes = [];

        // Find TeknikOzellikler in payload
        $teknikOzellikler = $payload['TeknikOzellikler'] ?? null;

        if (!is_array($teknikOzellikler)) {
            return $attributes;
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

                        // Skip system attributes
                        $skipKeys = ['marka', 'diger', 'diğer', 'açıklama', 'açıklama2'];
                        $normalizedOzellik = mb_strtolower(trim($ozellik), 'UTF-8');
                        $normalizedOzellik = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], $normalizedOzellik);
                        $normalizedSkipKeys = array_map(function($key) {
                            return str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], mb_strtolower($key, 'UTF-8'));
                        }, $skipKeys);
                        
                        if (in_array($normalizedOzellik, $normalizedSkipKeys)) {
                            continue;
                        }

                        if (!empty($ozellik)) {
                            $attributes[$ozellik] = $deger;
                        }
                    }
                }
            } else {
                // Single object
                if (isset($urunTeknikOzellikler['Ozellik']) && isset($urunTeknikOzellikler['Deger'])) {
                    $ozellik = trim($urunTeknikOzellikler['Ozellik']);
                    $deger = $urunTeknikOzellikler['Deger'];

                    // Skip system attributes
                    $skipKeys = ['marka', 'diger', 'diğer', 'açıklama', 'açıklama2'];
                    $normalizedOzellik = mb_strtolower(trim($ozellik), 'UTF-8');
                    $normalizedOzellik = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], $normalizedOzellik);
                    $normalizedSkipKeys = array_map(function($key) {
                        return str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç'], ['i', 'g', 'u', 's', 'o', 'c'], mb_strtolower($key, 'UTF-8'));
                    }, $skipKeys);
                    
                    if (!in_array($normalizedOzellik, $normalizedSkipKeys)) {
                        if (!empty($ozellik)) {
                            $attributes[$ozellik] = $deger;
                        }
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * Process attribute value based on data_type
     * 
     * @param Product $product
     * @param Attribute $attribute
     * @param mixed $xmlValue
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function processAttributeValue(Product $product, Attribute $attribute, $xmlValue): array
    {
        try {
            $dataType = $attribute->data_type;

            switch ($dataType) {
                case 'enum':
                    return $this->processEnumAttribute($product, $attribute, $xmlValue);
                
                case 'string':
                    return $this->processStringAttribute($product, $attribute, $xmlValue);
                
                case 'number':
                    return $this->processNumberAttribute($product, $attribute, $xmlValue);
                
                case 'boolean':
                    return $this->processBooleanAttribute($product, $attribute, $xmlValue);
                
                default:
                    return [
                        'success' => false,
                        'error' => "Unknown data_type: {$dataType} for attribute_id: {$attribute->id}",
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Exception processing attribute_id {$attribute->id}: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Process enum attribute - resolve attribute_value_id
     * 
     * @param Product $product
     * @param Attribute $attribute
     * @param mixed $xmlValue
     * @return array
     */
    private function processEnumAttribute(Product $product, Attribute $attribute, $xmlValue): array
    {
        // Normalize value
        $normalizedValue = $this->normalizeValue($xmlValue);

        // Find attribute_value_id
        $attributeValue = AttributeValue::where('attribute_id', $attribute->id)
            ->where('normalized_value', $normalizedValue)
            ->where('status', 'active')
            ->first();

        if (!$attributeValue) {
            return [
                'success' => false,
                'error' => "AttributeValue not found for attribute_id: {$attribute->id}, normalized_value: {$normalizedValue}",
            ];
        }

        // UPSERT into product_attributes
        ProductAttribute::updateOrCreate(
            [
                'product_id' => $product->id,
                'attribute_id' => $attribute->id,
            ],
            [
                'attribute_value_id' => $attributeValue->id,
                'value_string' => null,
                'value_number' => null,
            ]
        );

        return ['success' => true, 'error' => null];
    }

    /**
     * Process string attribute
     * 
     * @param Product $product
     * @param Attribute $attribute
     * @param mixed $xmlValue
     * @return array
     */
    private function processStringAttribute(Product $product, Attribute $attribute, $xmlValue): array
    {
        $valueString = $this->normalizeStringValue($xmlValue);

        if ($valueString === null || strlen($valueString) === 0) {
            return [
                'success' => false,
                'error' => "Empty string value for attribute_id: {$attribute->id}",
            ];
        }

        // UPSERT into product_attributes
        ProductAttribute::updateOrCreate(
            [
                'product_id' => $product->id,
                'attribute_id' => $attribute->id,
            ],
            [
                'value_string' => $valueString,
                'value_number' => null,
                'attribute_value_id' => null,
            ]
        );

        return ['success' => true, 'error' => null];
    }

    /**
     * Process number attribute
     * 
     * @param Product $product
     * @param Attribute $attribute
     * @param mixed $xmlValue
     * @return array
     */
    private function processNumberAttribute(Product $product, Attribute $attribute, $xmlValue): array
    {
        $valueNumber = $this->normalizeNumberValue($xmlValue);

        if ($valueNumber === null) {
            return [
                'success' => false,
                'error' => "Invalid number value for attribute_id: {$attribute->id}, value: " . (string)$xmlValue,
            ];
        }

        // UPSERT into product_attributes
        ProductAttribute::updateOrCreate(
            [
                'product_id' => $product->id,
                'attribute_id' => $attribute->id,
            ],
            [
                'value_number' => $valueNumber,
                'value_string' => null,
                'attribute_value_id' => null,
            ]
        );

        return ['success' => true, 'error' => null];
    }

    /**
     * Process boolean attribute (stored as number: 0 or 1)
     * 
     * @param Product $product
     * @param Attribute $attribute
     * @param mixed $xmlValue
     * @return array
     */
    private function processBooleanAttribute(Product $product, Attribute $attribute, $xmlValue): array
    {
        $valueNumber = $this->normalizeBooleanValue($xmlValue);

        if ($valueNumber === null) {
            return [
                'success' => false,
                'error' => "Invalid boolean value for attribute_id: {$attribute->id}, value: " . (string)$xmlValue,
            ];
        }

        // UPSERT into product_attributes
        ProductAttribute::updateOrCreate(
            [
                'product_id' => $product->id,
                'attribute_id' => $attribute->id,
            ],
            [
                'value_number' => $valueNumber,
                'value_string' => null,
                'attribute_value_id' => null,
            ]
        );

        return ['success' => true, 'error' => null];
    }

    /**
     * Normalize value for enum matching
     * 
     * @param mixed $value
     * @return string
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
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $str = (string) $value;
        $str = trim($str);
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/\s+/', ' ', $str);
        
        return $str;
    }

    /**
     * Normalize string value
     * 
     * @param mixed $value
     * @return string|null
     */
    private function normalizeStringValue($value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $str = trim((string) $value);
        
        if (strlen($str) === 0) {
            return null;
        }

        // Max length constraint (adjust as needed)
        if (strlen($str) > 1000) {
            $str = substr($str, 0, 1000);
        }

        return $str;
    }

    /**
     * Normalize number value
     * 
     * @param mixed $value
     * @return float|null
     */
    private function normalizeNumberValue($value): ?float
    {
        if (is_null($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Try to extract number from string
        $str = trim((string) $value);
        if (preg_match('/-?\d+\.?\d*/', $str, $matches)) {
            return (float) $matches[0];
        }

        return null;
    }

    /**
     * Normalize boolean value
     * 
     * @param mixed $value
     * @return int|null (0 or 1)
     */
    private function normalizeBooleanValue($value): ?int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_null($value)) {
            return null;
        }

        $str = mb_strtolower(trim((string) $value), 'UTF-8');
        
        $trueValues = ['true', '1', 'yes', 'evet', 'var', 'on'];
        $falseValues = ['false', '0', 'no', 'hayır', 'yok', 'off'];

        if (in_array($str, $trueValues)) {
            return 1;
        }

        if (in_array($str, $falseValues)) {
            return 0;
        }

        return null;
    }

    /**
     * Check if product has all required attributes
     * Returns true if all required attributes are present (in product_attributes or via brand->originCountry for Menşei)
     * 
     * @param Product $product
     * @return bool
     */
    public function hasAllRequiredAttributes(Product $product): bool
    {
        // Kategori yoksa kontrol edilemez
        if (!$product->category_id) {
            return false;
        }

        // Required attribute'ları al
        $requiredAttributes = CategoryAttribute::where('category_id', $product->category_id)
            ->where('is_required', true)
            ->with('attribute')
            ->get();

        if ($requiredAttributes->isEmpty()) {
            // Required attribute yoksa hazır sayılabilir
            return true;
        }

        // Product'ı yeniden yükle (product_attributes ilişkisi için)
        $product->load('productAttributes.attribute', 'productAttributes.attributeValue', 'brand.originCountry');

        // Product'ın product_attributes tablosundan verileri al
        $productAttributesMap = [];
        if ($product->productAttributes) {
            foreach ($product->productAttributes as $productAttribute) {
                $productAttributesMap[$productAttribute->attribute_id] = $productAttribute;
            }
        }

        // Her required attribute için kontrol yap
        foreach ($requiredAttributes as $categoryAttribute) {
            $attribute = $categoryAttribute->attribute;
            if (!$attribute) {
                continue;
            }

            // Menşei özelliği için özel kontrol
            $isMenşei = false;
            if (strtolower(trim($attribute->name)) === 'menşei' || 
                strtolower(trim($attribute->code)) === 'menşei' ||
                strtolower(trim($attribute->code)) === 'mensei') {
                $isMenşei = true;
            }

            // Menşei özelliği ve brand->originCountry mevcut ise geçerli say
            if ($isMenşei && $product->brand && $product->brand->originCountry) {
                continue; // Menşei bilgisi mevcut, geçerli
            }

            // product_attributes tablosunda kontrol et
            if (isset($productAttributesMap[$attribute->id])) {
                $productAttribute = $productAttributesMap[$attribute->id];
                
                // Değer var mı kontrol et
                $hasValue = false;
                if ($productAttribute->attribute_value_id !== null) {
                    $hasValue = true;
                } elseif ($productAttribute->value_string !== null && trim($productAttribute->value_string) !== '') {
                    $hasValue = true;
                } elseif ($productAttribute->value_number !== null) {
                    $hasValue = true;
                }

                if (!$hasValue) {
                    // Required attribute var ama değer yok
                    return false;
                }
            } else {
                // Required attribute product_attributes tablosunda yok
                return false;
            }
        }

        // Tüm required attribute'lar mevcut
        return true;
    }
}

