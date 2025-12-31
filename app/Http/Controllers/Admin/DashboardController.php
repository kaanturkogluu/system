<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Marketplace;
use App\Models\MarketplaceCategory;
use App\Models\Brand;
use App\Models\MarketplaceBrandMapping;
use App\Models\CategoryAttribute;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard
     */
    public function index()
    {
        $trendyolMarketplace = Marketplace::where('slug', 'trendyol')->first();
        
        // Kategori eşleştirmeleri
        $totalCategories = Category::count();
        $mappedCategories = 0;
        $unmappedCategories = 0;
        $unmappedCategoriesList = collect();
        
        if ($trendyolMarketplace) {
            $mappedCategories = MarketplaceCategory::where('marketplace_id', $trendyolMarketplace->id)
                ->where('is_mapped', true)
                ->whereNotNull('global_category_id')
                ->distinct('global_category_id')
                ->count('global_category_id');
            
            $mappedCategoryIds = MarketplaceCategory::where('marketplace_id', $trendyolMarketplace->id)
                ->where('is_mapped', true)
                ->whereNotNull('global_category_id')
                ->pluck('global_category_id')
                ->unique()
                ->toArray();
            
            $unmappedCategories = $totalCategories - count($mappedCategoryIds);
            
            // Eşleştirilmemiş kategoriler listesi (ilk 10)
            $unmappedCategoriesList = Category::whereNotIn('id', $mappedCategoryIds)
                ->where('is_leaf', true) // Sadece yaprak kategoriler
                ->orderBy('name')
                ->limit(10)
                ->get();
        }
        
        // Marka eşleştirmeleri
        $totalBrands = Brand::where('status', 'active')->count();
        $mappedBrands = 0;
        $unmappedBrands = 0;
        $unmappedBrandsList = collect();
        
        if ($trendyolMarketplace) {
            $mappedBrands = MarketplaceBrandMapping::where('marketplace_id', $trendyolMarketplace->id)
                ->where('status', 'mapped')
                ->count();
            
            $unmappedBrands = $totalBrands - $mappedBrands;
            
            // Eşleştirilmemiş markalar listesi (ilk 10)
            $mappedBrandIds = MarketplaceBrandMapping::where('marketplace_id', $trendyolMarketplace->id)
                ->where('status', 'mapped')
                ->pluck('brand_id')
                ->toArray();
            
            $unmappedBrandsList = Brand::where('status', 'active')
                ->whereNotIn('id', $mappedBrandIds)
                ->orderBy('name')
                ->limit(10)
                ->get();
        }
        
        // Özellik aktarılmamış kategoriler (sadece leaf/yaprak kategoriler)
        $categoriesWithoutAttributes = 0;
        $categoriesWithoutAttributesList = collect();
        
        if ($trendyolMarketplace) {
            // Sadece leaf (yaprak) kategorileri kontrol et
            $mappedCategoryIds = MarketplaceCategory::where('marketplace_id', $trendyolMarketplace->id)
                ->where('is_mapped', true)
                ->whereNotNull('global_category_id')
                ->pluck('global_category_id')
                ->unique()
                ->toArray();
            
            // Sadece leaf kategorileri filtrele
            $mappedLeafCategoryIds = Category::whereIn('id', $mappedCategoryIds)
                ->where('is_leaf', true)
                ->pluck('id')
                ->toArray();
            
            $categoriesWithAttributes = CategoryAttribute::whereIn('category_id', $mappedLeafCategoryIds)
                ->distinct('category_id')
                ->pluck('category_id')
                ->toArray();
            
            $categoriesWithoutAttributes = count($mappedLeafCategoryIds) - count($categoriesWithAttributes);
            
            // Özellik aktarılmamış leaf kategoriler listesi (ilk 10)
            $categoriesWithoutAttributesList = Category::whereIn('id', $mappedLeafCategoryIds)
                ->whereNotIn('id', $categoriesWithAttributes)
                ->orderBy('name')
                ->limit(10)
                ->get();
        }
        
        return view('admin.dashboard', compact(
            'totalCategories',
            'mappedCategories',
            'unmappedCategories',
            'unmappedCategoriesList',
            'totalBrands',
            'mappedBrands',
            'unmappedBrands',
            'unmappedBrandsList',
            'categoriesWithoutAttributes',
            'categoriesWithoutAttributesList',
            'trendyolMarketplace'
        ));
    }
}

