<?php

namespace App\Services;

use App\Helpers\MarketplaceConfig;
use App\Models\Category;
use App\Models\MarketplaceCategory;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductPriceCalculationService
{
    /**
     * Ürün fiyatını hesapla
     * 
     * Formül: Fiyat + KDV + Komisyon + 100 TL Kargo + (Pazaryeri kategori komisyonu)
     * 
     * @param Product $product Ürün
     * @param float $basePrice Fiyat_Ozel'den gelen TRY'ye çevrilmiş fiyat
     * @param string|null $marketplaceSlug Pazaryeri slug (opsiyonel, pazaryeri komisyonu için)
     * @return float Hesaplanan final fiyat
     */
    public function calculatePrice(Product $product, float $basePrice, ?string $marketplaceSlug = null): float
    {
        // 1. KDV oranını al
        $vatRate = $this->getVatRate($product, $marketplaceSlug);
        
        // 2. Kategori komisyon oranını al (default %20)
        $categoryCommissionRate = $this->getCategoryCommissionRate($product);
        
        // 3. Pazaryeri kategori komisyonunu al (eğer pazaryeri belirtilmişse)
        $marketplaceCategoryCommissionRate = 0;
        if ($marketplaceSlug && $product->category_id) {
            $marketplaceCategoryCommissionRate = $this->getMarketplaceCategoryCommissionRate(
                $product->category_id,
                $marketplaceSlug
            );
        }
        
        // 4. Kargo ücreti (sabit 100 TL)
        $cargoFee = 100.0;
        
        // 5. Fiyat hesaplama
        // Base price'a kategori komisyon oranını ekle
        $priceWithCategoryCommission = $basePrice * (1 + ($categoryCommissionRate / 100));
        $categoryCommissionAmount = $priceWithCategoryCommission - $basePrice;
        
        // KDV ekle
        $vatAmount = $priceWithCategoryCommission * ($vatRate / 100);
        $priceWithVat = $priceWithCategoryCommission + $vatAmount;
        
        // Pazaryeri kategori komisyonu ekle
        $marketplaceCategoryCommissionAmount = $priceWithVat * ($marketplaceCategoryCommissionRate / 100);
        $priceWithMarketplaceCommission = $priceWithVat + $marketplaceCategoryCommissionAmount;
        
        // Kargo ekle
        $finalPrice = $priceWithMarketplaceCommission + $cargoFee;
        
        Log::channel('imports')->debug('Fiyat hesaplama detayları', [
            'product_id' => $product->id,
            'base_price' => $basePrice,
            'category_commission_rate' => $categoryCommissionRate,
            'category_commission_amount' => $categoryCommissionAmount,
            'price_with_category_commission' => $priceWithCategoryCommission,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'price_with_vat' => $priceWithVat,
            'marketplace_category_commission_rate' => $marketplaceCategoryCommissionRate,
            'marketplace_category_commission_amount' => $marketplaceCategoryCommissionAmount,
            'cargo_fee' => $cargoFee,
            'final_price' => $finalPrice,
        ]);
        
        return round($finalPrice, 2);
    }

    /**
     * Ürün fiyatını detaylı bilgilerle hesapla
     * 
     * @param Product $product Ürün
     * @param float $basePrice Fiyat_Ozel'den gelen TRY'ye çevrilmiş fiyat
     * @param string|null $marketplaceSlug Pazaryeri slug (opsiyonel, pazaryeri komisyonu için)
     * @return array Hesaplanan final fiyat ve tüm detaylar
     */
    public function calculatePriceWithDetails(Product $product, float $basePrice, ?string $marketplaceSlug = null): array
    {
        // 1. KDV oranını al
        $vatRate = $this->getVatRate($product, $marketplaceSlug);
        
        // 2. Kategori komisyon oranını al (default %20)
        $categoryCommissionRate = $this->getCategoryCommissionRate($product);
        
        // 3. Pazaryeri kategori komisyonunu al (eğer pazaryeri belirtilmişse)
        $marketplaceCategoryCommissionRate = 0;
        if ($marketplaceSlug && $product->category_id) {
            $marketplaceCategoryCommissionRate = $this->getMarketplaceCategoryCommissionRate(
                $product->category_id,
                $marketplaceSlug
            );
        }
        
        // 4. Kargo ücreti (sabit 100 TL)
        $cargoFee = 100.0;
        
        // 5. Fiyat hesaplama
        // Base price'a kategori komisyon oranını ekle
        $priceWithCategoryCommission = $basePrice * (1 + ($categoryCommissionRate / 100));
        $categoryCommissionAmount = $priceWithCategoryCommission - $basePrice;
        
        // KDV ekle
        $vatAmount = $priceWithCategoryCommission * ($vatRate / 100);
        $priceWithVat = $priceWithCategoryCommission + $vatAmount;
        
        // Pazaryeri kategori komisyonu ekle
        $marketplaceCategoryCommissionAmount = $priceWithVat * ($marketplaceCategoryCommissionRate / 100);
        $priceWithMarketplaceCommission = $priceWithVat + $marketplaceCategoryCommissionAmount;
        
        // Kargo ekle
        $finalPrice = $priceWithMarketplaceCommission + $cargoFee;
        
        // Kategori komisyon oranı kaynağını belirle
        $categoryCommissionRateSource = 'Genel';
        if ($product->category && $product->category->commission_rate !== null) {
            $categoryCommissionRateSource = 'Kategori Bazlı';
        }
        
        return [
            'base_price' => round($basePrice, 2),
            'category_commission_rate' => $categoryCommissionRate,
            'category_commission_rate_source' => $categoryCommissionRateSource,
            'category_commission_amount' => round($categoryCommissionAmount, 2),
            'price_with_category_commission' => round($priceWithCategoryCommission, 2),
            'vat_rate' => $vatRate,
            'vat_amount' => round($vatAmount, 2),
            'price_with_vat' => round($priceWithVat, 2),
            'marketplace_category_commission_rate' => $marketplaceCategoryCommissionRate,
            'marketplace_category_commission_amount' => round($marketplaceCategoryCommissionAmount, 2),
            'price_with_marketplace_commission' => round($priceWithMarketplaceCommission, 2),
            'cargo_fee' => $cargoFee,
            'final_price' => round($finalPrice, 2),
        ];
    }

    /**
     * KDV oranını hesapla
     * Öncelik sırası: 1) Ürüne özel, 2) Kategori bazlı, 3) Genel (default 20)
     */
    private function getVatRate(Product $product, ?string $marketplaceSlug = null): int
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
        $defaultVatRate = $marketplaceSlug 
            ? MarketplaceConfig::get($marketplaceSlug, 'default_vat_rate', '20')
            : '20';
        return (int) $defaultVatRate;
    }

    /**
     * Kategori komisyon oranını hesapla (kar oranı yerine kullanılacak)
     * Öncelik sırası: 1) Kategori bazlı, 2) Genel (default 20)
     */
    private function getCategoryCommissionRate(Product $product): float
    {
        // 1. Kategori bazlı komisyon oranı
        if ($product->category && $product->category->commission_rate !== null) {
            return (float) $product->category->commission_rate;
        }

        // 2. Genel komisyon oranı (default 20)
        return 20.0;
    }


    /**
     * Pazaryeri kategori komisyonunu al
     * 
     * @param int $categoryId Kategori ID
     * @param string $marketplaceSlug Pazaryeri slug
     * @return float Komisyon oranı (%)
     */
    private function getMarketplaceCategoryCommissionRate(int $categoryId, string $marketplaceSlug): float
    {
        // Marketplace'i bul
        $marketplace = \App\Models\Marketplace::where('slug', $marketplaceSlug)->first();
        if (!$marketplace) {
            Log::channel('imports')->debug('Marketplace bulunamadı', ['marketplace_slug' => $marketplaceSlug]);
            return 0.0;
        }

        // Kategoriyi bul
        $category = Category::find($categoryId);
        if (!$category) {
            Log::channel('imports')->debug('Kategori bulunamadı', ['category_id' => $categoryId]);
            return 0.0;
        }

        // Marketplace category mapping'i bul
        // Önce komisyon oranı olanları kontrol et (is_mapped kontrolü yapmadan)
        // Eğer komisyon oranı yoksa, is_mapped=true olanları kontrol et
        $marketplaceCategory = MarketplaceCategory::where('marketplace_id', $marketplace->id)
            ->where('global_category_id', $categoryId)
            ->where(function($query) {
                $query->whereNotNull('commission_rate')
                      ->orWhere('is_mapped', true);
            })
            ->orderByRaw('CASE WHEN commission_rate IS NOT NULL THEN 0 ELSE 1 END')
            ->first();

        if ($marketplaceCategory && $marketplaceCategory->commission_rate !== null) {
            Log::channel('imports')->debug('Pazaryeri kategori komisyon oranı bulundu', [
                'category_id' => $categoryId,
                'marketplace_slug' => $marketplaceSlug,
                'marketplace_category_id' => $marketplaceCategory->id,
                'commission_rate' => $marketplaceCategory->commission_rate,
                'is_mapped' => $marketplaceCategory->is_mapped,
                'global_category_id' => $marketplaceCategory->global_category_id,
            ]);
            return (float) $marketplaceCategory->commission_rate;
        }

        // Eğer global_category_id ile eşleşme bulunamazsa, log kaydı
        Log::channel('imports')->debug('Pazaryeri kategori komisyon oranı bulunamadı', [
            'category_id' => $categoryId,
            'category_name' => $category->name,
            'marketplace_slug' => $marketplaceSlug,
            'marketplace_id' => $marketplace->id,
        ]);

        return 0.0;
    }
}

