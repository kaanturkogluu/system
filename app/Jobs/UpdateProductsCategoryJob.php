<?php

namespace App\Jobs;

use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateProductsCategoryJob implements ShouldQueue
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
        public int $externalCategoryId,
        public int $categoryId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Re-validate category exists and is leaf
        $category = Category::find($this->categoryId);
        
        if (!$category || !$category->is_leaf) {
            Log::channel('imports')->warning('Category update job skipped - category invalid or not leaf', [
                'external_category_id' => $this->externalCategoryId,
                'category_id' => $this->categoryId,
                'category_exists' => $category !== null,
                'is_leaf' => $category?->is_leaf ?? false,
            ]);
            return;
        }
        
        DB::beginTransaction();
        
        try {
            // Bulk update using correct JOIN: import_items.id = products.source_reference
            // Also join with category_mappings to ensure status = 'mapped'
            // Use cm.category_id from the mapping table to ensure consistency
            // Verify mapping's category_id matches expected value for safety
            $affectedRows = DB::update("
                UPDATE products p
                INNER JOIN import_items ii ON ii.id = p.source_reference
                INNER JOIN category_mappings cm ON cm.external_category_id = ii.external_category_id
                SET p.category_id = cm.category_id
                WHERE p.source_type = 'xml'
                  AND p.category_id IS NULL
                  AND cm.external_category_id = ?
                  AND cm.category_id = ?
                  AND cm.status = 'mapped'
            ", [
                $this->externalCategoryId,
                $this->categoryId,
            ]);
            
            DB::commit();
            
            // Count total products with this external_category_id (using correct JOIN)
            $totalProductsWithExternalCategory = DB::selectOne("
                SELECT COUNT(*) as count
                FROM products p
                INNER JOIN import_items ii ON ii.id = p.source_reference
                WHERE p.source_type = 'xml'
                  AND ii.external_category_id = ?
            ", [$this->externalCategoryId])->count ?? 0;
            
            // Count products still unmatched (category_id IS NULL) with correct JOIN
            $productsStillUnmatched = DB::selectOne("
                SELECT COUNT(*) as count
                FROM products p
                INNER JOIN import_items ii ON ii.id = p.source_reference
                WHERE p.source_type = 'xml'
                  AND p.category_id IS NULL
                  AND ii.external_category_id = ?
            ", [$this->externalCategoryId])->count ?? 0;
            
            if ($affectedRows > 0) {
                Log::channel('imports')->info('Products automatically updated with category', [
                    'external_category_id' => $this->externalCategoryId,
                    'category_id' => $this->categoryId,
                    'products_updated' => $affectedRows,
                    'total_products_with_external_category' => $totalProductsWithExternalCategory,
                    'products_still_unmatched' => $productsStillUnmatched,
                ]);
            } else {
                Log::channel('imports')->debug('Category update job completed but no products to update', [
                    'external_category_id' => $this->externalCategoryId,
                    'category_id' => $this->categoryId,
                    'total_products_with_external_category' => $totalProductsWithExternalCategory,
                    'products_still_unmatched' => $productsStillUnmatched,
                ]);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::channel('imports')->error('Failed to auto-update product categories', [
                'external_category_id' => $this->externalCategoryId,
                'category_id' => $this->categoryId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
}

