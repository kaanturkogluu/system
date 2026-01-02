<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceSetting;
use App\Helpers\MarketplaceConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MarketplaceController extends Controller
{
    /**
     * Display a listing of marketplaces
     */
    public function index()
    {
        $marketplaces = Marketplace::orderBy('name')->paginate(20);
        return view('admin.marketplaces.index', compact('marketplaces'));
    }

    /**
     * Show the form for creating a new marketplace
     */
    public function create()
    {
        return view('admin.marketplaces.create');
    }

    /**
     * Store a newly created marketplace
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'slug' => 'required|string|max:50|unique:marketplaces,slug',
        ], [
            'name.required' => 'Pazaryeri adı gereklidir.',
            'slug.required' => 'Slug gereklidir.',
            'slug.unique' => 'Bu slug zaten kullanılıyor.',
        ]);

        Marketplace::create($validated);

        return redirect()->route('admin.marketplaces.index')
            ->with('success', 'Pazaryeri başarıyla oluşturuldu.');
    }

    /**
     * Show the form for editing a marketplace
     */
    public function edit(Marketplace $marketplace)
    {
        return view('admin.marketplaces.edit', compact('marketplace'));
    }

    /**
     * Update the specified marketplace
     */
    public function update(Request $request, Marketplace $marketplace)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'slug' => [
                'required',
                'string',
                'max:50',
                Rule::unique('marketplaces', 'slug')->ignore($marketplace->id),
            ],
        ], [
            'name.required' => 'Pazaryeri adı gereklidir.',
            'slug.required' => 'Slug gereklidir.',
            'slug.unique' => 'Bu slug zaten kullanılıyor.',
        ]);

        $marketplace->update($validated);

        return redirect()->route('admin.marketplaces.index')
            ->with('success', 'Pazaryeri başarıyla güncellendi.');
    }

    /**
     * Remove the specified marketplace
     */
    public function destroy(Marketplace $marketplace)
    {
        // Check if marketplace has categories
        $categoryCount = \App\Models\MarketplaceCategory::where('marketplace_id', $marketplace->id)->count();
        
        if ($categoryCount > 0) {
            return redirect()->route('admin.marketplaces.index')
                ->with('error', 'Bu pazaryerine ait ' . $categoryCount . ' kategori bulunmaktadır. Önce kategorileri siliniz.');
        }

        $marketplace->delete();

        return redirect()->route('admin.marketplaces.index')
            ->with('success', 'Pazaryeri başarıyla silindi.');
    }

    /**
     * Show marketplace settings form
     */
    public function settings(Marketplace $marketplace)
    {
        $settings = MarketplaceSetting::where('marketplace_id', $marketplace->id)
            ->pluck('value', 'key')
            ->toArray();

        return view('admin.marketplaces.settings', compact('marketplace', 'settings'));
    }

    /**
     * Update marketplace settings
     */
    public function updateSettings(Request $request, Marketplace $marketplace)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            MarketplaceSetting::updateOrCreate(
                [
                    'marketplace_id' => $marketplace->id,
                    'key' => $key,
                ],
                [
                    'value' => $value ?: null,
                    'is_sensitive' => in_array($key, ['api_key', 'api_secret', 'password', 'token']),
                ]
            );
        }

        // Clear cache after update
        MarketplaceConfig::clearCache($marketplace->slug);

        return redirect()->route('admin.marketplaces.settings', $marketplace)
            ->with('success', 'Ayarlar başarıyla güncellendi.');
    }

    /**
     * Show default shipping company selection form
     */
    public function shippingCompanies(Marketplace $marketplace)
    {
        $shippingCompanyMappings = \App\Models\MarketplaceShippingCompanyMapping::where('marketplace_id', $marketplace->id)
            ->with('shippingCompany')
            ->orderBy('is_default', 'desc')
            ->orderBy('external_name')
            ->get();

        $allShippingCompanies = \App\Models\ShippingCompany::where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.marketplaces.shipping-companies', compact('marketplace', 'shippingCompanyMappings', 'allShippingCompanies'));
    }

    /**
     * Update default shipping company for marketplace
     */
    public function updateDefaultShippingCompany(Request $request, Marketplace $marketplace)
    {
        $validated = $request->validate([
            'default_shipping_company_mapping_id' => 'nullable|exists:marketplace_shipping_company_mappings,id',
        ], [
            'default_shipping_company_mapping_id.exists' => 'Seçilen kargo şirketi eşleştirmesi bulunamadı.',
        ]);

        // Remove default from all mappings for this marketplace
        \App\Models\MarketplaceShippingCompanyMapping::where('marketplace_id', $marketplace->id)
            ->update(['is_default' => false]);

        // Set new default if provided
        if ($validated['default_shipping_company_mapping_id']) {
            $mapping = \App\Models\MarketplaceShippingCompanyMapping::find($validated['default_shipping_company_mapping_id']);
            if ($mapping && $mapping->marketplace_id === $marketplace->id) {
                $mapping->update(['is_default' => true]);
            }
        }

        return redirect()->route('admin.marketplaces.shipping-companies', $marketplace)
            ->with('success', 'Varsayılan kargo şirketi başarıyla güncellendi.');
    }
}

