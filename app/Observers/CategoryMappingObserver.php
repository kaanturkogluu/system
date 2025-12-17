<?php

namespace App\Observers;

use App\Jobs\UpdateProductsCategoryJob;
use App\Models\Category;
use App\Models\CategoryMapping;
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

        // Dispatch async job for bulk update
        UpdateProductsCategoryJob::dispatch($mapping->external_category_id, $category->id);
    }
}

