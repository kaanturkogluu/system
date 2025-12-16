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
        
        $categories = Category::where('is_leaf', true)
            ->orderBy('name')
            ->get(['id', 'name', 'path'])
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
                    'full_path' => $fullPath,
                ];
            });

        return view('admin.xml.category-mappings.index', compact('feedSources', 'categories'));
    }

    public function getData(Request $request)
    {
        $feedSourceId = $request->get('feed_source_id');
        $unmappedOnly = $request->boolean('unmapped_only');

        if (!$feedSourceId) {
            return response()->json([]);
        }

        $feedSource = FeedSource::findOrFail($feedSourceId);
        $sourceType = strtolower(str_replace(' ', '_', $feedSource->name)) . '_xml';

        Log::debug('XmlCategoryMapping getData', [
            'feed_source_id' => $feedSourceId,
            'feed_source_name' => $feedSource->name,
            'source_type' => $sourceType,
            'unmapped_only' => $unmappedOnly,
        ]);

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

        $externalCategories = $query->orderBy('raw_path')->get();

        Log::debug('XmlCategoryMapping getData result', [
            'count' => $externalCategories->count(),
            'source_types_in_db' => ExternalCategory::distinct()->pluck('source_type')->toArray(),
        ]);

        $data = $externalCategories->map(function ($ec) {
            return [
                'id' => $ec->id,
                'raw_path' => $ec->raw_path,
                'level' => $ec->level,
                'mapped_category_id' => $ec->mapping && $ec->mapping->status === 'mapped' ? $ec->mapping->category_id : null,
                'mapping_status' => $ec->mapping ? $ec->mapping->status : null,
            ];
        });

        return response()->json($data->values()->all());
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
