<?php

namespace App\Jobs;

use App\Helpers\BrandNormalizer;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryMapping;
use App\Models\Currency;
use App\Models\ImportItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductAttributePersistenceService;
use App\Services\ProductPriceCalculationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importItemId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1) import_item'ı al
        $importItem = ImportItem::find($this->importItemId);

        if (!$importItem) {
            Log::channel('imports')->warning('Import item not found', [
                'import_item_id' => $this->importItemId,
            ]);
            return;
        }

        // Status kontrolü - PENDING değilse işlemi durdur
        if ($importItem->status !== 'PENDING') {
            Log::channel('imports')->info('Import item already processed', [
                'import_item_id' => $this->importItemId,
                'current_status' => $importItem->status,
            ]);
            return;
        }

        try {
            DB::transaction(function () use ($importItem) {
                // 2) payload oku
                $payload = $importItem->payload;

                if (empty($payload)) {
                    throw new \Exception('Payload boş');
                }

                // ============================================
                // SKU ÜRETİMİ - MERKEZİ BLOK (EN ÖNCE)
                // ============================================
                // SKU üretimi kategori/marka kontrollerinden ÖNCE çalışmalı
                // SKU üretilebildiği sürece ürün oluşturma devam eder
                $sku = $this->generateSku($payload, $importItem);
                
                if ($sku === null || strlen($sku) === 0) {
                    throw new \Exception('SKU üretilemedi - tüm fallback yöntemleri başarısız');
                }

                // Payload'dan verileri çıkar - hem nested hem düz yapıyı destekle
                $productTitle = $this->getNestedValue($payload, ['product', 'title'])
                    ?? $payload['Ad'] ?? $payload['Title'] ?? $payload['ProductTitle'] ?? null;
                
                // Detay verisi öncelikli olarak description'a yazılacak
                $productDescription = $payload['Detay'] 
                    ?? $this->getNestedValue($payload, ['product', 'description'])
                    ?? $payload['Aciklama'] ?? $payload['Description'] ?? null;
                
                // Desi bilgisini al (XML'den)
                $desi = $this->getNestedValue($payload, ['product', 'desi'])
                    ?? $payload['Desi'] ?? $payload['desi'] ?? $payload['DimensionalWeight'] ?? null;
                // Desi'yi decimal'e çevir (varsa)
                $desiValue = null;
                if ($desi !== null && $desi !== '') {
                    $desiValue = is_numeric($desi) ? (float) $desi : null;
                }
                
                $brandName = $this->getNestedValue($payload, ['product', 'brand'])
                    ?? $payload['Marka'] ?? $payload['Brand'] ?? null;
                
                // Barcode normalize et (boş string → NULL)
                $barcodeRaw = $this->getNestedValue($payload, ['product', 'barcode'])
                    ?: ($payload['Barkod'] ?? null)
                    ?: ($payload['Barcode'] ?? null)
                    ?: null;
                $barcode = $this->normalizeBarcode($barcodeRaw);
                
                // external_category_id ve raw_path hem root seviyesinde hem de category altında olabilir
                $externalCategoryId = $this->getNestedValue($payload, ['category', 'external_category_id'])
                    ?? $this->getNestedValue($payload, ['external_category_id'])
                    ?? $payload['external_category_id'] ?? null;
                
                $categoryRawPath = $this->getNestedValue($payload, ['category', 'raw_path'])
                    ?? $this->getNestedValue($payload, ['raw_path'])
                    ?? $payload['raw_path'] ?? null;
                
                // Fiyat bilgilerini al (XML'den)
                $priceSk = $this->getNestedValue($payload, ['pricing', 'price_sk'])
                    ?? $payload['Fiyat_SK'] ?? null;
                $priceBayi = $this->getNestedValue($payload, ['pricing', 'price_bayi'])
                    ?? $payload['Fiyat_Bayi'] ?? null;
                $priceOzel = $this->getNestedValue($payload, ['pricing', 'price_ozel'])
                    ?? $payload['Fiyat_Ozel'] ?? null;
                
                // Döviz tipini al (XML'den - TL veya USD)
                $currencyCode = $this->getNestedValue($payload, ['pricing', 'currency'])
                    ?? $payload['Doviz'] ?? $payload['Currency'] ?? $payload['ParaBirimi'] 
                    ?? $payload['Döviz'] ?? 'TRY'; // Varsayılan TRY
                
                // Currency kodunu normalize et (TL -> TRY, USD -> USD)
                $currencyCode = strtoupper(trim($currencyCode));
                if ($currencyCode === 'TL') {
                    $currencyCode = 'TRY';
                }
                
                // Currency'yi bul veya varsayılan olarak TRY kullan
                $currency = Currency::where('code', $currencyCode)->first();
                if (!$currency) {
                    // Currency bulunamazsa TRY'yi kullan
                    $currency = Currency::where('code', 'TRY')->first();
                    if (!$currency) {
                        Log::channel('imports')->warning('Currency not found, using TRY as default', [
                            'import_item_id' => $this->importItemId,
                            'requested_currency' => $currencyCode,
                        ]);
                        $currencyCode = 'TRY';
                    }
                }
                
                $currencyId = $currency ? $currency->id : null;
                
                // Fiyat_Ozel'i kullanarak TRY'ye çevrilmiş satış fiyatını hesapla
                // price sütunu SADECE fiyat_ozel verisi kullanılarak doldurulacak
                $priceInTry = 0;
                if ($priceOzel !== null && $priceOzel !== '' && is_numeric($priceOzel)) {
                    $priceOzelValue = (float) $priceOzel;
                    
                    if ($currencyCode === 'TRY') {
                        // Zaten TRY, direkt kullan
                        $priceInTry = $priceOzelValue;
                    } else {
                        // Döviz cinsinden, TRY'ye çevir
                        if ($currency && $currency->rate_to_try > 0) {
                            $priceInTry = $priceOzelValue * $currency->rate_to_try;
                        } else {
                            Log::channel('imports')->warning('Currency rate not available, cannot convert price', [
                                'import_item_id' => $this->importItemId,
                                'currency_code' => $currencyCode,
                                'price_ozel' => $priceOzelValue,
                            ]);
                            $priceInTry = 0; // Kur yoksa 0 olarak bırak
                        }
                    }
                } else {
                    // Fiyat_Ozel yoksa, price 0 olarak kalacak
                    Log::channel('imports')->warning('Fiyat_Ozel bulunamadı, price sütunu 0 olarak ayarlanacak', [
                        'import_item_id' => $this->importItemId,
                        'product_sku' => $sku,
                    ]);
                }
                
                $stock = $this->getNestedValue($payload, ['stock'])
                    ?? $payload['Miktar'] ?? $payload['Stock'] ?? $payload['Stok'] ?? null;
                
                $attributes = $this->getNestedValue($payload, ['attributes'])
                    ?? $payload['TeknikOzellikler'] ?? null;

                // 3) Marka işlemi
                // SKU üretilebildiği için marka eksik olsa bile devam ediyoruz
                $brandId = null;
                if (!empty($brandName)) {
                    $normalizedName = BrandNormalizer::normalize($brandName);
                    $brandSlug = BrandNormalizer::slug($brandName);
                    
                    $brand = Brand::firstOrCreate(
                        ['normalized_name' => $normalizedName],
                        [
                            'name' => $brandName,
                            'slug' => $brandSlug,
                            'status' => 'active',
                        ]
                    );

                    $brandId = $brand->id;
                } else {
                    // Marka boşsa log kaydet ama devam et
                    Log::channel('imports')->warning('Import item missing brand - continuing anyway', [
                        'import_item_id' => $this->importItemId,
                        'product_sku' => $sku,
                    ]);
                }

                // 4) Kategori işlemi - category_mappings üzerinden
                // SKU üretilebildiği için kategori eksik olsa bile devam ediyoruz
                $categoryId = null;

                if (!empty($externalCategoryId)) {
                    $mapping = CategoryMapping::where('external_category_id', $externalCategoryId)
                        ->where('status', 'mapped')
                        ->first();

                    if ($mapping) {
                        $category = Category::find($mapping->category_id);

                        if ($category && $category->is_leaf) {
                            $categoryId = $category->id;
                        } else {
                            if (!$category) {
                                Log::channel('imports')->warning('Import item mapped category not found - continuing anyway', [
                                    'import_item_id' => $this->importItemId,
                                    'product_sku' => $sku,
                                    'external_category_id' => $externalCategoryId,
                                    'category_id' => $mapping->category_id,
                                ]);
                            } else {
                                Log::channel('imports')->warning('Import item category is not leaf - continuing anyway', [
                                    'import_item_id' => $this->importItemId,
                                    'product_sku' => $sku,
                                    'category_id' => $category->id,
                                    'category_name' => $category->name,
                                ]);
                            }
                        }
                    } else {
                        Log::channel('imports')->warning('Import item category mapping not found - continuing anyway', [
                            'import_item_id' => $this->importItemId,
                            'product_sku' => $sku,
                            'external_category_id' => $externalCategoryId,
                            'category_raw_path' => $categoryRawPath,
                        ]);
                    }
                } else {
                    Log::channel('imports')->warning('Import item missing category info - continuing anyway', [
                        'import_item_id' => $this->importItemId,
                        'product_sku' => $sku,
                    ]);
                }

                // 5) Product upsert (sku bazlı)
                $sourceReference = $this->getNestedValue($payload, ['product', 'external_id'])
                    ?? $payload['Kod'] ?? $payload['ProductId'] ?? $payload['Id'] ?? null;
                
                $product = Product::updateOrCreate(
                    [
                        'source_type' => 'xml',
                        'sku' => $sku,
                    ],
                    [
                        'source_reference' => $sourceReference,
                        'barcode' => $barcode,
                        'title' => $productTitle ?? 'Başlıksız Ürün',
                        'description' => $productDescription,
                        'desi' => $desiValue,
                        'brand_id' => $brandId,
                        'category_id' => $categoryId,
                        'product_type' => 'simple',
                        'status' => 'IMPORTED',
                    ]
                );
                

                // 6) Product variant oluştur (tek varyant)
                $variantSku = $sku;
                $variantStock = $stock ? (int) $stock : 0;
                
                // Fiyat değerlerini normalize et
                $priceSkValue = ($priceSk !== null && $priceSk !== '' && is_numeric($priceSk)) ? (float) $priceSk : null;
                $priceBayiValue = ($priceBayi !== null && $priceBayi !== '' && is_numeric($priceBayi)) ? (float) $priceBayi : null;
                $priceOzelValue = ($priceOzel !== null && $priceOzel !== '' && is_numeric($priceOzel)) ? (float) $priceOzel : null;

                // Final fiyatı hesapla (KDV + Komisyon + Kargo + Pazaryeri komisyonu)
                // Product'ı yeniden yükle (category ilişkisi için)
                $product->load('category');
                
                $finalPrice = $priceInTry;
                if ($priceInTry > 0) {
                    $priceCalculationService = new ProductPriceCalculationService();
                    // Pazaryeri slug'ı şu an için null (ileride pazaryeri belirtilirse kullanılabilir)
                    $finalPrice = $priceCalculationService->calculatePrice($product, $priceInTry, null);
                }

                ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku' => $variantSku,
                    ],
                    [
                        'barcode' => $barcode,
                        'price' => round($finalPrice, 2), // Hesaplanan final fiyat (Fiyat_Ozel + KDV + Komisyon + Kargo + Pazaryeri komisyonu)
                        'currency' => $currencyCode, // Orijinal döviz kodu
                        'price_sk' => $priceSkValue, // Fiyat Satış Kuru (orijinal döviz)
                        'price_bayi' => $priceBayiValue, // Fiyat Bayi (orijinal döviz)
                        'price_ozel' => $priceOzelValue, // Fiyat Özel (orijinal döviz)
                        'currency_id' => $currencyId, // Currency ID
                        'stock' => $variantStock,
                        'attributes' => $attributes ?: null,
                    ]
                );

                // 7) Product images (resim URL'leri kaydet)
                $this->saveProductImages($product->id, $payload);

                // 8) Process product attributes (maplanen özelliklere göre)
                $attributeService = new ProductAttributePersistenceService();
                $attributeStats = $attributeService->processProduct($product, $payload);
                
                // Eğer mapping eksikse status'ü NEEDS_MAPPING yap
                if (!empty($attributeStats['missing_mappings'])) {
                    $importItem->update([
                        'status' => 'NEEDS_MAPPING',
                        'error_message' => 'XML attribute mapping eksik: ' . implode(', ', $attributeStats['missing_mappings']),
                    ]);
                } else {
                    // 9) Required attribute kontrolü yap ve product status'ünü güncelle
                    // Product'ı yeniden yükle (product_attributes ilişkisi için)
                    $product->load('productAttributes.attribute', 'productAttributes.attributeValue', 'brand.originCountry');
                    
                    // Tüm required attribute'lar mevcut mu kontrol et
                    if ($attributeService->hasAllRequiredAttributes($product)) {
                        // Tüm required attribute'lar mevcut - READY status'üne geç
                        $product->update([
                            'status' => 'READY',
                        ]);
                    } else {
                        // Required attribute'lar eksik - IMPORTED olarak kal
                        $product->update([
                            'status' => 'IMPORTED',
                        ]);
                    }
                    
                    // import_item status güncelle - Başarılı
                    $importItem->update([
                        'status' => 'UPSERTED',
                    ]);
                }

                Log::channel('imports')->info('Import item processed successfully', [
                    'import_item_id' => $this->importItemId,
                    'product_sku' => $sku,
                    'product_id' => $product->id,
                    'status' => 'UPSERTED',
                ]);
            });

        } catch (\Exception $e) {
            // Hata durumunda status güncelle (transaction dışında)
            $importItem->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);

            Log::channel('imports')->error('Import item processing failed', [
                'import_item_id' => $this->importItemId,
                'product_sku' => $importItem->sku ?? 'N/A',
                'status' => 'FAILED',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get nested value from array using dot notation or array path
     */
    private function getNestedValue(array $array, array $path): mixed
    {
        $current = $array;

        foreach ($path as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * SKU üretimi - fallback sırası ile
     * 
     * Fallback sırası:
     * 1. payload.product.sku
     * 2. payload.product.stock_code
     * 3. payload.product.barcode
     * 4. payload.product.external_id
     * 5. GN-{feed_run_id}-{import_item_id}
     * 
     * @param array $payload
     * @param ImportItem $importItem
     * @return string|null
     */
    private function generateSku(array $payload, ImportItem $importItem): ?string
    {
        // SKU adayları - hem nested hem de düz payload yapısını destekle
        $skuCandidates = [
            // İç içe yapı (product.*)
            $this->getNestedValue($payload, ['product', 'sku']),
            $this->getNestedValue($payload, ['product', 'stock_code']),
            $this->getNestedValue($payload, ['product', 'barcode']),
            $this->getNestedValue($payload, ['product', 'external_id']),
            
            // Düz yapı (XML'den gelen - Türkçe field isimleri)
            $payload['Kod'] ?? null,
            $payload['StokKodu'] ?? null,
            $payload['UrunKodu'] ?? null,
            $payload['Barkod'] ?? null,
            $payload['Barcode'] ?? null,
            
            // İngilizce field isimleri
            $payload['Sku'] ?? null,
            $payload['SKU'] ?? null,
            $payload['ProductCode'] ?? null,
            $payload['ProductId'] ?? null,
            $payload['Id'] ?? null,
            $payload['ExternalId'] ?? null,
        ];

        // Her adayı kontrol et
        foreach ($skuCandidates as $candidate) {
            if ($candidate !== null) {
                $sku = $this->normalizeSku($candidate);
                // normalizeSku null dönerse boş string demektir, geçerli SKU varsa döndür
                if ($sku !== null) {
                    return $sku;
                }
            }
        }

        // Hiçbiri çalışmadıysa, fallback: GN-{feed_run_id}-{import_item_id}
        // Bu fallback her zaman geçerli bir SKU üretir
        $fallbackSku = sprintf('GN-%d-%d', $importItem->feed_run_id ?? 0, $importItem->id ?? 0);
        $normalizedSku = $this->normalizeSku($fallbackSku);
        
        // Fallback kullanıldığını logla
        if ($normalizedSku !== null) {
            Log::channel('imports')->info('Using fallback SKU', [
                'import_item_id' => $importItem->id,
                'sku' => $normalizedSku,
            ]);
        }
        
        return $normalizedSku;
    }

    /**
     * SKU normalizasyonu
     * - trim edilir
     * - boş string kontrolü (empty() değil, strlen() ile)
     * - max 64 karakter
     * 
     * @param mixed $sku
     * @return string|null
     */
    private function normalizeSku($sku): ?string
    {
        // String'e çevir
        $sku = (string) $sku;
        
        // Trim et
        $sku = trim($sku);
        
        // Boş string kontrolü - strlen() kullan (empty() "0" için true döner)
        if (strlen($sku) === 0) {
            return null;
        }
        
        // Max 64 karakter
        if (strlen($sku) > 64) {
            $sku = substr($sku, 0, 64);
        }
        
        return $sku;
    }

    /**
     * Barcode normalizasyonu
     * - null veya boş string → NULL
     * - string ise trim edilir
     * - trim sonrası boş string → NULL
     * 
     * UNIQUE constraint (uq_barcode) için kritik:
     * Boş string ('') asla DB'ye yazılmamalı, mutlaka NULL olmalı
     * 
     * @param mixed $barcode
     * @return string|null
     */
    private function normalizeBarcode($barcode): ?string
    {
        if ($barcode === null || $barcode === '') {
            return null;
        }
        
        $barcode = (string) $barcode;
        $barcode = trim($barcode);
        
        if ($barcode === '' || strlen($barcode) === 0) {
            return null;
        }
        
        return $barcode;
    }

    /**
     * Ürün resim URL'lerini kaydet (indirme YOK, sadece link)
     * 
     * @param int $productId
     * @param array $payload
     * @return void
     */
    private function saveProductImages(int $productId, array $payload): void
    {
        $imageUrls = [];
        
        // AnaResim (ana resim, sort_order = 0)
        $anaResim = $payload['AnaResim'] ?? null;
        if ($this->isValidImageUrl($anaResim)) {
            $imageUrls[] = [
                'url' => trim($anaResim),
                'sort_order' => 0,
            ];
        }
        
        // UrunResimleri (ek resimler)
        $sortOrder = 1;
        if (isset($payload['urunResimleri']['UrunResimler'])) {
            $urunResimler = $payload['urunResimleri']['UrunResimler'];
            
            // UrunResimler array mi (birden fazla resim) yoksa tek obje mi?
            if (isset($urunResimler[0])) {
                // Array of objects: [{"UrunKodu": "...", "Resim": "..."}, ...]
                foreach ($urunResimler as $resimObj) {
                    $resimUrl = $resimObj['Resim'] ?? null;
                    
                    if ($this->isValidImageUrl($resimUrl)) {
                        $resimUrl = trim($resimUrl);
                        
                        // Duplicate kontrolü (AnaResim ile aynı olabilir)
                        $alreadyExists = false;
                        foreach ($imageUrls as $existing) {
                            if ($existing['url'] === $resimUrl) {
                                $alreadyExists = true;
                                break;
                            }
                        }
                        
                        if (!$alreadyExists) {
                            $imageUrls[] = [
                                'url' => $resimUrl,
                                'sort_order' => $sortOrder++,
                            ];
                        }
                    }
                }
            } else {
                // Single object: {"UrunKodu": "...", "Resim": "..."}
                $resimUrl = $urunResimler['Resim'] ?? null;
                
                if ($this->isValidImageUrl($resimUrl)) {
                    $resimUrl = trim($resimUrl);
                    
                    // Duplicate kontrolü
                    $alreadyExists = false;
                    foreach ($imageUrls as $existing) {
                        if ($existing['url'] === $resimUrl) {
                            $alreadyExists = true;
                            break;
                        }
                    }
                    
                    if (!$alreadyExists) {
                        $imageUrls[] = [
                            'url' => $resimUrl,
                            'sort_order' => $sortOrder++,
                        ];
                    }
                }
            }
        }
        
        // Resim yoksa skip
        if (empty($imageUrls)) {
            return;
        }
        
        // Her resim URL'i için upsert
        foreach ($imageUrls as $imageData) {
            ProductImage::updateOrCreate(
                [
                    'product_id' => $productId,
                    'url' => $imageData['url'],
                ],
                [
                    'sort_order' => $imageData['sort_order'],
                ]
            );
        }
    }

    /**
     * URL validasyonu (basit kontrol)
     * 
     * @param mixed $url
     * @return bool
     */
    private function isValidImageUrl($url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }
        
        $url = trim((string) $url);
        
        if (strlen($url) === 0) {
            return false;
        }
        
        // http veya https ile başlıyor mu
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return false;
        }
        
        return true;
    }
}

