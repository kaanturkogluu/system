<?php

namespace App\Jobs;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportItem;
use App\Models\Product;
use App\Models\ProductVariant;
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

                // Payload'dan verileri çıkar
                $productTitle = $this->getNestedValue($payload, ['product', 'title']);
                $productDescription = $this->getNestedValue($payload, ['product', 'description']);
                $brandName = $this->getNestedValue($payload, ['product', 'brand']);
                $categoryRawPath = $this->getNestedValue($payload, ['category', 'raw_path']);
                $price = $this->getNestedValue($payload, ['pricing', 'price']);
                $stock = $this->getNestedValue($payload, ['stock']);
                $attributes = $this->getNestedValue($payload, ['attributes']);

                // SKU kontrolü
                $sku = $importItem->sku;
                if (empty($sku)) {
                    throw new \Exception('SKU bulunamadı');
                }

                // 3) Marka işlemi
                $brandId = null;
                if (!empty($brandName)) {
                    $brandSlug = Str::slug($brandName, '-', 'tr');
                    $brand = Brand::where('slug', $brandSlug)->first();

                    if (!$brand) {
                        // Marka yoksa oluştur
                        $brand = Brand::create([
                            'name' => $brandName,
                            'slug' => $brandSlug,
                        ]);
                    }

                    $brandId = $brand->id;
                } else {
                    // Marka boşsa NEEDS_MAPPING
                    $importItem->update([
                        'status' => 'NEEDS_MAPPING',
                        'error_message' => 'Marka bilgisi bulunamadı',
                    ]);

                    Log::channel('imports')->warning('Import item needs mapping - missing brand', [
                        'import_item_id' => $this->importItemId,
                        'product_sku' => $sku,
                        'status' => 'NEEDS_MAPPING',
                    ]);

                    return;
                }

                // 4) Kategori işlemi
                if (empty($categoryRawPath)) {
                    $importItem->update([
                        'status' => 'NEEDS_MAPPING',
                        'error_message' => 'Kategori raw_path bulunamadı',
                    ]);

                    Log::channel('imports')->warning('Import item needs mapping - missing category path', [
                        'import_item_id' => $this->importItemId,
                        'product_sku' => $sku,
                        'status' => 'NEEDS_MAPPING',
                    ]);

                    return;
                }

                // raw_path ile kategori eşleştirmesi
                $category = Category::where('path', $categoryRawPath)->first();

                if (!$category) {
                    $importItem->update([
                        'status' => 'NEEDS_MAPPING',
                        'error_message' => 'Kategori eşleştirmesi bulunamadı: ' . $categoryRawPath,
                    ]);

                    Log::channel('imports')->warning('Import item needs mapping - category not found', [
                        'import_item_id' => $this->importItemId,
                        'product_sku' => $sku,
                        'status' => 'NEEDS_MAPPING',
                        'category_path' => $categoryRawPath,
                    ]);

                    return;
                }

                // Kategori LEAF kontrolü
                if (!$category->is_leaf) {
                    $importItem->update([
                        'status' => 'NEEDS_MAPPING',
                        'error_message' => 'Kategori leaf değil: ' . $category->name,
                    ]);

                    Log::channel('imports')->warning('Import item needs mapping - category is not leaf', [
                        'import_item_id' => $this->importItemId,
                        'product_sku' => $sku,
                        'status' => 'NEEDS_MAPPING',
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                    ]);

                    return;
                }

                // 5) Product upsert (sku bazlı)
                $product = Product::updateOrCreate(
                    [
                        'source_type' => 'xml',
                        'sku' => $sku,
                    ],
                    [
                        'source_reference' => $importItem->external_id,
                        'barcode' => $importItem->barcode,
                        'title' => $productTitle ?? 'Başlıksız Ürün',
                        'description' => $productDescription,
                        'brand_id' => $brandId,
                        'category_id' => $category->id,
                        'product_type' => 'simple',
                        'status' => 'IMPORTED',
                    ]
                );

                // 6) Product variant oluştur (tek varyant)
                $variantSku = $sku;
                $variantPrice = $price ? (float) $price : 0;
                $variantStock = $stock ? (int) $stock : 0;

                ProductVariant::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku' => $variantSku,
                    ],
                    [
                        'barcode' => $importItem->barcode,
                        'price' => $variantPrice,
                        'currency' => 'TRY',
                        'stock' => $variantStock,
                        'attributes' => $attributes ?: null,
                    ]
                );

                // 7) import_item status güncelle - Başarılı
                $importItem->update([
                    'status' => 'UPSERTED',
                ]);

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
}

