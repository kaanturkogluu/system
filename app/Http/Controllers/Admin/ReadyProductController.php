<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\CategoryAttribute;
use App\Models\AttributeValue;
use App\Models\XmlAttributeMapping;
use App\Models\Marketplace;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceCountryMapping;
use App\Models\MarketplaceShippingCompanyMapping;
use App\Models\TrendyolProductRequest;
use App\Models\Country;
use App\Helpers\MarketplaceConfig;
use App\Services\ProductAttributePersistenceService;
use App\Services\ProductPriceCalculationService;
use App\Services\TrendyolProductService;
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
            'brandId' => $this->getTrendyolBrandId($product),
            'categoryId' => $this->getTrendyolCategoryId($product),
            'quantity' => $this->getTotalStock($product),
            'stockCode' => $product->sku ?: 'Sistemde Veri yok',
            'dimensionalWeight' => (int)$product->desi ?? 'Sistemde Veri yok',
            'description' => $this->truncateDescription($product->description),
            'currencyType' => $product->currency ?: 'TRY',
            'listPrice' => $this->getSalePrice($product, 'trendyol'),
            'salePrice' => $this->getSalePrice($product, 'trendyol'),
            'vatRate' => $this->getVatRate($product),
            'cargoCompanyId' => $this->getCargoCompanyId($product),
            'images' => $this->prepareImages($product),
            'attributes' => $this->prepareAttributes($product),
        ];

        return $data;
    }

    /**
     * Description'ı ilk 5 karakter + ... olacak şekilde kısalt
     */
    private function truncateDescription(?string $description): string
    {
        if (empty($description)) {
            return 'Sistemde Veri yok';
        }

        if (mb_strlen($description) <= 5) {
            return $description;
        }

        return mb_substr($description, 0, 5) . '...';
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

        // Ürünü brand ve originCountry ile yükle (menşei için gerekli)
        $product->load('brand.originCountry');

        // Kategoriye göre required attribute'ları al
        if ($product->category_id) {
            $requiredAttributes = CategoryAttribute::where('category_id', $product->category_id)
                ->where('is_required', true)
                ->with('attribute') // Attribute'ları eager load et (external_id için)
                ->get();
            
            // Menşei özelliğini de ekle (required olmasa bile)
            $menşeiAttribute = \App\Models\Attribute::where(function($query) {
                $query->whereRaw('LOWER(TRIM(name)) = ?', ['menşei'])
                      ->orWhereRaw('LOWER(TRIM(code)) = ?', ['menşei'])
                      ->orWhereRaw('LOWER(TRIM(code)) = ?', ['mensei']);
            })->first();
            
            // Menşei özelliği required attributes'te yoksa ekle
            if ($menşeiAttribute && !$requiredAttributes->contains('attribute_id', $menşeiAttribute->id)) {
                $requiredAttributes->push((object)[
                    'attribute' => $menşeiAttribute,
                    'is_required' => false,
                ]);
            }

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

                // Menşei özelliği için - Gönderilmeye hazır ürünlerde her zaman TR olarak gönder
                if ($isMenşei) {
                    // TR ülkesini bul
                    $trCountry = Country::where('code', 'TR')->where('status', 'active')->first();
                    
                    if ($trCountry) {
                        // Trendyol marketplace'ini bul
                        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();
                        
                        if ($trendyolMarketplace) {
                            // TR için Marketplace country mapping'i bul
                            $countryMapping = MarketplaceCountryMapping::where('marketplace_id', $trendyolMarketplace->id)
                                ->where('country_id', $trCountry->id)
                                ->where('status', 'active')
                                ->first();
                            
                            if ($countryMapping && $countryMapping->external_country_id) {
                                // Mapping'den Trendyol attribute value ID'sini kullan
                                $attributes[] = [
                                    'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                                    'attributeValueId' => $countryMapping->external_country_id, // Trendyol menşei value ID
                                ];
                                continue;
                            } elseif ($countryMapping && $countryMapping->external_country_code) {
                                // Mapping'den Trendyol country code'unu custom değer olarak kullan
                                $attributes[] = [
                                    'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                                    'customAttributeValue' => $countryMapping->external_country_code,
                                ];
                                continue;
                            }
                        }
                        
                        // Mapping yoksa, TR için AttributeValue'da ara veya custom değer olarak ekle
                        $trCountryName = $trCountry->name;
                        $normalizedOrigin = $this->normalizeValue($trCountryName);
                        
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
                            // Custom değer olarak TR ekle
                            $attributes[] = [
                                'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                                'customAttributeValue' => 'TR',
                            ];
                        }
                        continue;
                    } else {
                        // TR ülkesi bulunamadıysa, direkt TR olarak ekle
                        $attributes[] = [
                            'attributeId' => $attribute->external_id ?? $attribute->id, // Trendyol attribute ID
                            'customAttributeValue' => 'TR',
                        ];
                        continue;
                    }
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
        
        // Menşei bilgisini her zaman ekle (required attributes listesinde olmasa bile)
        // Gönderilmeye hazır ürünlerde her zaman TR olarak gönder
        // Menşei özelliğini bul - Trendyol'da menşei attribute ID'si genellikle 1192
        // Önce external_id = 1192 olanı ara, yoksa name/code ile ara
        $menşeiAttribute = Attribute::where('external_id', 1192)
            ->orWhere(function($query) {
                $query->whereRaw('LOWER(TRIM(name)) = ?', ['menşei'])
                      ->orWhereRaw('LOWER(TRIM(code)) = ?', ['menşei'])
                      ->orWhereRaw('LOWER(TRIM(code)) = ?', ['mensei'])
                      ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', ['%menşei%'])
                      ->orWhereRaw('LOWER(TRIM(name)) LIKE ?', ['%mensei%']);
            })
            ->first();
        
        // Eğer menşei özelliği bulunamadıysa, external_id = 1192 ile oluştur veya kullan
        if (!$menşeiAttribute) {
            // Trendyol menşei attribute ID'si 1192
            $menşeiAttribute = Attribute::firstOrCreate(
                ['external_id' => 1192],
                [
                    'name' => 'Menşei',
                    'code' => 'mensei',
                    'data_type' => 'enum',
                    'status' => 'active',
                ]
            );
        }
        
        if ($menşeiAttribute) {
            // Menşei zaten attributes array'inde var mı kontrol et
            $menşeiExists = false;
            $menşeiAttributeId = $menşeiAttribute->external_id ?? $menşeiAttribute->id;
            foreach ($attributes as $attr) {
                if (isset($attr['attributeId']) && 
                    ($attr['attributeId'] == $menşeiAttributeId || 
                     $attr['attributeId'] == $menşeiAttribute->external_id || 
                     $attr['attributeId'] == $menşeiAttribute->id)) {
                    $menşeiExists = true;
                    break;
                }
            }
            
            // Menşei yoksa TR olarak ekle
            if (!$menşeiExists) {
                // TR ülkesini bul
                $trCountry = Country::where('code', 'TR')->where('status', 'active')->first();
                
                if ($trCountry) {
                    // Trendyol marketplace'ini bul
                    $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();
                    
                    if ($trendyolMarketplace) {
                        // TR için Marketplace country mapping'i bul
                        $countryMapping = MarketplaceCountryMapping::where('marketplace_id', $trendyolMarketplace->id)
                            ->where('country_id', $trCountry->id)
                            ->where('status', 'active')
                            ->first();
                        
                        if ($countryMapping && $countryMapping->external_country_id) {
                            // Mapping'den Trendyol attribute value ID'sini kullan
                            $attributes[] = [
                                'attributeId' => $menşeiAttribute->external_id ?? 1192, // Trendyol menşei attribute ID
                                'attributeValueId' => $countryMapping->external_country_id, // Trendyol menşei value ID
                            ];
                        } elseif ($countryMapping && $countryMapping->external_country_code) {
                            // Mapping'den Trendyol country code'unu custom değer olarak kullan
                            $attributes[] = [
                                'attributeId' => $menşeiAttribute->external_id ?? 1192, // Trendyol menşei attribute ID
                                'customAttributeValue' => $countryMapping->external_country_code,
                            ];
                        } else {
                            // Mapping yoksa, TR için AttributeValue'da ara veya custom değer olarak ekle
                            $trCountryName = $trCountry->name;
                            $normalizedOrigin = $this->normalizeValue($trCountryName);
                            
                            // AttributeValue'da ara
                            $attributeValue = AttributeValue::where('attribute_id', $menşeiAttribute->id)
                                ->where('normalized_value', $normalizedOrigin)
                                ->where('status', 'active')
                                ->first();
                            
                            if ($attributeValue && $attributeValue->external_id) {
                                $attributes[] = [
                                    'attributeId' => $menşeiAttribute->external_id ?? 1192, // Trendyol menşei attribute ID
                                    'attributeValueId' => $attributeValue->external_id, // Trendyol attribute value ID
                                ];
                            } else {
                                // Custom değer olarak TR ekle
                                $attributes[] = [
                                    'attributeId' => $menşeiAttribute->external_id ?? 1192, // Trendyol menşei attribute ID
                                    'customAttributeValue' => 'TR',
                                ];
                            }
                        }
                    } else {
                        // Marketplace bulunamadı, custom değer olarak TR ekle
                        $attributes[] = [
                            'attributeId' => $menşeiAttribute->external_id ?? 1192, // Trendyol menşei attribute ID
                            'customAttributeValue' => 'TR',
                        ];
                    }
                } else {
                    // TR ülkesi bulunamadıysa, direkt TR olarak ekle
                    $attributes[] = [
                        'attributeId' => $menşeiAttribute->external_id ?? 1192, // Trendyol menşei attribute ID
                        'customAttributeValue' => 'TR',
                    ];
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

    /**
     * Trendyol kategori ID'sini al
     */
    private function getTrendyolCategoryId(Product $product): string|int
    {
        if (!$product->category_id) {
            return 'Sistemde Veri yok';
        }

        // Trendyol marketplace'ini bul
        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();
        if (!$trendyolMarketplace) {
            return 'Sistemde Veri yok';
        }

        // Category mapping'i bul
        $categoryMapping = MarketplaceCategory::where('marketplace_id', $trendyolMarketplace->id)
            ->where('global_category_id', $product->category_id)
            ->where('is_mapped', true)
            ->first();

        if ($categoryMapping && $categoryMapping->marketplace_category_id) {
            return (int) $categoryMapping->marketplace_category_id;
        }

        // Mapping yoksa sistem category ID'sini döndür
        return $product->category_id ?: 'Sistemde Veri yok';
    }

    /**
     * Trendyol marka ID'sini al
     */
    private function getTrendyolBrandId(Product $product): string|int
    {
        if (!$product->brand_id) {
            return 'Sistemde Veri yok';
        }

        // Trendyol marketplace'ini bul
        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();
        if (!$trendyolMarketplace) {
            return 'Sistemde Veri yok';
        }

        // Brand mapping'i bul
        $brandMapping = \App\Models\MarketplaceBrandMapping::where('marketplace_id', $trendyolMarketplace->id)
            ->where('brand_id', $product->brand_id)
            ->where('status', 'mapped')
            ->first();

        if ($brandMapping && $brandMapping->marketplace_brand_id) {
            return $brandMapping->marketplace_brand_id;
        }

        // Fallback to system brand ID if no mapped Trendyol brand ID is found
        return (int) $product->brand_id;
    }

    /**
     * Ürünleri Trendyol API'ye gönder
     */
    public function sendProducts(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $products = Product::with([
            'category',
            'variants.currencyRelation',
            'images',
            'productAttributes.attribute',
            'productAttributes.attributeValue',
            'brand'
        ])
        ->whereIn('id', $request->product_ids)
        ->where('status', 'READY')
        ->get();

        if ($products->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Gönderilecek ürün bulunamadı.',
            ], 400);
        }

        // Ürünleri API formatına çevir
        $items = $products->map(function ($product) {
            return $this->prepareApiData($product);
        })->toArray();

        // Her ürün için ayrı kayıt oluştur (pending durumunda)
        $productRequests = [];
        foreach ($products as $product) {
            $productRequest = TrendyolProductRequest::create([
                'product_id' => $product->id,
                'request_data' => ['items' => [$this->prepareApiData($product)]],
                'status' => 'pending',
                'items_count' => 1,
                'sent_at' => now(),
            ]);
            $productRequests[] = $productRequest;
        }

        // Trendyol API'ye gönder (items array'i içinde gönder)
        $trendyolService = new TrendyolProductService();
        $response = $trendyolService->sendProducts(['items' => $items]);

        // Tüm kayıtları güncelle
        if (!$response || !$response['success']) {
            foreach ($productRequests as $productRequest) {
                $productRequest->update([
                    'status' => 'failed',
                    'error_message' => $response['error'] ?? 'Bilinmeyen hata',
                    'response_data' => $response,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Ürünler Trendyol API\'ye gönderilemedi.',
                'error' => $response['error'] ?? 'Bilinmeyen hata',
                'request_ids' => array_map(fn($r) => $r->id, $productRequests),
            ], 500);
        }

        // batchRequestId'yi al
        $batchRequestId = $response['data']['batchRequestId'] ?? null;

        if (!$batchRequestId) {
            foreach ($productRequests as $productRequest) {
                $productRequest->update([
                    'status' => 'failed',
                    'error_message' => 'Batch Request ID alınamadı',
                    'response_data' => $response['data'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Batch Request ID alınamadı.',
                'response' => $response['data'],
                'request_ids' => array_map(fn($r) => $r->id, $productRequests),
            ], 500);
        }

        // Tüm kayıtları güncelle (sent durumunda)
        foreach ($productRequests as $productRequest) {
            $productRequest->update([
                'batch_request_id' => $batchRequestId,
                'status' => 'sent',
                'response_data' => $response['data'],
            ]);
        }

        // Batch status kontrolünü otomatik olarak yap (arka planda)
        // Trendyol API'nin batch'ı işlemesi için kısa bir süre bekleyelim
        $this->checkBatchStatusAsync($batchRequestId);

        return response()->json([
            'success' => true,
            'message' => 'Ürünler başarıyla gönderildi. Batch durumu kontrol ediliyor...',
            'batchRequestId' => $batchRequestId,
            'response' => $response['data'],
            'request_ids' => array_map(fn($r) => $r->id, $productRequests),
        ]);
    }

    /**
     * Batch status kontrolünü arka planda yap
     */
    private function checkBatchStatusAsync(string $batchRequestId): void
    {
        // Closure'ı dispatch et (arka planda çalışır)
        dispatch(function () use ($batchRequestId) {
            // Trendyol API'nin batch'ı işlemesi için 3 saniye bekle
            sleep(3);
            
            // Batch status kontrolü yap
            $trendyolService = new TrendyolProductService();
            $response = $trendyolService->checkBatchStatus($batchRequestId);
            
            if (!$response || !$response['success']) {
                return;
            }
            
            $batchData = $response['data'];
            
            // Batch data'nın tam olarak kaydedildiğinden emin olmak için log ekle
            \Log::channel('imports')->debug('Batch Status Data (Async)', [
                'batch_request_id' => $batchRequestId,
                'data_size' => strlen(json_encode($batchData)),
                'data_keys' => array_keys($batchData ?? []),
            ]);
            
            // Aynı batch_request_id'ye sahip tüm kayıtları bul
            $productRequests = TrendyolProductRequest::where('batch_request_id', $batchRequestId)->get();
            
            if ($productRequests->isEmpty()) {
                return;
            }
            
            // Batch durumuna göre status belirle
            $batchStatus = null;
            $batchFailureReasons = [];
            
            if (isset($batchData['status'])) {
                $batchStatus = strtolower($batchData['status']);
                
                if ($batchStatus === 'failed' && isset($batchData['failureReasons']) && is_array($batchData['failureReasons'])) {
                    $batchFailureReasons = $batchData['failureReasons'];
                }
            }

            // Items array'ini kontrol et
            $itemsStatus = [];
            if (isset($batchData['items']) && is_array($batchData['items'])) {
                foreach ($batchData['items'] as $item) {
                    if (isset($item['status'])) {
                        $itemStatus = strtolower($item['status']);
                        $itemFailureReasons = [];
                        
                        if ($itemStatus === 'failed' && isset($item['failureReasons']) && is_array($item['failureReasons'])) {
                            $itemFailureReasons = $item['failureReasons'];
                        }
                        
                        if (isset($item['productMainId'])) {
                            $itemsStatus[$item['productMainId']] = [
                                'status' => $itemStatus,
                                'failureReasons' => $itemFailureReasons,
                            ];
                        }
                    }
                }
            }

            // Her kayıt için güncelleme yap
            foreach ($productRequests as $productRequest) {
                $status = 'sent';
                $errorMessage = null;
                $successCount = 0;
                $failedCount = 0;

                if ($batchStatus) {
                    if (in_array($batchStatus, ['completed', 'success'])) {
                        $status = 'success';
                    } elseif (in_array($batchStatus, ['failed', 'error'])) {
                        $status = 'failed';
                        if (!empty($batchFailureReasons)) {
                            $errorMessage = 'Batch Hataları: ' . implode(' | ', $batchFailureReasons);
                        }
                    } elseif ($batchStatus === 'partial') {
                        $status = 'partial';
                    }
                }

                // Bu ürün için item durumunu kontrol et
                if ($productRequest->product_id) {
                    $product = Product::find($productRequest->product_id);
                    if ($product) {
                        $productMainId = (string) $product->id;
                        if (isset($itemsStatus[$productMainId])) {
                            $itemStatusInfo = $itemsStatus[$productMainId];
                            
                            if ($itemStatusInfo['status'] === 'failed') {
                                $status = 'failed';
                                if (!empty($itemStatusInfo['failureReasons'])) {
                                    $itemErrorMsg = 'Ürün Hataları: ' . implode(' | ', $itemStatusInfo['failureReasons']);
                                    $errorMessage = $errorMessage ? $errorMessage . "\n" . $itemErrorMsg : $itemErrorMsg;
                                }
                            } elseif ($itemStatusInfo['status'] === 'success' && $status !== 'failed') {
                                $status = 'success';
                            }
                        }
                    }
                }

                // Başarılı/başarısız sayılarını hesapla
                if (isset($batchData['items'])) {
                    foreach ($batchData['items'] as $item) {
                        if (isset($item['status'])) {
                            if (strtolower($item['status']) === 'success') {
                                $successCount++;
                            } else {
                                $failedCount++;
                            }
                        }
                    }
                }

                // Batch status data'yı tam olarak kaydet (JSON encoding ile)
                // Laravel otomatik olarak JSON'a çevirir ama manuel encoding ile garantileyelim
                $batchStatusDataJson = json_encode($batchData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                
                $updateData = [
                    'batch_status_data' => json_decode($batchStatusDataJson, true), // Array olarak kaydet
                    'status' => $status,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                ];

                if ($errorMessage) {
                    $updateData['error_message'] = $errorMessage;
                }

                if ($status === 'success' || $status === 'failed' || $status === 'partial') {
                    $updateData['completed_at'] = now();
                }

                $productRequest->update($updateData);
            }
        })->afterResponse();
    }

    /**
     * Batch request durumunu kontrol et
     */
    public function checkBatchStatus(Request $request, string $batchRequestId)
    {
        // Kaydı bul
        $productRequest = TrendyolProductRequest::where('batch_request_id', $batchRequestId)->first();

        $trendyolService = new TrendyolProductService();
        $response = $trendyolService->checkBatchStatus($batchRequestId);

        if (!$response || !$response['success']) {
            if ($productRequest) {
                $productRequest->update([
                    'error_message' => $response['error'] ?? 'Bilinmeyen hata',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Batch durumu kontrol edilemedi.',
                'error' => $response['error'] ?? 'Bilinmeyen hata',
            ], 500);
        }

        $batchData = $response['data'];
        
        // Batch data'nın tam olarak kaydedildiğinden emin olmak için log ekle
        \Log::channel('imports')->debug('Batch Status Data', [
            'batch_request_id' => $batchRequestId,
            'data_size' => strlen(json_encode($batchData)),
            'data_keys' => array_keys($batchData ?? []),
        ]);

        // Aynı batch_request_id'ye sahip tüm kayıtları bul
        $productRequests = TrendyolProductRequest::where('batch_request_id', $batchRequestId)->get();

        if ($productRequests->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu batch request ID\'ye ait kayıt bulunamadı.',
            ], 404);
        }

        // Batch durumuna göre status belirle
        $batchStatus = null;
        $batchFailureReasons = [];
        
        if (isset($batchData['status'])) {
            $batchStatus = strtolower($batchData['status']);
            
            // Batch seviyesinde FAILED durumu ve failureReasons kontrolü
            if ($batchStatus === 'failed' && isset($batchData['failureReasons']) && is_array($batchData['failureReasons'])) {
                $batchFailureReasons = $batchData['failureReasons'];
            }
        }

        // Items array'ini kontrol et
        $itemsStatus = [];
        if (isset($batchData['items']) && is_array($batchData['items'])) {
            foreach ($batchData['items'] as $item) {
                if (isset($item['status'])) {
                    $itemStatus = strtolower($item['status']);
                    $itemFailureReasons = [];
                    
                    // Item seviyesinde FAILED durumu ve failureReasons kontrolü
                    if ($itemStatus === 'failed' && isset($item['failureReasons']) && is_array($item['failureReasons'])) {
                        $itemFailureReasons = $item['failureReasons'];
                    }
                    
                    // Item'ın productMainId'sine göre kaydet
                    if (isset($item['productMainId'])) {
                        $itemsStatus[$item['productMainId']] = [
                            'status' => $itemStatus,
                            'failureReasons' => $itemFailureReasons,
                        ];
                    }
                }
            }
        }

        // Her kayıt için güncelleme yap
        foreach ($productRequests as $productRequest) {
            $status = 'sent';
            $errorMessage = null;
            $successCount = 0;
            $failedCount = 0;

            // Batch durumuna göre status belirle
            if ($batchStatus) {
                if (in_array($batchStatus, ['completed', 'success'])) {
                    $status = 'success';
                } elseif (in_array($batchStatus, ['failed', 'error'])) {
                    $status = 'failed';
                    // Batch seviyesinde failureReasons varsa error_message'a yaz
                    if (!empty($batchFailureReasons)) {
                        $errorMessage = 'Batch Hataları: ' . implode(' | ', $batchFailureReasons);
                    }
                } elseif ($batchStatus === 'partial') {
                    $status = 'partial';
                }
            }

            // Bu ürün için item durumunu kontrol et
            if ($productRequest->product_id) {
                $product = Product::find($productRequest->product_id);
                if ($product) {
                    // productMainId olarak product->id kullanılıyor
                    $productMainId = (string) $product->id;
                    if (isset($itemsStatus[$productMainId])) {
                        $itemStatusInfo = $itemsStatus[$productMainId];
                        
                        // Item seviyesinde FAILED ise status'u failed yap (batch seviyesindeki durumu override eder)
                        if ($itemStatusInfo['status'] === 'failed') {
                            $status = 'failed';
                            
                            // Item seviyesinde failureReasons varsa error_message'a yaz
                            if (!empty($itemStatusInfo['failureReasons'])) {
                                $itemErrorMsg = 'Ürün Hataları: ' . implode(' | ', $itemStatusInfo['failureReasons']);
                                $errorMessage = $errorMessage ? $errorMessage . "\n" . $itemErrorMsg : $itemErrorMsg;
                            }
                        } elseif ($itemStatusInfo['status'] === 'success' && $status !== 'failed') {
                            // Item seviyesinde success ise ve batch seviyesinde failed değilse success yap
                            $status = 'success';
                        }
                    }
                }
            }

            // Başarılı/başarısız sayılarını hesapla
            if (isset($batchData['items'])) {
                foreach ($batchData['items'] as $item) {
                    if (isset($item['status'])) {
                        if (strtolower($item['status']) === 'success') {
                            $successCount++;
                        } else {
                            $failedCount++;
                        }
                    }
                }
            }

            // Batch status data'yı tam olarak kaydet (JSON encoding ile)
            // Laravel otomatik olarak JSON'a çevirir ama manuel encoding ile garantileyelim
            $batchStatusDataJson = json_encode($batchData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            $updateData = [
                'batch_status_data' => json_decode($batchStatusDataJson, true), // Array olarak kaydet
                'status' => $status,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
            ];

            // Error message ekle
            if ($errorMessage) {
                $updateData['error_message'] = $errorMessage;
            }

            // Tamamlanma zamanını ayarla
            if ($status === 'success' || $status === 'failed' || $status === 'partial') {
                $updateData['completed_at'] = now();
            }

            $productRequest->update($updateData);
        }

        return response()->json([
            'success' => true,
            'data' => $batchData,
            'request_id' => $productRequest?->id,
        ]);
    }

    /**
     * İstek detaylarını getir
     */
    public function getRequestDetails(string $requestId)
    {
        $request = TrendyolProductRequest::findOrFail($requestId);

        return response()->json([
            'success' => true,
            'request' => [
                'id' => $request->id,
                'product_id' => $request->product_id,
                'batch_request_id' => $request->batch_request_id,
                'status' => $request->status,
                'items_count' => $request->items_count,
                'success_count' => $request->success_count,
                'failed_count' => $request->failed_count,
                'sent_at' => $request->sent_at ? $request->sent_at->format('Y-m-d H:i:s') : null,
                'completed_at' => $request->completed_at ? $request->completed_at->format('Y-m-d H:i:s') : null,
                'error_message' => $request->error_message,
                'request_data' => $request->request_data,
                'response_data' => $request->response_data,
                'batch_status_data' => $request->batch_status_data,
            ],
        ]);
    }

    /**
     * Ürün için tüm istek geçmişini getir
     */
    public function getProductRequestHistory(int $productId)
    {
        $requests = TrendyolProductRequest::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'batch_request_id' => $request->batch_request_id,
                    'status' => $request->status,
                    'items_count' => $request->items_count,
                    'success_count' => $request->success_count,
                    'failed_count' => $request->failed_count,
                    'sent_at' => $request->sent_at ? $request->sent_at->format('Y-m-d H:i:s') : null,
                    'completed_at' => $request->completed_at ? $request->completed_at->format('Y-m-d H:i:s') : null,
                    'error_message' => $request->error_message,
                    'request_data' => $request->request_data,
                    'response_data' => $request->response_data,
                    'batch_status_data' => $request->batch_status_data,
                    'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'requests' => $requests,
        ]);
    }

    /**
     * Ürün için batch detaylarını göster
     */
    public function showBatchDetails(int $productId)
    {
        $product = Product::with(['brand', 'category', 'variants', 'images'])
            ->findOrFail($productId);

        // Bu ürüne ait tüm batch isteklerini getir
        $requests = TrendyolProductRequest::where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.ready-products.batch-details', compact('product', 'requests'));
    }
}

