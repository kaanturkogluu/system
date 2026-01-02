<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceShippingCompanyMapping;
use App\Models\ShippingCompany;
use Illuminate\Http\Request;

class ShippingCompanyController extends Controller
{
    /**
     * Display a listing of shipping companies
     */
    public function index(Request $request)
    {
        $query = ShippingCompany::withCount('marketplaceMappings');

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $shippingCompanies = $query->orderBy('name')->paginate(25);

        return view('admin.shipping-companies.index', compact('shippingCompanies'));
    }

    /**
     * Show the form for editing a shipping company
     */
    public function edit(ShippingCompany $shippingCompany)
    {
        $marketplaces = Marketplace::where('status', 'active')->orderBy('name')->get();
        
        // Get mappings for this shipping company
        $mappings = MarketplaceShippingCompanyMapping::where('shipping_company_id', $shippingCompany->id)
            ->with('marketplace')
            ->get()
            ->keyBy('marketplace_id');

        return view('admin.shipping-companies.edit', compact('shippingCompany', 'marketplaces', 'mappings'));
    }

    /**
     * Update the specified shipping company
     */
    public function update(Request $request, ShippingCompany $shippingCompany)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,passive',
        ], [
            'name.required' => 'Kargo şirketi adı gereklidir.',
            'status.required' => 'Durum gereklidir.',
        ]);

        $shippingCompany->update($validated);

        return redirect()->route('admin.shipping-companies.index')
            ->with('success', 'Kargo şirketi başarıyla güncellendi.');
    }

    /**
     * Update marketplace mapping for a shipping company
     */
    public function updateMapping(Request $request, ShippingCompany $shippingCompany)
    {
        $validated = $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'external_id' => 'nullable|integer',
            'external_code' => 'nullable|string|max:100',
            'external_name' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'status' => 'required|in:active,passive',
        ], [
            'marketplace_id.required' => 'Pazaryeri gereklidir.',
            'marketplace_id.exists' => 'Seçilen pazaryeri bulunamadı.',
        ]);

        MarketplaceShippingCompanyMapping::updateOrCreate(
            [
                'marketplace_id' => $validated['marketplace_id'],
                'shipping_company_id' => $shippingCompany->id,
            ],
            [
                'external_id' => $validated['external_id'] ?? null,
                'external_code' => $validated['external_code'] ?? null,
                'external_name' => $validated['external_name'] ?? null,
                'tax_number' => $validated['tax_number'] ?? null,
                'status' => $validated['status'],
            ]
        );

        return redirect()->route('admin.shipping-companies.edit', $shippingCompany)
            ->with('success', 'Pazaryeri eşleştirmesi başarıyla güncellendi.');
    }

    /**
     * Delete marketplace mapping
     */
    public function deleteMapping(ShippingCompany $shippingCompany, MarketplaceShippingCompanyMapping $mapping)
    {
        if ($mapping->shipping_company_id !== $shippingCompany->id) {
            return redirect()->route('admin.shipping-companies.edit', $shippingCompany)
                ->with('error', 'Geçersiz eşleştirme.');
        }

        $mapping->delete();

        return redirect()->route('admin.shipping-companies.edit', $shippingCompany)
            ->with('success', 'Pazaryeri eşleştirmesi başarıyla silindi.');
    }
}
