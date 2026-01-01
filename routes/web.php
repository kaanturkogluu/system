<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;

// Public Routes
Route::get('/', function () {
    return view('welcome');
});

// Login route for auth middleware redirect
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Auth Routes
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Protected Routes
    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('categories.index');
        
        // Attributes
        Route::resource('attributes', \App\Http\Controllers\Admin\AttributeController::class);
        
        // Category Attributes
        Route::prefix('category-attributes')->name('category-attributes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\CategoryAttributeController::class, 'index'])->name('index');
            Route::post('/{category}', [\App\Http\Controllers\Admin\CategoryAttributeController::class, 'store'])->name('store');
            Route::put('/{categoryAttribute}', [\App\Http\Controllers\Admin\CategoryAttributeController::class, 'update'])->name('update');
            Route::delete('/{categoryAttribute}', [\App\Http\Controllers\Admin\CategoryAttributeController::class, 'destroy'])->name('destroy');
        });

        // Marketplace Category Mappings
        Route::prefix('marketplace-category-mappings')->name('marketplace-category-mappings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\MarketplaceCategoryMappingController::class, 'index'])->name('index');
            Route::post('/update', [\App\Http\Controllers\Admin\MarketplaceCategoryMappingController::class, 'update'])->name('update');
            Route::post('/bulk-update', [\App\Http\Controllers\Admin\MarketplaceCategoryMappingController::class, 'bulkUpdate'])->name('bulk-update');
            Route::get('/categories', [\App\Http\Controllers\Admin\MarketplaceCategoryMappingController::class, 'getCategories'])->name('categories');
            Route::post('/import-attributes', [\App\Http\Controllers\Admin\MarketplaceCategoryMappingController::class, 'importAttributes'])->name('import-attributes');
        });
        
        // Marketplaces
        Route::resource('marketplaces', \App\Http\Controllers\Admin\MarketplaceController::class);
        Route::get('/marketplaces/{marketplace}/settings', [\App\Http\Controllers\Admin\MarketplaceController::class, 'settings'])->name('marketplaces.settings');
        Route::post('/marketplaces/{marketplace}/settings', [\App\Http\Controllers\Admin\MarketplaceController::class, 'updateSettings'])->name('marketplaces.settings.update');
        
        // Feed Sources
        Route::resource('feed-sources', \App\Http\Controllers\Admin\FeedSourceController::class);
        
        // Feed Runs
        Route::get('/feed-runs', [\App\Http\Controllers\Admin\FeedRunController::class, 'index'])->name('feed-runs.index');
        Route::get('/feed-runs/{feedRun}', [\App\Http\Controllers\Admin\FeedRunController::class, 'show'])->name('feed-runs.show');
        Route::post('/feed-runs/trigger', [\App\Http\Controllers\Admin\FeedRunController::class, 'triggerDownload'])->name('feed-runs.trigger');
        Route::post('/feed-runs/{feedRun}/parse', [\App\Http\Controllers\Admin\FeedRunController::class, 'parseFeedRun'])->name('feed-runs.parse');
        Route::post('/feed-runs/{feedRun}/dispatch', [\App\Http\Controllers\Admin\FeedRunController::class, 'dispatchImports'])->name('feed-runs.dispatch');
        Route::post('/feed-runs/dispatch-all', [\App\Http\Controllers\Admin\FeedRunController::class, 'dispatchImports'])->name('feed-runs.dispatch-all');
        
        // XML Category Mappings
        Route::prefix('xml')->name('xml.')->group(function () {
            Route::get('/category-mappings', [\App\Http\Controllers\Admin\XmlCategoryMappingController::class, 'index'])->name('category-mappings.index');
            Route::get('/category-mappings/data', [\App\Http\Controllers\Admin\XmlCategoryMappingController::class, 'getData'])->name('category-mappings.data');
            Route::get('/category-mappings/categories', [\App\Http\Controllers\Admin\XmlCategoryMappingController::class, 'getCategories'])->name('category-mappings.categories');
            Route::post('/category-mappings/bulk', [\App\Http\Controllers\Admin\XmlCategoryMappingController::class, 'bulkUpdate'])->name('category-mappings.bulk');
        });
        
        // XML Products
        Route::get('/xml-products', [\App\Http\Controllers\Admin\XmlProductController::class, 'index'])->name('xml-products.index');
        Route::get('/xml-products/{id}', [\App\Http\Controllers\Admin\XmlProductController::class, 'show'])->name('xml-products.show');
        
        // XML Attribute Analysis
        Route::prefix('xml-attribute-analysis')->name('xml-attribute-analysis.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\XmlAttributeAnalysisController::class, 'index'])->name('index');
            Route::post('/mapping', [\App\Http\Controllers\Admin\XmlAttributeAnalysisController::class, 'storeMapping'])->name('mapping.store');
            Route::delete('/mapping/{mapping}', [\App\Http\Controllers\Admin\XmlAttributeAnalysisController::class, 'deleteMapping'])->name('mapping.delete');
        });
        
        // Brand Mappings
        Route::get('/brand-mappings', [\App\Http\Controllers\Admin\BrandMappingController::class, 'index'])->name('brand-mappings.index');
        Route::get('/brand-mappings/{brand}/marketplace/{marketplace}/search-results', [\App\Http\Controllers\Admin\BrandMappingController::class, 'showSearchResults'])->name('brand-mappings.search-results');
        Route::post('/brand-mappings/{brand}/marketplace/{marketplace}', [\App\Http\Controllers\Admin\BrandMappingController::class, 'store'])->name('brand-mappings.store');
        Route::delete('/brand-mappings/{brand}/marketplace/{marketplace}', [\App\Http\Controllers\Admin\BrandMappingController::class, 'destroy'])->name('brand-mappings.destroy');
        Route::post('/brand-mappings/auto-map', [\App\Http\Controllers\Admin\BrandMappingController::class, 'autoMap'])->name('brand-mappings.auto-map');
        
        // Brand Origins
        Route::prefix('brand-origins')->name('brand-origins.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BrandOriginController::class, 'index'])->name('index');
            Route::put('/{brand}', [\App\Http\Controllers\Admin\BrandOriginController::class, 'update'])->name('update');
            Route::post('/bulk-update', [\App\Http\Controllers\Admin\BrandOriginController::class, 'bulkUpdate'])->name('bulk-update');
            Route::post('/marketplace-mapping', [\App\Http\Controllers\Admin\BrandOriginController::class, 'updateMarketplaceMapping'])->name('marketplace-mapping.update');
            Route::delete('/marketplace-mapping', [\App\Http\Controllers\Admin\BrandOriginController::class, 'deleteMarketplaceMapping'])->name('marketplace-mapping.delete');
        });
    });
});
