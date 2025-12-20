<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportElektronikCategoriesCommand extends Command
{
    protected $signature = 'app:import-elektronik-categories
                            {--parent-id= : Parent category ID in database (if not provided, will create as root)}';

    protected $description = 'Import Elektronik category (ID: 1071) and its subcategories from trendyol_categories.json to categories table';

    private int $processedCount = 0;
    private int $skippedCount = 0;

    public function handle(): int
    {
        $this->info('🔄 Starting Elektronik categories import...');

        // Load JSON file
        $jsonPath = base_path('trendyol_categories.json');
        
        if (!file_exists($jsonPath)) {
            $this->error('❌ trendyol_categories.json file not found!');
            return Command::FAILURE;
        }

        $json = json_decode(file_get_contents($jsonPath), true);

        if (empty($json) || !isset($json['categories'])) {
            $this->error('❌ Invalid JSON structure!');
            return Command::FAILURE;
        }

        // Find Elektronik category (ID: 1071)
        $elektronikCategory = $this->findCategoryById($json['categories'], 1071);

        if (!$elektronikCategory) {
            $this->error('❌ Elektronik category (ID: 1071) not found in JSON!');
            return Command::FAILURE;
        }

        $this->info("✅ Found Elektronik category: {$elektronikCategory['name']}");

        // Get parent category ID
        $parentId = $this->option('parent-id') ? (int) $this->option('parent-id') : null;
        
        if ($parentId) {
            $parentCategory = Category::find($parentId);
            if (!$parentCategory) {
                $this->error("❌ Parent category with ID {$parentId} not found!");
                return Command::FAILURE;
            }
            $this->info("📁 Using parent category: {$parentCategory->name} (ID: {$parentId})");
        } else {
            $this->info("📁 Creating as root category (no parent)");
        }

        // Import categories
        DB::transaction(function () use ($elektronikCategory, $parentId) {
            $this->importCategory(
                $elektronikCategory,
                $parentId,
                0, // level
                null // path
            );
        });

        $this->newLine();
        $this->info("📊 Summary:");
        $this->line("   ✅ Processed: {$this->processedCount}");
        $this->line("   ⏭️  Skipped: {$this->skippedCount}");

        return Command::SUCCESS;
    }

    /**
     * Find category by ID in nested structure
     */
    private function findCategoryById(array $categories, int $targetId): ?array
    {
        foreach ($categories as $category) {
            if (isset($category['id']) && $category['id'] === $targetId) {
                return $category;
            }

            if (!empty($category['subCategories'])) {
                $found = $this->findCategoryById($category['subCategories'], $targetId);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * Import category and its subcategories recursively
     */
    private function importCategory(
        array $categoryData,
        ?int $parentId,
        int $level,
        ?string $parentPath
    ): void {
        $name = $categoryData['name'] ?? '';
        $slug = $this->makeSlug($name);

        if (empty($name)) {
            $this->warn("⚠️  Skipping category with empty name (ID: {$categoryData['id']})");
            $this->skippedCount++;
            return;
        }

        // Check if category already exists
        $existingCategory = Category::where('slug', $slug)
            ->where('parent_id', $parentId)
            ->first();

        if ($existingCategory) {
            $this->line("   ⏭️  Skipped: {$name} (already exists)");
            $this->skippedCount++;
            $category = $existingCategory;
        } else {
            // Create category
            $category = Category::create([
                'parent_id' => $parentId,
                'level' => $level,
                'name' => $name,
                'slug' => $slug,
                'path' => null, // Will be updated below
                'is_leaf' => empty($categoryData['subCategories']),
                'is_active' => true,
            ]);

            $this->line("   ✅ Created: {$name} (Level: {$level})");
            $this->processedCount++;
        }

        // Update path
        $path = $this->buildPath($parentPath, $category->id);
        $category->update(['path' => $path]);

        // Process subcategories recursively
        if (!empty($categoryData['subCategories'])) {
            foreach ($categoryData['subCategories'] as $subCategory) {
                $this->importCategory(
                    $subCategory,
                    $category->id,
                    $level + 1,
                    $path
                );
            }
        }
    }

    /**
     * Create slug from name
     */
    private function makeSlug(string $name): string
    {
        return Str::slug($name, '-', 'tr');
    }

    /**
     * Build category path
     */
    private function buildPath(?string $parentPath, int $id): string
    {
        return $parentPath ? $parentPath . '/' . $id : (string) $id;
    }
}

