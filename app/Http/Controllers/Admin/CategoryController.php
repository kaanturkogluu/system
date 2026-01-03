<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Marketplace;
use App\Models\MarketplaceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $query = Category::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');
        }

        // Filter by level
        if ($request->has('level') && $request->level !== '') {
            $query->where('level', $request->level);
        }

        // Filter by parent
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Order by
        $query->orderBy('level')->orderBy('name');

        $categories = $query->paginate(50);
        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories', 'parentCategories'));
    }

    /**
     * Download Trendyol categories from API and save to JSON file
     */
    public function downloadTrendyolCategories(Request $request)
    {
        try {
            $response = Http::get('https://apigw.trendyol.com/integration/product/product-categories');

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trendyol API\'den veri alınamadı. HTTP Status: ' . $response->status(),
                ], 400);
            }

            $jsonData = $response->json();

            if (empty($jsonData) || !isset($jsonData['categories'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Geçersiz JSON yapısı veya kategoriler bulunamadı.',
                ], 400);
            }

            // Save to file
            $filePath = base_path('trendyol_categories.json');
            file_put_contents($filePath, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            // Extract main categories (parentId is null)
            $mainCategories = array_filter($jsonData['categories'], function ($cat) {
                return ($cat['parentId'] ?? null) === null;
            });

            return response()->json([
                'success' => true,
                'message' => 'Kategoriler başarıyla indirildi ve kaydedildi.',
                'main_categories' => array_values($mainCategories),
                'total_categories' => count($jsonData['categories']),
            ]);
        } catch (\Exception $e) {
            Log::error('Trendyol categories download error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get main categories from downloaded JSON file
     */
    public function getMainCategories()
    {
        $filePath = base_path('trendyol_categories.json');

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'trendyol_categories.json dosyası bulunamadı. Önce kategorileri indirin.',
            ], 404);
        }

        $jsonData = json_decode(file_get_contents($filePath), true);

        if (empty($jsonData) || !isset($jsonData['categories'])) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz JSON yapısı.',
            ], 400);
        }

        // Extract main categories (parentId is null)
        $mainCategories = array_filter($jsonData['categories'], function ($cat) {
            return ($cat['parentId'] ?? null) === null;
        });

        return response()->json([
            'success' => true,
            'main_categories' => array_values($mainCategories),
        ]);
    }

    /**
     * Import selected categories from Trendyol
     */
    public function importCategories(Request $request)
    {
        $validated = $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'integer',
        ]);

        $filePath = base_path('trendyol_categories.json');

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'trendyol_categories.json dosyası bulunamadı.',
            ], 404);
        }

        $jsonData = json_decode(file_get_contents($filePath), true);

        if (empty($jsonData) || !isset($jsonData['categories'])) {
            return response()->json([
                'success' => false,
                'message' => 'Geçersiz JSON yapısı.',
            ], 400);
        }

        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();

        if (!$trendyolMarketplace) {
            return response()->json([
                'success' => false,
                'message' => 'Trendyol pazaryeri bulunamadı.',
            ], 404);
        }

        $imported = 0;
        $updated = 0;
        $errors = [];

        try {
            DB::transaction(function () use ($jsonData, $validated, $trendyolMarketplace, &$imported, &$updated, &$errors) {
                foreach ($validated['category_ids'] as $categoryId) {
                    $categoryData = $this->findCategoryById($jsonData['categories'], $categoryId);

                    if (!$categoryData) {
                        $errors[] = "Kategori ID {$categoryId} bulunamadı.";
                        continue;
                    }

                    // Import category and subcategories recursively
                    $this->importCategoryRecursive(
                        $categoryData,
                        null, // parent_id
                        0, // level
                        null, // parent_path
                        $trendyolMarketplace->id,
                        null, // marketplace_parent_id
                        $imported,
                        $updated
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => "İşlem tamamlandı. {$imported} kategori eklendi, {$updated} kategori güncellendi.",
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            Log::error('Category import error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
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
     * Import category and subcategories recursively
     */
    private function importCategoryRecursive(
        array $categoryData,
        ?int $parentId,
        int $level,
        ?string $parentPath,
        int $marketplaceId,
        ?int $marketplaceParentId,
        int &$imported,
        int &$updated
    ): void {
        $name = $categoryData['name'] ?? '';
        $slug = Str::slug($name, '-', 'tr');
        $trendyolCategoryId = $categoryData['id'] ?? null;

        if (empty($name) || !$trendyolCategoryId) {
            return;
        }

        // Check if global category exists
        $globalCategory = Category::where('slug', $slug)
            ->where('parent_id', $parentId)
            ->first();

        if ($globalCategory) {
            // Update name if different
            if ($globalCategory->name !== $name) {
                $globalCategory->update(['name' => $name]);
                $updated++;
            }
        } else {
            // Create new global category
            $globalCategory = Category::create([
                'parent_id' => $parentId,
                'level' => $level,
                'name' => $name,
                'slug' => $slug,
                'path' => null, // Will be updated below
                'is_leaf' => empty($categoryData['subCategories']),
                'is_active' => true,
            ]);
            $imported++;
        }

        // Update path
        $path = $this->buildPath($parentPath, $globalCategory->id);
        $globalCategory->update(['path' => $path]);

        // Build marketplace path
        $marketplacePath = $this->buildMarketplacePath($marketplaceParentId, $trendyolCategoryId, $marketplaceId);

        // Create or update marketplace category mapping
        $marketplaceCategory = MarketplaceCategory::updateOrCreate(
            [
                'marketplace_id' => $marketplaceId,
                'marketplace_category_id' => $trendyolCategoryId,
            ],
            [
                'marketplace_parent_id' => $marketplaceParentId,
                'name' => $name,
                'level' => $level,
                'path' => $marketplacePath,
                'global_category_id' => $globalCategory->id,
                'is_mapped' => true,
            ]
        );

        // Process subcategories recursively
        if (!empty($categoryData['subCategories'])) {
            foreach ($categoryData['subCategories'] as $subCategory) {
                $this->importCategoryRecursive(
                    $subCategory,
                    $globalCategory->id,
                    $level + 1,
                    $path,
                    $marketplaceId,
                    $trendyolCategoryId,
                    $imported,
                    $updated
                );
            }
        }
    }

    /**
     * Build category path
     */
    private function buildPath(?string $parentPath, int $id): string
    {
        return $parentPath ? $parentPath . '/' . $id : (string) $id;
    }

    /**
     * Build marketplace category path
     */
    private function buildMarketplacePath(?int $marketplaceParentId, int $categoryId, int $marketplaceId): string
    {
        if ($marketplaceParentId) {
            $parentCategory = MarketplaceCategory::where('marketplace_id', $marketplaceId)
                ->where('marketplace_category_id', $marketplaceParentId)
                ->first();

            if ($parentCategory) {
                // If parent has a path, append to it
                if ($parentCategory->path) {
                    return $parentCategory->path . '/' . $categoryId;
                }
                // If parent exists but no path, create path from parent ID
                return (string) $marketplaceParentId . '/' . $categoryId;
            }
        }

        return (string) $categoryId;
    }

    /**
     * Update category commission or VAT rate
     */
    public function updateRate(Request $request, Category $category)
    {
        $validated = $request->validate([
            'field' => 'required|in:commission_rate,vat_rate',
            'value' => 'nullable|numeric',
        ]);

        $field = $validated['field'];
        $value = $validated['value'] !== null && $validated['value'] !== '' 
            ? ($field === 'commission_rate' ? (float) $validated['value'] : (int) $validated['value'])
            : null;

        $category->update([$field => $value]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori oranı başarıyla güncellendi.',
        ]);
    }
}

