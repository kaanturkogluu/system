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
        // Load Trendyol categories from JSON
        $trendyolCategories = $this->loadTrendyolCategories();
        
        // Get all system categories
        $systemCategories = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();

        // Get existing mappings
        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();
        $existingMappings = [];
        
        if ($trendyolMarketplace) {
            $mappings = MarketplaceCategory::where('marketplace_id', $trendyolMarketplace->id)
                ->whereNotNull('global_category_id')
                ->get()
                ->keyBy('marketplace_category_id');
            
            foreach ($mappings as $mapping) {
                $existingMappings[$mapping->marketplace_category_id] = $mapping->global_category_id;
            }
        }

        return view('admin.marketplace-category-mappings.index', compact(
            'trendyolCategories',
            'systemCategories',
            'existingMappings',
            'trendyolMarketplace'
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

        return $json['categories'];
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'trendyol_category_id' => 'required|integer',
            'global_category_id' => 'nullable|exists:categories,id',
        ]);

        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();
        
        if (!$trendyolMarketplace) {
            return back()->with('error', 'Trendyol pazaryeri bulunamadı.');
        }

        $marketplaceCategory = MarketplaceCategory::firstOrNew([
            'marketplace_id' => $trendyolMarketplace->id,
            'marketplace_category_id' => $validated['trendyol_category_id'],
        ]);

        // If new, we need to get category name from JSON
        if (!$marketplaceCategory->exists) {
            $trendyolCategories = $this->loadTrendyolCategories();
            $categoryData = $this->findCategoryById($trendyolCategories, $validated['trendyol_category_id']);
            
            if ($categoryData) {
                $marketplaceCategory->name = $categoryData['name'];
                $marketplaceCategory->marketplace_parent_id = $categoryData['parentId'] ?? null;
                $marketplaceCategory->level = $this->calculateLevel($trendyolCategories, $validated['trendyol_category_id']);
            }
        }

        $marketplaceCategory->global_category_id = $validated['global_category_id'];
        $marketplaceCategory->is_mapped = !empty($validated['global_category_id']);
        $marketplaceCategory->save();

        return response()->json(['success' => true, 'message' => 'Eşleştirme güncellendi.']);
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

            if (!empty($stats['errors'])) {
                return back()->with('error', 'Hata: ' . implode(', ', $stats['errors']));
            }

            $message = sprintf(
                'Özellikler içe aktarıldı. Oluşturulan: %d, Güncellenen: %d, Değerler: %d',
                $stats['attributes_created'] ?? 0,
                $stats['attributes_updated'] ?? 0,
                $stats['values_created'] ?? 0
            );

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Hata: ' . $e->getMessage());
        }
    }
}

