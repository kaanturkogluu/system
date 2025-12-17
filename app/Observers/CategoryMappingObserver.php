<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\CategoryMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategoryMappingObserver
{
    /**
     * Handle the CategoryMapping "saved" event.
     * Triggered after create or update.
     */
    public function saved(CategoryMapping $mapping): void
    {
        // Only process if status is 'mapped'
        if ($mapping->status !== 'mapped') {
            return;
        }

        // Validate category exists and is leaf
        $category = Category::find($mapping->category_id);
        
        if (!$category) {
            Log::channel('imports')->warning('Category mapping saved but category not found', [
                'mapping_id' => $mapping->id,
                'category_id' => $mapping->category_id,
                'external_category_id' => $mapping->external_category_id,
            ]);
            return;
        }
        
        if (!$category->is_leaf) {
            Log::channel('imports')->warning('Category mapping saved but category is not leaf', [
                'mapping_id' => $mapping->id,
                'category_id' => $mapping->category_id,
                'category_name' => $category->name,
                'external_category_id' => $mapping->external_category_id,
            ]);
            return;
        }

        // Bulk update products via import_items payload match
        $this->updateProductCategories($mapping, $category);
    }

    /**
     * Bulk update product categories based on mapping
     */
    private function updateProductCategories(CategoryMapping $mapping, Category $category): void
    {
        DB::beginTransaction();
        
        try {
            // Bulk update using subquery to match products via import_items payload
            $affectedRows = DB::update("
                UPDATE products p
                INNER JOIN import_items ii ON JSON_UNQUOTE(JSON_EXTRACT(ii.payload, '$.Kod')) = p.source_reference
                SET p.category_id = ?
                WHERE p.source_type = 'xml'
                  AND p.category_id IS NULL
                 AND JSON_UNQUOTE(JSON_EXTRACT(ii.payload, '$.external_category_id')) = ?
            ", [
                $category->id,
                $mapping->external_category_id,
            ]);
            
            DB::commit();
            
            if ($affectedRows > 0) {
                Log::channel('imports')->info('Products automatically updated with category', [
                    'mapping_id' => $mapping->id,
                    'external_category_id' => $mapping->external_category_id,
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'products_updated' => $affectedRows,
                ]);
            } else {
                Log::channel('imports')->debug('Category mapping saved but no products to update', [
                    'mapping_id' => $mapping->id,
                    'external_category_id' => $mapping->external_category_id,
                    'category_id' => $category->id,
                ]);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::channel('imports')->error('Failed to auto-update product categories', [
                'mapping_id' => $mapping->id,
                'external_category_id' => $mapping->external_category_id,
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

