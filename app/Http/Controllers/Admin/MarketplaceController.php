<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
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
}

