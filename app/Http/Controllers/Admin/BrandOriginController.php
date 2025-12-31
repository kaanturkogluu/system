<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Country;
use App\Models\Marketplace;
use App\Models\MarketplaceCountryMapping;
use Illuminate\Http\Request;

class BrandOriginController extends Controller
{
    /**
     * Display brands with missing origin information
     */
    public function index(Request $request)
    {
        $query = Brand::where('status', 'active')
            ->with('originCountry');

        // Filter: missing origin
        if ($request->has('filter') && $request->filter === 'missing') {
            $query->whereNull('origin_country_id');
        }
        // Filter: has origin
        elseif ($request->has('filter') && $request->filter === 'has') {
            $query->whereNotNull('origin_country_id');
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $brands = $query->orderBy('name')->paginate(25);
        
        $countries = Country::where('status', 'active')
            ->orderBy('name')
            ->get();

        $marketplaces = Marketplace::where('status', 'active')
            ->orderBy('name')
            ->get();

        // Get all marketplace country mappings for quick lookup
        $marketplaceCountryMappings = MarketplaceCountryMapping::all()
            ->groupBy('marketplace_id')
            ->map(function ($group) {
                return $group->groupBy('country_id');
            });

        // Statistics
        $totalBrands = Brand::where('status', 'active')->count();
        $brandsWithOrigin = Brand::where('status', 'active')
            ->whereNotNull('origin_country_id')
            ->count();
        $brandsWithoutOrigin = $totalBrands - $brandsWithOrigin;

        return view('admin.brand-origins.index', compact(
            'brands',
            'countries',
            'marketplaces',
            'marketplaceCountryMappings',
            'totalBrands',
            'brandsWithOrigin',
            'brandsWithoutOrigin'
        ));
    }

    /**
     * Update brand origin
     */
    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'origin_country_id' => 'nullable|exists:countries,id',
        ]);

        $brand->origin_country_id = $validated['origin_country_id'] ?? null;
        $brand->save();

        return back()->with('success', "Marka '{$brand->name}' menşei başarıyla güncellendi.");
    }

    /**
     * Bulk update brand origins
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'brand_ids' => 'required|array',
            'brand_ids.*' => 'exists:brands,id',
            'origin_country_id' => 'nullable|exists:countries,id',
        ]);

        $count = Brand::whereIn('id', $validated['brand_ids'])
            ->update(['origin_country_id' => $validated['origin_country_id'] ?? null]);

        return back()->with('success', "{$count} marka menşei başarıyla güncellendi.");
    }

    /**
     * Update marketplace country mapping
     */
    public function updateMarketplaceMapping(Request $request)
    {
        $validated = $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'country_id' => 'required|exists:countries,id',
            'external_country_id' => 'nullable|integer',
            'external_country_code' => 'nullable|string|max:50',
            'external_country_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,passive',
        ]);

        $mapping = MarketplaceCountryMapping::updateOrCreate(
            [
                'marketplace_id' => $validated['marketplace_id'],
                'country_id' => $validated['country_id'],
            ],
            [
                'external_country_id' => $validated['external_country_id'] ?? null,
                'external_country_code' => $validated['external_country_code'] ?? null,
                'external_country_name' => $validated['external_country_name'] ?? null,
                'status' => $validated['status'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pazaryeri menşei eşleştirmesi başarıyla güncellendi.',
            'mapping' => $mapping,
        ]);
    }

    /**
     * Delete marketplace country mapping
     */
    public function deleteMarketplaceMapping(Request $request)
    {
        $validated = $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'country_id' => 'required|exists:countries,id',
        ]);

        $deleted = MarketplaceCountryMapping::where('marketplace_id', $validated['marketplace_id'])
            ->where('country_id', $validated['country_id'])
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Pazaryeri menşei eşleştirmesi başarıyla silindi.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Eşleştirme bulunamadı.',
        ], 404);
    }
}
