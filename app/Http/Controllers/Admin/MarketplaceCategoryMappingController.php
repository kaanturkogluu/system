<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceCategory;
use App\Models\Category;
use Illuminate\Http\Request;

class MarketplaceCategoryMappingController extends Controller
{
    public function index(Request $request)
    {
        // Get active marketplaces
        $activeMarketplaces = Marketplace::where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get selected marketplace ID from request or default to first active marketplace
        $selectedMarketplaceId = $request->get('marketplace_id');
        
        // If no marketplace selected, use first active marketplace
        if (!$selectedMarketplaceId && $activeMarketplaces->isNotEmpty()) {
            $selectedMarketplaceId = $activeMarketplaces->first()->id;
        }

        $selectedMarketplace = null;
        $marketplaceCategories = [];
        $existingMappings = [];
        $mappingDetails = [];
        $hasCategories = false;

        if ($selectedMarketplaceId) {
            $selectedMarketplace = Marketplace::find($selectedMarketplaceId);
            
            if ($selectedMarketplace) {
                // Load categories based on marketplace
                if ($selectedMarketplace->slug === 'trendyol') {
                    // For Trendyol, load from JSON file
                    $marketplaceCategories = $this->loadTrendyolCategories();
                } elseif ($selectedMarketplace->slug === 'n11') {
                    // For N11, load from JSON file
                    $marketplaceCategories = $this->loadN11Categories();
                } else {
                    // For other marketplaces, load from database
                    $marketplaceCategories = $this->loadMarketplaceCategoriesFromDb($selectedMarketplace->id);
                }

                // Check if categories exist
                $hasCategories = !empty($marketplaceCategories);

                // Get existing mappings with commission rates
                $mappings = MarketplaceCategory::where('marketplace_id', $selectedMarketplace->id)
                    ->whereNotNull('global_category_id')
                    ->with('globalCategory')
                    ->get()
                    ->keyBy('marketplace_category_id');
                
                foreach ($mappings as $mapping) {
                    $existingMappings[$mapping->marketplace_category_id] = $mapping->global_category_id;
                    $mappingDetails[$mapping->marketplace_category_id] = [
                        'id' => $mapping->id,
                        'global_category_id' => $mapping->global_category_id,
                        'commission_rate' => $mapping->commission_rate,
                        'category_name' => $mapping->globalCategory ? $mapping->globalCategory->name : null,
                    ];
                }
            }
        }
        
        // Get all system categories
        $systemCategories = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();

        return view('admin.marketplace-category-mappings.index', compact(
            'activeMarketplaces',
            'selectedMarketplace',
            'selectedMarketplaceId',
            'marketplaceCategories',
            'systemCategories',
            'existingMappings',
            'mappingDetails',
            'hasCategories'
        ));
    }

    private function loadTrendyolCategories()
    {
        $jsonPath = base_path('trendyol_categories.json');
        
        if (!file_exists($jsonPath)) {
            return [];
        }

        $json = json_decode(file_get_contents($jsonPath), true);
        
        if (empty($json) || !isset($json['categories'])) {
            return [];
        }

        return $this->convertFlatCategoriesToTree($json['categories']);
    }

    /**
     * Load N11 categories from JSON file
     */
    private function loadN11Categories()
    {
        $jsonPath = base_path('n11_categories.json');
        
        if (!file_exists($jsonPath)) {
            return [];
        }

        $json = json_decode(file_get_contents($jsonPath), true);
        
        if (empty($json) || !isset($json['categories'])) {
            return [];
        }

        return $this->convertFlatCategoriesToTree($json['categories']);
    }

    /**
     * Convert flat category array to tree structure
     */
    private function convertFlatCategoriesToTree($flatCategories)
    {
        // Create a map for quick lookup
        $categoryMap = [];
        foreach ($flatCategories as $cat) {
            $categoryMap[$cat['id']] = $cat;
            $categoryMap[$cat['id']]['subCategories'] = [];
        }

        // Build tree
        $tree = [];
        foreach ($flatCategories as $cat) {
            if (empty($cat['parentId']) || !isset($categoryMap[$cat['parentId']])) {
                // Root category
                $tree[] = $this->buildCategoryTree($cat, $categoryMap);
            }
        }

        return $tree;
    }

