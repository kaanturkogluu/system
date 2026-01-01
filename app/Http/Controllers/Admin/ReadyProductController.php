<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Models\AttributeValue;
use App\Models\XmlAttributeMapping;
use Illuminate\Http\Request;

class ReadyProductController extends Controller
{
    /**
     * Gönderilmeye hazır ürünler listesi
     */
    public function index(Request $request)
    {
        $query = Product::with(['brand.originCountry', 'category', 'variants', 'images'])
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
            ];
        });

        // Kategorileri filtreleme için al
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('admin.ready-products.index', compact('products', 'productsWithApiData', 'categories'));
    }

    /**
     * Ürünü API formatına çevir
     */
    private function prepareApiData(Product $product): array
    {
        // Temel bilgiler
        $data = [
            'barcode' => $product->barcode ?: 'Sistemde Veri yok',
            'title' => $product->title ?: 'Sistemde Veri yok',
            'productMainId' => $product->sku ?: $product->source_reference ?: 'Sistemde Veri yok',
            'brandId' => $product->brand_id ?: 'Sistemde Veri yok',
            'categoryId' => $product->category_id ?: 'Sistemde Veri yok',
            'quantity' => $this->getTotalStock($product),
            'stockCode' => 'Sistemde Veri yok',
            'dimensionalWeight' => 'Sistemde Veri yok',
            'description' => $product->description ?: 'Sistemde Veri yok',
            'currencyType' => $product->currency ?: 'TRY',
            'listPrice' => $product->reference_price ?: 'Sistemde Veri yok',
            'salePrice' => $this->getSalePrice($product),
            'vatRate' => 'Sistemde Veri yok',
            'cargoCompanyId' => 'Sistemde Veri yok',
            'lotNumber' => 'Sistemde Veri yok',
            'specialConsumptionTax' => 'Sistemde Veri yok',
            'deliveryOption' => [
                'deliveryDuration' => 'Sistemde Veri yok',
                'fastDeliveryType' => 'Sistemde Veri yok',
            ],
            'images' => $this->prepareImages($product),
            'attributes' => $this->prepareAttributes($product),
        ];

        return $data;
    }

    /**
     * Toplam stok miktarını hesapla
     */
    private function getTotalStock(Product $product): string|int
    {
        if ($product->variants && $product->variants->count() > 0) {
            $totalStock = $product->variants->sum('stock');
            return $totalStock > 0 ? $totalStock : 'Sistemde Veri yok';
        }
        return 'Sistemde Veri yok';
    }

    /**
     * Satış fiyatını al
     */
    private function getSalePrice(Product $product): string|float
    {
        if ($product->variants && $product->variants->count() > 0) {
            $firstVariant = $product->variants->first();
            return $firstVariant->price ?: 'Sistemde Veri yok';
        }
        return $product->reference_price ?: 'Sistemde Veri yok';
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
     * Attribute'ları hazırla - Required attribute'lar dahil
     */
    private function prepareAttributes(Product $product): array
    {
        $attributes = [];

        // Kategoriye göre required attribute'ları al
        if ($product->category_id) {
            $requiredAttributes = CategoryAttribute::where('category_id', $product->category_id)
                ->where('is_required', true)
                ->with('attribute')
                ->get();

            // Product'ın raw_xml'inden attribute değerlerini çıkar
            $productAttributes = $this->extractProductAttributes($product);

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
                            'attributeId' => $attribute->id,
                            'attributeValueId' => $attributeValue->id,
                        ];
                    } else {
                        // Custom değer olarak ekle
                        $attributes[] = [
                            'attributeId' => $attribute->id,
                            'customAttributeValue' => $originCountryName,
                        ];
                    }
                    continue;
                }

                // XML'den bu attribute'a karşılık gelen değeri bul
                $attributeValue = $this->findAttributeValue($product, $attribute->id, $productAttributes);

                if ($attributeValue) {
                    // AttributeValue ID varsa onu kullan
                    if (isset($attributeValue['attribute_value_id'])) {
                        $attributes[] = [
                            'attributeId' => $attribute->id,
                            'attributeValueId' => $attributeValue['attribute_value_id'],
                        ];
                    } else {
                        // Custom değer kullan
                        $attributes[] = [
                            'attributeId' => $attribute->id,
                            'customAttributeValue' => $attributeValue['value'] ?? 'Sistemde Veri yok',
                        ];
                    }
                } else {
                    // Değer bulunamadı - Sistemde Veri yok olarak ekle
                    $attributes[] = [
                        'attributeId' => $attribute->id,
                        'customAttributeValue' => 'Sistemde Veri yok',
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
}

