<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\XmlAttributeMapping;
use App\Services\XmlAttributeAnalysisService;
use Illuminate\Http\Request;

class XmlAttributeAnalysisController extends Controller
{
    /**
     * Show analysis page
     */
    public function index(Request $request)
    {
        $limit = (int) $request->get('limit', 1000);
        $categorySearch = $request->get('category_search');
        $analysis = null;
        $mappings = [];
        $summary = null;
        $groupedByCategory = null;

        if ($request->has('analyze')) {
            $service = new XmlAttributeAnalysisService();
            
            // Phase 1: Discover
            $xmlAttributes = $service->discoverXmlAttributes($limit, $categorySearch);
            
            if (!empty($xmlAttributes)) {
                // Phase 2: Analyze and match
                $analysis = $service->analyzeAndMatch($xmlAttributes);
                
                // Phase 3: Prepare mappings
                $mappings = $service->prepareMappings($analysis);
                
                // Group by category
                $groupedByCategory = $this->groupByCategory($analysis);
                
                // Summary
                $matched = array_filter($analysis, fn($a) => $a['confidence'] === 'HIGH' && $a['matched_attribute_id']);
                $needsReview = array_filter($analysis, fn($a) => $a['confidence'] === 'MEDIUM' || ($a['confidence'] === 'LOW' && $a['matched_attribute_id']));
                $unmapped = array_filter($analysis, fn($a) => $a['confidence'] === 'LOW' && !$a['matched_attribute_id']);
                
                $summary = [
                    'total' => count($analysis),
                    'matched' => count($matched),
                    'needs_review' => count($needsReview),
                    'unmapped' => count($unmapped),
                ];
            }
        }

        // Get existing mappings
        $existingMappings = XmlAttributeMapping::with('attribute')
            ->where('source_type', 'xml')
            ->orderBy('source_attribute_key')
            ->get();

        // Get all attributes for manual mapping (will be filtered by category in modal)
        $allAttributes = Attribute::where('status', 'active')
            ->orderBy('name')
            ->get();
        
        // Group attributes by category for modal filtering
        $attributesByCategory = [];
        foreach ($allAttributes as $attribute) {
            $categoryIds = $attribute->categories()->pluck('categories.id')->toArray();
            foreach ($categoryIds as $categoryId) {
                if (!isset($attributesByCategory[$categoryId])) {
                    $attributesByCategory[$categoryId] = [];
                }
                $attributesByCategory[$categoryId][] = [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'code' => $attribute->code,
                ];
            }
        }

        // Get mapped categories for filter dropdown
        $mappedCategories = \App\Models\CategoryMapping::where('status', 'mapped')
            ->with('category')
            ->get()
            ->map(function ($mapping) {
                return [
                    'id' => $mapping->category_id,
                    'name' => $mapping->category->name ?? 'Unknown',
                    'external_category_id' => $mapping->external_category_id,
                ];
            })
            ->filter(function ($item) {
                return $item['name'] !== 'Unknown';
            })
            ->sortBy('name')
            ->values();

        return view('admin.xml-attribute-analysis.index', compact(
            'analysis',
            'mappings',
            'summary',
            'groupedByCategory',
            'existingMappings',
            'allAttributes',
            'attributesByCategory',
            'limit',
            'categorySearch',
            'mappedCategories'
        ));
    }

    /**
     * Group analysis by category
     */
    private function groupByCategory(array $analysis): array
    {
        $grouped = [];

        foreach ($analysis as $item) {
            $categories = $item['categories'] ?? [];
            $categoryNames = $item['category_names'] ?? [];

            if (empty($categories)) {
                // No category - put in "Uncategorized"
                if (!isset($grouped['_uncategorized'])) {
                    $grouped['_uncategorized'] = [
                        'category_id' => null,
                        'category_name' => 'Kategori Belirtilmemiş',
                        'attributes' => [],
                    ];
                }
                $grouped['_uncategorized']['attributes'][] = $item;
            } else {
                // Group by each category
                foreach ($categories as $categoryId) {
                    $categoryName = $categoryNames[$categoryId] ?? "Kategori ID: {$categoryId}";
                    
                    if (!isset($grouped[$categoryId])) {
                        $grouped[$categoryId] = [
                            'category_id' => $categoryId,
                            'category_name' => $categoryName,
                            'attributes' => [],
                        ];
                    }
                    
                    // Only add if not already added to this category
                    $exists = false;
                    foreach ($grouped[$categoryId]['attributes'] as $existing) {
                        if ($existing['xml_attribute_key'] === $item['xml_attribute_key']) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if (!$exists) {
                        $grouped[$categoryId]['attributes'][] = $item;
                    }
                }
            }
        }

        // Sort by category name
        uasort($grouped, function($a, $b) {
            return strcmp($a['category_name'], $b['category_name']);
        });

        return $grouped;
    }

    /**
     * Create mapping manually
     */
    public function storeMapping(Request $request)
    {
        $validated = $request->validate([
            'source_attribute_key' => 'required|string|max:255',
            'attribute_id' => 'required|exists:attributes,id',
        ]);

        $mapping = XmlAttributeMapping::updateOrCreate(
            [
                'source_type' => 'xml',
                'source_attribute_key' => $validated['source_attribute_key'],
            ],
            [
                'attribute_id' => $validated['attribute_id'],
                'status' => 'active',
            ]
        );

        return back()->with('success', 'Mapping başarıyla oluşturuldu.');
    }

    /**
     * Delete mapping
     */
    public function deleteMapping(XmlAttributeMapping $mapping)
    {
        $mapping->delete();
        return back()->with('success', 'Mapping başarıyla silindi.');
    }
}
