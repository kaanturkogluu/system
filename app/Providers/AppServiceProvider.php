<?php

namespace App\Providers;

use App\Models\CategoryMapping;
use App\Observers\CategoryMappingObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register CategoryMapping observer for automatic product category updates
        CategoryMapping::observe(CategoryMappingObserver::class);
    }
}
