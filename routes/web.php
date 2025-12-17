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
        
        // Marketplaces
        Route::resource('marketplaces', \App\Http\Controllers\Admin\MarketplaceController::class);
        
        // Feed Sources
        Route::resource('feed-sources', \App\Http\Controllers\Admin\FeedSourceController::class);
        
        // Feed Runs
        Route::get('/feed-runs', [\App\Http\Controllers\Admin\FeedRunController::class, 'index'])->name('feed-runs.index');
        Route::get('/feed-runs/{feedRun}', [\App\Http\Controllers\Admin\FeedRunController::class, 'show'])->name('feed-runs.show');
        Route::post('/feed-runs/trigger', [\App\Http\Controllers\Admin\FeedRunController::class, 'triggerDownload'])->name('feed-runs.trigger');
        
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
    });
});