    /**
     * Build category tree recursively
     */
    private function buildCategoryTree($category, $categoryMap)
    {
        $tree = [
            'id' => $category['id'],
            'name' => $category['name'],
            'parentId' => $category['parentId'] ?? null,
            'subCategories' => [],
        ];

        // Find children
        foreach ($categoryMap as $cat) {
            if (isset($cat['parentId']) && $cat['parentId'] == $category['id']) {
                $tree['subCategories'][] = $this->buildCategoryTree($cat, $categoryMap);
            }
        }

        return $tree;
    }

    /**
     * Load marketplace categories from database
     */
    private function loadMarketplaceCategoriesFromDb($marketplaceId)
    {
        // Load all categories for this marketplace
        $allCategories = MarketplaceCategory::where('marketplace_id', $marketplaceId)
            ->orderBy('name')
            ->get();

        // Get root categories (parent_id is null)
        $rootCategories = $allCategories->whereNull('marketplace_parent_id');

        // Build a map for quick lookup
        $categoryMap = $allCategories->keyBy('marketplace_category_id');

        return $this->convertDbCategoriesToArray($rootCategories, $categoryMap);
    }

    /**
     * Convert database categories to array format similar to JSON structure
     */
    private function convertDbCategoriesToArray($categories, $categoryMap, $level = 0)
    {
        $result = [];
        
        foreach ($categories as $category) {
            $categoryData = [
                'id' => $category->marketplace_category_id,
                'name' => $category->name,
                'parentId' => $category->marketplace_parent_id,
                'level' => $category->level ?? $level,
            ];

            // Find children for this category
            $children = $categoryMap->filter(function ($cat) use ($category) {
                return $cat->marketplace_parent_id == $category->marketplace_category_id;
            });

            // Recursively add children
            if ($children->isNotEmpty()) {
                $categoryData['subCategories'] = $this->convertDbCategoriesToArray(
                    $children,
                    $categoryMap,
                    $level + 1
                );
            }

            $result[] = $categoryData;
        }

        return $result;
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'marketplace_category_id' => 'required|integer',
            'global_category_id' => 'nullable|exists:categories,id',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $marketplace = Marketplace::find($validated['marketplace_id']);
        
        if (!$marketplace) {
            return response()->json(['success' => false, 'message' => 'Pazaryeri bulunamadı.'], 404);
        }

        $marketplaceCategory = MarketplaceCategory::firstOrNew([
            'marketplace_id' => $marketplace->id,
            'marketplace_category_id' => $validated['marketplace_category_id'],
        ]);

        // If new, we need to get category data
        if (!$marketplaceCategory->exists) {
            if ($marketplace->slug === 'trendyol') {
                // For Trendyol, get from JSON
                $trendyolCategories = $this->loadTrendyolCategories();
                $categoryData = $this->findCategoryById($trendyolCategories, $validated['marketplace_category_id']);
                
                if ($categoryData) {
                    $marketplaceCategory->name = $categoryData['name'];
                    $marketplaceCategory->marketplace_parent_id = $categoryData['parentId'] ?? null;
                    $marketplaceCategory->level = $this->calculateLevel($trendyolCategories, $validated['marketplace_category_id']);
                }
            } elseif ($marketplace->slug === 'n11') {
                // For N11, get from JSON
                $n11Categories = $this->loadN11Categories();
                $categoryData = $this->findCategoryById($n11Categories, $validated['marketplace_category_id']);
                
                if ($categoryData) {
                    $marketplaceCategory->name = $categoryData['name'];
                    $marketplaceCategory->marketplace_parent_id = $categoryData['parentId'] ?? null;
                    $marketplaceCategory->level = $this->calculateLevel($n11Categories, $validated['marketplace_category_id']);
                }
            } else {
                // For other marketplaces, try to find in database
                $existingCategory = MarketplaceCategory::where('marketplace_id', $marketplace->id)
                    ->where('marketplace_category_id', $validated['marketplace_category_id'])
                    ->first();
                
                if ($existingCategory) {
                    $marketplaceCategory->name = $existingCategory->name;
                    $marketplaceCategory->marketplace_parent_id = $existingCategory->marketplace_parent_id;
                    $marketplaceCategory->level = $existingCategory->level;
                }
            }
        }

        $marketplaceCategory->global_category_id = $validated['global_category_id'];
        $marketplaceCategory->is_mapped = !empty($validated['global_category_id']);
        
        // Komisyon oranını güncelle
        if (isset($validated['commission_rate'])) {
            $marketplaceCategory->commission_rate = $validated['commission_rate'] !== null && $validated['commission_rate'] !== '' 
                ? (float) $validated['commission_rate'] 
                : null;
        }
        
        $marketplaceCategory->save();

        return response()->json(['success' => true, 'message' => 'Eşleştirme ve komisyon oranı güncellendi.']);
    }

