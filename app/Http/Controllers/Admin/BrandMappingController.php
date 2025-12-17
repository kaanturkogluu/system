<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Marketplace;
use App\Models\MarketplaceBrandMapping;
use App\Models\MarketplaceBrandSearchResult;
use App\Helpers\BrandNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BrandMappingController extends Controller
{
    /**
     * Display brand list with mapping status
     */
    public function index(Request $request)
    {
        $query = Brand::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('normalized_name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Mapping filter
        $mappingFilter = $request->get('mapping', 'all');
        $marketplaceId = $request->get('marketplace_id');

        if ($mappingFilter === 'unmapped' && $marketplaceId) {
            $mappedBrandIds = MarketplaceBrandMapping::where('marketplace_id', $marketplaceId)
                ->where('status', 'mapped')
                ->pluck('brand_id');
            $query->whereNotIn('id', $mappedBrandIds);
        } elseif ($mappingFilter === 'mapped' && $marketplaceId) {
            $mappedBrandIds = MarketplaceBrandMapping::where('marketplace_id', $marketplaceId)
                ->where('status', 'mapped')
                ->pluck('brand_id');
            $query->whereIn('id', $mappedBrandIds);
        }

        // Order: unmapped first if filter is set
        if ($mappingFilter === 'unmapped') {
            $query->orderByRaw('CASE WHEN id IN (SELECT brand_id FROM marketplace_brand_mappings WHERE marketplace_id = ? AND status = "mapped") THEN 1 ELSE 0 END', [$marketplaceId ?? 0]);
        }
        $query->orderBy('name');

        $brands = $query->paginate(20)->withQueryString();

        // Get all marketplaces for filter
        $marketplaces = Marketplace::where('status', 'active')->orderBy('name')->get();

        // Get mapping status for each brand
        $mappingStatuses = [];
        foreach ($brands as $brand) {
            $mappingStatuses[$brand->id] = MarketplaceBrandMapping::where('brand_id', $brand->id)
                ->where('status', 'mapped')
                ->with('marketplace')
                ->get()
                ->keyBy('marketplace_id');
        }

        return view('admin.brand-mappings.index', compact('brands', 'marketplaces', 'mappingStatuses'));
    }

    /**
     * Show marketplace search results for a brand
     */
    public function showSearchResults(Brand $brand, Marketplace $marketplace)
    {
        // Validate marketplace exists
        if (!$marketplace) {
            return redirect()->route('admin.brand-mappings.index')
                ->with('error', 'Pazaryeri bulunamadı.');
        }

        // Get search results
        $searchResult = MarketplaceBrandSearchResult::where('brand_id', $brand->id)
            ->where('marketplace_id', $marketplace->id)
            ->first();

        // Check if mapping already exists
        $existingMapping = MarketplaceBrandMapping::where('brand_id', $brand->id)
            ->where('marketplace_id', $marketplace->id)
            ->first();

        // Parse response to extract brand options
        $marketplaceBrands = [];
        if ($searchResult && $searchResult->response) {
            $response = $searchResult->response;
            
            // Handle different response structures
            if (isset($response['content']) && is_array($response['content'])) {
                // If response has 'content' array
                foreach ($response['content'] as $item) {
                    if (isset($item['id']) && isset($item['name'])) {
                        $marketplaceBrands[] = [
                            'id' => $item['id'],
                            'name' => $item['name'],
                        ];
                    }
                }
            } elseif (is_array($response) && isset($response[0])) {
                // If response is array of items
                foreach ($response as $item) {
                    if (isset($item['id']) && isset($item['name'])) {
                        $marketplaceBrands[] = [
                            'id' => $item['id'],
                            'name' => $item['name'],
                        ];
                    }
                }
            } elseif (isset($response['id']) && isset($response['name'])) {
                // Single item
                $marketplaceBrands[] = [
                    'id' => $response['id'],
                    'name' => $response['name'],
                ];
            }
        }

        return view('admin.brand-mappings.search-results', compact(
            'brand',
            'marketplace',
            'searchResult',
            'marketplaceBrands',
            'existingMapping'
        ));
    }

    /**
     * Store brand mapping
     */
    public function store(Request $request, Brand $brand, Marketplace $marketplace)
    {
        $validated = $request->validate([
            'marketplace_brand_id' => 'required|string|max:100',
            'marketplace_brand_name' => 'required|string|max:255',
            'confirm_overwrite' => 'nullable|boolean',
        ], [
            'marketplace_brand_id.required' => 'Pazaryeri marka ID\'si gereklidir.',
            'marketplace_brand_name.required' => 'Pazaryeri marka adı gereklidir.',
        ]);

        // Validate brand exists
        if (!$brand) {
            return redirect()->route('admin.brand-mappings.index')
                ->with('error', 'Marka bulunamadı.');
        }

        // Validate marketplace exists
        if (!$marketplace) {
            return redirect()->route('admin.brand-mappings.index')
                ->with('error', 'Pazaryeri bulunamadı.');
        }

        // Check if mapping already exists
        $existingMapping = MarketplaceBrandMapping::where('brand_id', $brand->id)
            ->where('marketplace_id', $marketplace->id)
            ->first();

        if ($existingMapping && !$request->has('confirm_overwrite')) {
            return redirect()->back()
                ->with('error', 'Bu marka için zaten bir eşleştirme mevcut. Üzerine yazmak için onaylayın.')
                ->withInput();
        }

        // Validate marketplace_brand_id comes from cached search results
        $searchResult = MarketplaceBrandSearchResult::where('brand_id', $brand->id)
            ->where('marketplace_id', $marketplace->id)
            ->first();

        if (!$searchResult) {
            return redirect()->back()
                ->with('error', 'Pazaryeri arama sonuçları bulunamadı. Önce marka arama sonuçlarını yükleyin.')
                ->withInput();
        }

        // Validate the selected brand_id exists in search results
        $response = $searchResult->response ?? [];
        $validBrandIds = [];
        
        if (isset($response['content']) && is_array($response['content'])) {
            foreach ($response['content'] as $item) {
                if (isset($item['id'])) {
                    $validBrandIds[] = (string) $item['id'];
                }
            }
        } elseif (is_array($response) && isset($response[0])) {
            foreach ($response as $item) {
                if (isset($item['id'])) {
                    $validBrandIds[] = (string) $item['id'];
                }
            }
        } elseif (isset($response['id'])) {
            $validBrandIds[] = (string) $response['id'];
        }

        if (!in_array((string) $validated['marketplace_brand_id'], $validBrandIds, true)) {
            return redirect()->back()
                ->with('error', 'Seçilen pazaryeri marka ID\'si geçersiz. Lütfen arama sonuçlarından birini seçin.')
                ->withInput();
        }

        try {
            DB::transaction(function () use ($brand, $marketplace, $validated) {
                MarketplaceBrandMapping::updateOrCreate(
                    [
                        'marketplace_id' => $marketplace->id,
                        'brand_id' => $brand->id,
                    ],
                    [
                        'marketplace_brand_id' => $validated['marketplace_brand_id'],
                        'marketplace_brand_name' => $validated['marketplace_brand_name'],
                        'status' => 'mapped',
                    ]
                );
            });

            return redirect()->route('admin.brand-mappings.index')
                ->with('success', "Marka '{$brand->name}' başarıyla '{$marketplace->name}' pazaryerine eşleştirildi.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Eşleştirme kaydedilirken bir hata oluştu: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete brand mapping
     */
    public function destroy(Brand $brand, Marketplace $marketplace)
    {
        $mapping = MarketplaceBrandMapping::where('brand_id', $brand->id)
            ->where('marketplace_id', $marketplace->id)
            ->first();

        if (!$mapping) {
            return redirect()->route('admin.brand-mappings.index')
                ->with('error', 'Eşleştirme bulunamadı.');
        }

        $mapping->delete();

        return redirect()->route('admin.brand-mappings.index')
            ->with('success', 'Eşleştirme başarıyla silindi.');
    }

    /**
     * Auto-map brands based on 100% match
     */
    public function autoMap(Request $request)
    {
        $validated = $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
        ]);

        $marketplace = Marketplace::find($validated['marketplace_id']);

        if (!$marketplace) {
            return response()->json([
                'success' => false,
                'message' => 'Pazaryeri bulunamadı.',
            ], 404);
        }

        try {
            // Get all unmapped active brands
            $mappedBrandIds = MarketplaceBrandMapping::where('marketplace_id', $marketplace->id)
                ->where('status', 'mapped')
                ->pluck('brand_id')
                ->toArray();

            $unmappedBrands = Brand::where('status', 'active')
                ->whereNotIn('id', $mappedBrandIds)
                ->whereNotNull('normalized_name')
                ->get();

            // Get all search results for this marketplace
            $searchResults = MarketplaceBrandSearchResult::where('marketplace_id', $marketplace->id)
                ->with('brand')
                ->get();

            $matched = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($unmappedBrands as $brand) {
                if (empty($brand->normalized_name)) {
                    $skipped++;
                    continue;
                }

                // Find search result for this brand
                $searchResult = $searchResults->firstWhere('brand_id', $brand->id);

                if (!$searchResult || empty($searchResult->response)) {
                    $skipped++;
                    continue;
                }

                // Parse marketplace brands from response
                $marketplaceBrands = $this->parseMarketplaceBrands($searchResult->response);

                if (empty($marketplaceBrands)) {
                    $skipped++;
                    continue;
                }

                // Find 100% match
                $matchedBrand = null;
                $brandNormalized = $brand->normalized_name;

                foreach ($marketplaceBrands as $mpBrand) {
                    $mpBrandNormalized = BrandNormalizer::normalize($mpBrand['name']);
                    
                    // 100% exact match
                    if ($brandNormalized === $mpBrandNormalized) {
                        $matchedBrand = $mpBrand;
                        break;
                    }
                }

                if ($matchedBrand) {
                    try {
                        MarketplaceBrandMapping::updateOrCreate(
                            [
                                'marketplace_id' => $marketplace->id,
                                'brand_id' => $brand->id,
                            ],
                            [
                                'marketplace_brand_id' => (string) $matchedBrand['id'],
                                'marketplace_brand_name' => $matchedBrand['name'],
                                'status' => 'mapped',
                            ]
                        );
                        $matched++;
                    } catch (\Exception $e) {
                        $errors[] = "Marka '{$brand->name}': " . $e->getMessage();
                        $skipped++;
                    }
                } else {
                    $skipped++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Otomatik eşleştirme tamamlandı.",
                'data' => [
                    'matched' => $matched,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Otomatik eşleştirme sırasında hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse marketplace brands from API response
     */
    private function parseMarketplaceBrands(array $response): array
    {
        $marketplaceBrands = [];

        // Handle different response structures
        if (isset($response['content']) && is_array($response['content'])) {
            foreach ($response['content'] as $item) {
                if (isset($item['id']) && isset($item['name'])) {
                    $marketplaceBrands[] = [
                        'id' => $item['id'],
                        'name' => $item['name'],
                    ];
                }
            }
        } elseif (is_array($response) && isset($response[0])) {
            foreach ($response as $item) {
                if (isset($item['id']) && isset($item['name'])) {
                    $marketplaceBrands[] = [
                        'id' => $item['id'],
                        'name' => $item['name'],
                    ];
                }
            }
        } elseif (isset($response['id']) && isset($response['name'])) {
            $marketplaceBrands[] = [
                'id' => $response['id'],
                'name' => $response['name'],
            ];
        }

        return $marketplaceBrands;
    }
}

