<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryMapping;
use App\Models\ExternalCategory;
use App\Models\FeedSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XmlCategoryMappingController extends Controller
{
    public function index()
    {
        $feedSources = FeedSource::where('type', 'xml')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Sadece ana kategorileri getir (parent_id = null)
        $mainCategories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.xml.category-mappings.index', compact('feedSources', 'mainCategories'));
    }

    public function getCategories(Request $request)
    {
        $parentCategoryId = $request->get('parent_category_id');
        $search = $request->get('search');

        $query = Category::where('is_leaf', true)
            ->where('is_active', true);

        // Ana kategori seçilmişse, sadece o ana kategori altındaki kategorileri getir
        if ($parentCategoryId) {
            $parentCategory = Category::find($parentCategoryId);
            if ($parentCategory) {
                // Path ile filtreleme: parent'ın id'si path içinde geçen veya direkt parent_id'si eşit olan
                $query->where(function ($q) use ($parentCategory) {
                    // Path'te parent id geçiyorsa veya direkt parent_id eşitse
                    $q->where('path', 'like', $parentCategory->id . '/%')
                        ->orWhere('path', 'like', '%/' . $parentCategory->id . '/%')
                        ->orWhere('path', 'like', '%/' . $parentCategory->id)
                        ->orWhere('parent_id', $parentCategory->id);
                    
                    // Eğer parent'ın kendisi leaf ise, onu da dahil et
                    if ($parentCategory->is_leaf) {
                        $q->orWhere('id', $parentCategory->id);
                    }
                });
            }
        }

        // Arama varsa
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'path', 'parent_id'])
            ->map(function ($cat) {
                $fullPath = $cat->name;
                if ($cat->path) {
                    $pathIds = explode('/', $cat->path);
                    $pathNames = [];
                    foreach ($pathIds as $pathId) {
                        $parent = Category::find($pathId);
                        if ($parent) {
                            $pathNames[] = $parent->name;
                        }
                    }
                    if (!empty($pathNames)) {
                        $fullPath = implode(' > ', $pathNames) . ' > ' . $cat->name;
                    }
                }
                return [
                    'id' => $cat->id,
                    'text' => $fullPath,
                    'full_path' => $fullPath,
                ];
            });

        return response()->json(['results' => $categories]);
    }

    public function getData(Request $request)
    {
        $feedSourceId = $request->get('feed_source_id');
        $unmappedOnly = $request->boolean('unmapped_only');
        $parentCategoryId = $request->get('parent_category_id');
        $page = $request->get('page', 1);
        $perPage = 50;

        if (!$feedSourceId) {
            return response()->json([
                'data' => [],
                'links' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ],
            ]);
        }

        $feedSource = FeedSource::findOrFail($feedSourceId);
        $sourceType = strtolower(str_replace(' ', '_', $feedSource->name)) . '_xml';

        $query = ExternalCategory::with('mapping.category')
            ->where('source_type', $sourceType);

        if ($unmappedOnly) {
            $query->where(function ($q) {
                $q->whereDoesntHave('mapping')
                    ->orWhereHas('mapping', function ($subQ) {
                        $subQ->where('status', '!=', 'mapped');
                    });
            });
        }

        // Ana kategori filtresi kaldırıldı - sadece kategori seçiminde kullanılacak
        // External categories listesini filtrelemeyeceğiz, sadece kategori dropdown'ında filtreleme yapacağız

        $externalCategories = $query->orderBy('raw_path')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = $externalCategories->map(function ($ec) {
            return [
                'id' => $ec->id,
                'raw_path' => $ec->raw_path,
                'level' => $ec->level,
                'mapped_category_id' => $ec->mapping && $ec->mapping->status === 'mapped' ? $ec->mapping->category_id : null,
                'mapping_status' => $ec->mapping ? $ec->mapping->status : null,
            ];
        });

        return response()->json([
            'data' => $data->values()->all(),
            'links' => $externalCategories->linkCollection(),
            'meta' => [
                'current_page' => $externalCategories->currentPage(),
                'last_page' => $externalCategories->lastPage(),
                'per_page' => $externalCategories->perPage(),
                'total' => $externalCategories->total(),
            ],
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $requestData = $request->all();
        
        if (isset($requestData['mappings']) && is_array($requestData['mappings'])) {
            $mappingsToProcess = $requestData['mappings'];
        } elseif (is_array($requestData) && isset($requestData[0])) {
            $mappingsToProcess = $requestData;
        } else {
            return response()->json(['success' => false, 'message' => 'Geçersiz veri formatı'], 400);
        }

        DB::transaction(function () use ($mappingsToProcess) {
            foreach ($mappingsToProcess as $mapping) {
                $externalId = $mapping['external_id'] ?? null;
                $globalId = $mapping['global_id'] ?? null;

                if (!$externalId || !$globalId) {
                    continue;
                }

                if (!ExternalCategory::find($externalId)) {
                    continue;
                }

                $category = Category::find($globalId);

                if (!$category || !$category->is_leaf) {
                    continue;
                }

                CategoryMapping::updateOrCreate(
                    [
                        'external_category_id' => $externalId,
                    ],
                    [
                        'category_id' => $globalId,
                        'status' => 'mapped',
                        'confidence' => 100,
                    ]
                );
            }
        });

        return response()->json(['success' => true, 'message' => 'Eşleştirmeler başarıyla kaydedildi']);
    }
}