    /**
     * Update marketplace category commission rate
     */
    public function updateCommissionRate(Request $request, MarketplaceCategory $marketplaceCategory)
    {
        $validated = $request->validate([
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $commissionRate = $validated['commission_rate'] !== null && $validated['commission_rate'] !== '' 
            ? (float) $validated['commission_rate'] 
            : null;

        $marketplaceCategory->update(['commission_rate' => $commissionRate]);

        return response()->json([
            'success' => true,
            'message' => 'Pazaryeri kategori komisyon oranı başarıyla güncellendi.',
        ]);
    }

    private function findCategoryById(array $categories, int $id): ?array
    {
        foreach ($categories as $category) {
            if ($category['id'] == $id) {
                return $category;
            }
            
            if (!empty($category['subCategories'])) {
                $found = $this->findCategoryById($category['subCategories'], $id);
                if ($found) {
                    return $found;
                }
            }
        }
        
        return null;
    }

    private function calculateLevel(array $categories, int $id, int $currentLevel = 0): int
    {
        foreach ($categories as $category) {
            if ($category['id'] == $id) {
                return $currentLevel;
            }
            
            if (!empty($category['subCategories'])) {
                $level = $this->calculateLevel($category['subCategories'], $id, $currentLevel + 1);
                if ($level >= 0) {
                    return $level;
                }
            }
        }
        
        return 0;
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'marketplace_category_ids' => 'required|array',
            'marketplace_category_ids.*' => 'exists:marketplace_categories,id',
            'global_category_id' => 'nullable|exists:categories,id',
        ]);

        MarketplaceCategory::whereIn('id', $validated['marketplace_category_ids'])
            ->update([
                'global_category_id' => $validated['global_category_id'],
                'is_mapped' => !empty($validated['global_category_id']),
            ]);

        return back()->with('success', count($validated['marketplace_category_ids']) . ' kategori eşleştirildi.');
    }

    public function getCategories(Request $request)
    {
        $search = $request->get('search', '');
        $parentId = $request->get('parent_id');
        $withChildren = $request->get('with_children', false);

        $query = Category::query();

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($withChildren) {
            $query->with('children');
        }

        $categories = $query->orderBy('name')->get();

        return response()->json($categories);
    }

    public function importAttributes(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        try {
            $service = app(\App\Services\TrendyolCategoryAttributeService::class);
            $stats = $service->importAttributesForCategory($validated['category_id']);

            // Check for errors first
            if (!empty($stats['errors'])) {
                \Log::channel('imports')->error('Import attributes failed', [
                    'category_id' => $validated['category_id'],
                    'errors' => $stats['errors'],
                ]);
                return back()->with('error', 'Hata: ' . implode(', ', $stats['errors']));
            }

            // Check if any attributes were processed
            $totalProcessed = ($stats['attributes_created'] ?? 0) + ($stats['attributes_updated'] ?? 0);
            
            if ($totalProcessed === 0) {
                \Log::channel('imports')->warning('No attributes imported', [
                    'category_id' => $validated['category_id'],
                    'stats' => $stats,
                ]);
                return back()->with('warning', 'Bu kategori için Trendyol\'dan özellik bulunamadı veya zaten mevcut.');
            }

            $message = sprintf(
                'Özellikler içe aktarıldı. Oluşturulan: %d, Güncellenen: %d, Değerler: %d',
                $stats['attributes_created'] ?? 0,
                $stats['attributes_updated'] ?? 0,
                $stats['values_created'] ?? 0
            );

            \Log::channel('imports')->info('Attributes imported successfully', [
                'category_id' => $validated['category_id'],
                'stats' => $stats,
            ]);

            return back()->with('success', $message);

        } catch (\Exception $e) {
            \Log::channel('imports')->error('Import attributes exception', [
                'category_id' => $validated['category_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Hata: ' . $e->getMessage());
        }
    }
}

