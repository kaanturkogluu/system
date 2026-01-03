<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\AttributeValue;
use App\Models\XmlAttributeMapping;
use App\Models\Marketplace;
use App\Models\MarketplaceShippingCompanyMapping;
use App\Helpers\MarketplaceConfig;
use App\Services\ProductAttributePersistenceService;
use App\Services\ProductPriceCalculationService;
use Illuminate\Http\Request;

class ReadyProductController extends Controller
{
    /**
     * Gönderilmeye hazır ürünler listesi
     */
    public function index(Request $request)
    {
        $query = Product::with([
                'brand.originCountry', 
                'category.trendyolCategory', 
                'variants', 
                'images', 
                'productAttributes.attribute', 
                'productAttributes.attributeValue'
            ])
            ->where('status', 'READY');

        // Filtreleme
        if ($request->filled('sku')) {
            $query->where('sku', 'like', '%' . $request->sku . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);

        // Her ürün için API formatına uygun veri hazırla
        $productsWithApiData = $products->map(function ($product) {
            return [
                'product' => $product,
                'api_data' => $this->prepareApiData($product),
                'price_details' => $this->getPriceCalculationDetails($product, 'trendyol'),
            ];
        });

        // Kategorileri filtreleme için al
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('admin.ready-products.index', compact('products', 'productsWithApiData', 'categories'));
    }

    /**
     * Mevcut IMPORTED ürünleri kontrol et ve READY'ye çevir
     */
    public function checkAndUpdateStatus(Request $request)
    {
        $categoryId = $request->input('category_id');
        
        $query = Product::with(['productAttributes.attribute', 'productAttributes.attributeValue', 'brand.originCountry', 'category'])
            ->where('status', 'IMPORTED');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->get();
        $service = new ProductAttributePersistenceService();
        
        $updated = 0;
        $skipped = 0;
        $details = [];

        foreach ($products as $product) {
            if ($service->hasAllRequiredAttributes($product)) {
                $product->update(['status' => 'READY']);
                $updated++;
                $details[] = [
                    'sku' => $product->sku,
                    'title' => $product->title,
                    'status' => 'READY',
                    'category' => $product->category ? $product->category->name : 'N/A',
                ];
            } else {
                $skipped++;
                // Eksik attribute'ları bul
                $missing = $this->getMissingRequiredAttributes($product);
                $details[] = [
                    'sku' => $product->sku,
                    'title' => $product->title,
                    'status' => 'IMPORTED (eksik özellikler)',
                    'category' => $product->category ? $product->category->name : 'N/A',
                    'missing' => $missing,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Kontrol tamamlandı. {$updated} ürün READY'ye çevrildi, {$skipped} ürün eksik özellik nedeniyle IMPORTED olarak kaldı.",
            'updated' => $updated,
            'skipped' => $skipped,
            'details' => $details,
        ]);
    }

    /**
     * Eksik required attribute'ları bul
     */
    private function getMissingRequiredAttributes(Product $product): array
    {
        $missing = [];

        if (!$product->category_id) {
            return ['Kategori tanımlı değil'];
        }

        $requiredAttributes = CategoryAttribute::where('category_id', $product->category_id)
            ->where('is_required', true)
            ->with('attribute')
            ->get();

        $productAttributesMap = [];
        if ($product->productAttributes) {
            foreach ($product->productAttributes as $productAttribute) {
                $productAttributesMap[$productAttribute->attribute_id] = $productAttribute;
            }
        }

        foreach ($requiredAttributes as $categoryAttribute) {
            $attribute = $categoryAttribute->attribute;
            if (!$attribute) {
                continue;
            }

            $isMenşei = false;
            if (strtolower(trim($attribute->name)) === 'menşei' || 
                strtolower(trim($attribute->code)) === 'menşei' ||
                strtolower(trim($attribute->code)) === 'mensei') {
                $isMenşei = true;
            }

            if ($isMenşei && $product->brand && $product->brand->originCountry) {
                continue;
            }

            if (!isset($productAttributesMap[$attribute->id])) {
                $missing[] = $attribute->name . ' (product_attributes tablosunda yok)';
            } else {
                $productAttribute = $productAttributesMap[$attribute->id];
                $hasValue = false;
                if ($productAttribute->attribute_value_id !== null) {
                    $hasValue = true;
                } elseif ($productAttribute->value_string !== null && trim($productAttribute->value_string) !== '') {
                    $hasValue = true;
                } elseif ($productAttribute->value_number !== null) {
                    $hasValue = true;
                }

                if (!$hasValue) {
                    $missing[] = $attribute->name . ' (değer boş)';
                }
            }
        }

        return $missing;
    }

    /**
     * Ürünü API formatına çevir
     */
    private function prepareApiData(Product $product): array
    {
        // Temel bilgiler
        $data = [
            'barcode' => $this->getBarcodeWithPrefix($product),
            'title' => $product->title ?: 'Sistemde Veri yok',
            'productMainId' =>(string) $product->id,
            'brandId' => $product->brand_id ?: 'Sistemde Veri yok',
            'categoryId' => $product->category_id ?: 'Sistemde Veri yok',
            'quantity' => $this->getTotalStock($product),
            'stockCode' => $product->sku ?: 'Sistemde Veri yok',
            'dimensionalWeight' => (int)$product->desi ?? 'Sistemde Veri yok',
            'description' => $product->description ?: 'Sistemde Veri yok',
            'currencyType' => $product->currency ?: 'TRY',
            'listPrice' => $this->getSalePrice($product, 'trendyol'),
            'salePrice' => $this->getSalePrice($product, 'trendyol'),
            'vatRate' => $this->getVatRate($product),
            'cargoCompanyId' => $this->getCargoCompanyId($product),
            
           
            'deliveryDuration' => 1,
            'images' => $this->prepareImages($product),
            'attributes' => $this->prepareAttributes($product),
        ];

        return $data;
    }

    /**
     * Toplam stok miktarını hesapla
     * Ürünün sistemdeki adet miktarını döndürür (variant'ların stock toplamı)
     */
    private function getTotalStock(Product $product): int
    {
        if ($product->variants && $product->variants->count() > 0) {
            return (int) $product->variants->sum('stock');
        }
        return 0;
    }

    /**
     * Satış fiyatını al
     * Fiyat hesaplama servisi kullanılarak güncel fiyat hesaplanır
     */
    private function getSalePrice(Product $product, ?string $marketplaceSlug = 'trendyol'): string|float
    {
        // Product'ı category ve variants ile yükle (fiyat hesaplama için gerekli)
        $product->load('category', 'variants.currencyRelation');
        
        // Base price'ı al (Fiyat_Ozel'den TRY'ye çevrilmiş)
        $basePrice = 0;
        if ($product->variants && $product->variants->count() > 0) {
            $firstVariant = $product->variants->first();
            // price_ozel'den base price'ı hesapla
            if ($firstVariant->price_ozel !== null && $firstVariant->price_ozel > 0) {
                // Currency bilgisini al
                $currencyCode = $firstVariant->currency ?? 'TRY';
                $currency = $firstVariant->currencyRelation;
                
                if ($currencyCode === 'TRY') {
                    $basePrice = (float) $firstVariant->price_ozel;
                } else {
                    // Döviz cinsinden, TRY'ye çevir
                    if ($currency && $currency->rate_to_try > 0) {
                        $basePrice = (float) $firstVariant->price_ozel * $currency->rate_to_try;
                    } else {
                        // Kur yoksa, mevcut price'ı kullan (zaten hesaplanmış olabilir)
                        $basePrice = (float) $firstVariant->price;
                    }
                }
            } else {
                // price_ozel yoksa, mevcut price'ı kullan
                $basePrice = (float) $firstVariant->price;
            }
        } else {
            // Variant yoksa reference_price kullan
            $basePrice = (float) ($product->reference_price ?? 0);
        }
        
        // Base price 0 ise hata döndür
        if ($basePrice <= 0) {
            return 'Sistemde Veri yok';
        }
        
        // Fiyat hesaplama servisini kullan
        try {
            $priceCalculationService = new ProductPriceCalculationService();
            $finalPrice = $priceCalculationService->calculatePrice($product, $basePrice, $marketplaceSlug);
            return $finalPrice;
        } catch (\Exception $e) {
            // Hata durumunda mevcut price'ı döndür
            if ($product->variants && $product->variants->count() > 0) {
                $firstVariant = $product->variants->first();
                return $firstVariant->price ?: 'Sistemde Veri yok';
            }
            return $product->reference_price ?: 'Sistemde Veri yok';
        }
    }

    /**
     * Resimleri hazırla
     */
    private function prepareImages(Product $product): array
    {
        if ($product->images && $product->images->count() > 0) {
            return $product->images->map(function ($image) {
                return [
                    'url' => $image->url,
                ];
            })->toArray();
        }
        return [];
    }

    /**
     * Pazaryeri için varsayılan kargo şirketinin external_id değerini al
     * Varsayılan olarak Trendyol kullanılır
     */
    private function getCargoCompanyId(Product $product, string $marketplaceSlug = 'trendyol'): string|int
    {
        // Pazaryerini bul
        $marketplace = Marketplace::where('slug', $marketplaceSlug)->first();
        
        if (!$marketplace) {
            return 'Sistemde Veri yok';
        }

        // Pazaryeri için varsayılan kargo şirketini bul
        $defaultShippingCompany = MarketplaceShippingCompanyMapping::where('marketplace_id', $marketplace->id)
            ->where('is_default', true)
            ->where('status', 'active')
            ->first();

        if (!$defaultShippingCompany || !$defaultShippingCompany->external_id) {
            return 'Sistemde Veri yok';
        }

        return $defaultShippingCompany->external_id;
    }

    /**
     * Barkod'a pazaryeri prefix'ini ekle
     * Varsayılan olarak Trendyol kullanılır
     */
    private function getBarcodeWithPrefix(Product $product, string $marketplaceSlug = 'trendyol'): string
    {
        $barcode = $product->barcode;
        
        if (!$barcode || $barcode === 'Sistemde Veri yok') {
            return 'Sistemde Veri yok';
        }

        // Pazaryeri için barcode prefix'ini al
        $prefix = MarketplaceConfig::get($marketplaceSlug, 'barcode_prefix', null);
        
        // Prefix varsa ekle
        if ($prefix && trim($prefix) !== '') {
            return trim($prefix) . $barcode;
        }

        // Prefix yoksa orijinal barcode'u döndür
        return $barcode;
    }

    /**
     * Attribute'ları hazırla - Required attribute'lar dahil
     * product_attributes tablosundan verileri çeker (SINGLE source of truth)
     */
    private function prepareAttributes(Product $product): array
    {
        $attributes = [];

        // Kategoriye göre required attribute'ları al
        if ($product->category_id) {
            $requiredAttributes = CategoryAttribute::where('category_id', $product->category_id)
                ->where('is_required', true)
                ->with('attribute') // Attribute'ları eager load et (external_id için)
                ->get();

            // Product'ın product_attributes tablosundan verileri al (SINGLE source of truth)
            $productAttributesMap = [];
            if ($product->productAttributes) {
                foreach ($product->productAttributes as $productAttribute) {
                    $productAttributesMap[$productAttribute->attribute_id] = $productAttribute;
                }
            }

            // Fallback: Eğer product_attributes'da yoksa raw_xml'den çıkar (backward compatibility)
            $rawXmlAttributes = [];
            if (empty($productAttributesMap)) {
                $rawXmlAttributes = $this->extractProductAttributes($product);
            }

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

                // Menşei özelliği ve brand->originCountry mevcut ise
                if ($isMenşei && $product->brand && $product->brand->originCountry) {
                    // Menşei bilgisi mevcut, AttributeValue'da ara veya custom değer olarak ekle
                    $originCountryName = $product->brand->originCountry->name;
                    $normalizedOrigin = $this->normalizeValue($originCountryName);
                    
                    // AttributeValue'da ara
                    $attributeValue = AttributeValue::where('attribute_id', $attribute->id)
                        ->where('normalized_value', $normalizedOrigin)
                        ->where('status', 'active')
                        ->first();
                    
                    if ($attributeValue) {
                        $attributes[] = [
                            'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                            'attributeValueId' => $attributeValue->external_id ?? $attributeValue->id, // Trendyol attribute value ID
                        ];
                    } else {
                        // Custom değer olarak ekle
                        $attributes[] = [
                            'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                            'customAttributeValue' => $originCountryName,
                        ];
                    }
                    continue;
                }

                // product_attributes tablosundan değeri bul (SINGLE source of truth)
                if (isset($productAttributesMap[$attribute->id])) {
                    $productAttribute = $productAttributesMap[$attribute->id];
                    
                    // Data type'a göre değeri al
                    if ($productAttribute->attribute_value_id) {
                        // Enum değer - AttributeValue'yu yükle
                        $attributeValue = AttributeValue::find($productAttribute->attribute_value_id);
                        $attributes[] = [
                            'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                            'attributeValueId' => $attributeValue ? ($attributeValue->external_id ?? $attributeValue->id) : $productAttribute->attribute_value_id, // Trendyol attribute value ID
                        ];
                    } elseif ($productAttribute->value_string !== null) {
                        // String değer
                        $attributes[] = [
                            'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                            'customAttributeValue' => $productAttribute->value_string,
                        ];
                    } elseif ($productAttribute->value_number !== null) {
                        // Number değer (string olarak gönder)
                        $attributes[] = [
                            'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                            'customAttributeValue' => (string) $productAttribute->value_number,
                        ];
                    } else {
                        // Değer yok
                        $attributes[] = [
                            'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                            'customAttributeValue' => 'Sistemde Veri yok',
                        ];
                    }
                } else {
                    // product_attributes'da yok, fallback olarak raw_xml'den çıkar
                    $attributeValue = $this->findAttributeValue($product, $attribute->id, $rawXmlAttributes);

                    if ($attributeValue) {
                        // AttributeValue ID varsa onu kullan
                        if (isset($attributeValue['attribute_value_id'])) {
                            $attrValue = AttributeValue::find($attributeValue['attribute_value_id']);
                            $attributes[] = [
                                'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                                'attributeValueId' => $attrValue ? ($attrValue->external_id ?? $attrValue->id) : $attributeValue['attribute_value_id'], // Trendyol attribute value ID
                            ];
                        } else {
                            // Custom değer kullan
                            $attributes[] = [
                                'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                                'customAttributeValue' => $attributeValue['value'] ?? 'Sistemde Veri yok',
                            ];
                        }
                    } else {
                        // Değer bulunamadı - Sistemde Veri yok olarak ekle
                        $attributes[] = [
                            'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                            'customAttributeValue' => 'Sistemde Veri yok',
                        ];
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * Product'ın raw_xml'inden attribute değerlerini çıkar
     */
    private function extractProductAttributes(Product $product): array
    {
        $attributes = [];

        if (!$product->raw_xml || !is_array($product->raw_xml)) {
            return $attributes;
        }

        // XML attribute mapping'lerini al
        $mappings = XmlAttributeMapping::where('source_type', 'xml')
            ->where('status', 'active')
            ->with('attribute')
            ->get()
            ->keyBy('source_attribute_key');

        // TeknikOzellikler bölümünden attribute'ları çıkar
        if (isset($product->raw_xml['TeknikOzellikler']) && is_array($product->raw_xml['TeknikOzellikler'])) {
            foreach ($product->raw_xml['TeknikOzellikler'] as $key => $value) {
                if (isset($mappings[$key])) {
                    $mapping = $mappings[$key];
                    $attributeId = $mapping->attribute_id;

                    // Değeri normalize et ve AttributeValue'da ara
                    $normalizedValue = $this->normalizeValue($value);
                    $attributeValue = AttributeValue::where('attribute_id', $attributeId)
                        ->where('normalized_value', $normalizedValue)
                        ->where('status', 'active')
                        ->first();

                    if ($attributeValue) {
                        $attributes[$attributeId] = [
                            'value' => $value,
                            'normalized_value' => $normalizedValue,
                            'attribute_value_id' => $attributeValue->id,
                        ];
                    } else {
                        // Custom değer
                        $attributes[$attributeId] = [
                            'value' => $value,
                            'normalized_value' => $normalizedValue,
                        ];
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * Attribute değerini bul
     */
    private function findAttributeValue(Product $product, int $attributeId, array $productAttributes): ?array
    {
        if (isset($productAttributes[$attributeId])) {
            return $productAttributes[$attributeId];
        }

        return null;
    }

    /**
     * Değeri normalize et
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
     * KDV oranını hesapla
     * Öncelik sırası: 1) Ürüne özel, 2) Kategori bazlı, 3) Genel (default 20)
     */
    private function getVatRate(Product $product, string $marketplaceSlug = 'trendyol'): int
    {
        // 1. Ürüne özel KDV
        if ($product->vat_rate !== null) {
            return (int) $product->vat_rate;
        }

        // 2. Kategori bazlı KDV
        if ($product->category && $product->category->vat_rate !== null) {
            return (int) $product->category->vat_rate;
        }

        // 3. Genel KDV (marketplace settings'den, default 20)
        $defaultVatRate = MarketplaceConfig::get($marketplaceSlug, 'default_vat_rate', '20');
        return (int) $defaultVatRate;
    }

    /**
     * Fiyat hesaplama detaylarını al
     */
    private function getPriceCalculationDetails(Product $product, ?string $marketplaceSlug = 'trendyol'): ?array
    {
        // Product'ı category ve variants ile yükle
        $product->load('category', 'variants.currencyRelation');
        
        // Base price'ı al (Fiyat_Ozel'den TRY'ye çevrilmiş)
        $basePrice = 0;
        if ($product->variants && $product->variants->count() > 0) {
            $firstVariant = $product->variants->first();
            // price_ozel'den base price'ı hesapla
            if ($firstVariant->price_ozel !== null && $firstVariant->price_ozel > 0) {
                // Currency bilgisini al
                $currencyCode = $firstVariant->currency ?? 'TRY';
                $currency = $firstVariant->currencyRelation;
                
                if ($currencyCode === 'TRY') {
                    $basePrice = (float) $firstVariant->price_ozel;
                } else {
                    // Döviz cinsinden, TRY'ye çevir
                    if ($currency && $currency->rate_to_try > 0) {
                        $basePrice = (float) $firstVariant->price_ozel * $currency->rate_to_try;
                    } else {
                        // Kur yoksa, mevcut price'ı kullan
                        $basePrice = (float) $firstVariant->price;
                    }
                }
            } else {
                // price_ozel yoksa, mevcut price'ı kullan
                $basePrice = (float) $firstVariant->price;
            }
        } else {
            // Variant yoksa reference_price kullan
            $basePrice = (float) ($product->reference_price ?? 0);
        }
        
        // Base price 0 ise null döndür
        if ($basePrice <= 0) {
            return null;
        }
        
        // Fiyat hesaplama servisini kullan
        try {
            $priceCalculationService = new ProductPriceCalculationService();
            $details = $priceCalculationService->calculatePriceWithDetails($product, $basePrice, $marketplaceSlug);
            
            // Oran kaynaklarını ekle
            $details['vat_rate_source'] = $this->getVatRateSource($product);
            $details['marketplace_category_commission_source'] = $this->getMarketplaceCategoryCommissionSource($product, $marketplaceSlug);
            
            return $details;
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * KDV oranı kaynağını al
     */
    private function getVatRateSource(Product $product): string
    {
        if ($product->vat_rate !== null) {
            return 'Ürüne Özel';
        }
        if ($product->category && $product->category->vat_rate !== null) {
            return 'Kategori Bazlı';
        }
        return 'Genel';
    }

    /**
     * Komisyon oranı kaynağını al
     */

    /**
     * Pazaryeri kategori komisyon kaynağını al
     */
    private function getMarketplaceCategoryCommissionSource(Product $product, ?string $marketplaceSlug = null): string
    {
        if (!$marketplaceSlug || !$product->category_id) {
            return 'Uygulanmadı';
        }
        
        $marketplace = Marketplace::where('slug', $marketplaceSlug)->first();
        if (!$marketplace) {
            return 'Uygulanmadı';
        }
        
        $marketplaceCategory = \App\Models\MarketplaceCategory::where('marketplace_id', $marketplace->id)
            ->where('global_category_id', $product->category_id)
            ->where('is_mapped', true)
            ->first();
        
        if ($marketplaceCategory && $marketplaceCategory->commission_rate !== null) {
            return 'Pazaryeri Kategori';
        }
        
        return 'Uygulanmadı';
    }
}

