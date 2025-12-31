@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Kategori</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ \App\Models\Category::count() }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Marketplace</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ \App\Models\Marketplace::count() }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Marketplace Kategori</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ \App\Models\MarketplaceCategory::count() }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Kullanıcılar</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ \App\Models\User::count() }}</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Hoş Geldiniz!</h2>
        <p class="text-gray-600 dark:text-gray-400">
            Admin paneline hoş geldiniz, <strong class="text-gray-900 dark:text-white">{{ Auth::user()->name }}</strong>.
            Buradan sistem yönetimini gerçekleştirebilirsiniz.
        </p>
    </div>

    <!-- Pending Mappings Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Unmapped Categories -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Eşleştirilmemiş Kategoriler</h3>
                <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $unmappedCategories > 0 ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' }}">
                    {{ $unmappedCategories }}
                </span>
            </div>
            
            @if($unmappedCategories > 0)
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Toplam <strong>{{ $totalCategories }}</strong> kategoriden <strong>{{ $mappedCategories }}</strong> tanesi eşleştirilmiş, <strong>{{ $unmappedCategories }}</strong> tanesi eşleştirilmemiş.
                </p>
                
                @if($unmappedCategoriesList->count() > 0)
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($unmappedCategoriesList as $category)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $category->name }}</span>
                                <a href="{{ route('admin.marketplace-category-mappings.index') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                    Eşleştir →
                                </a>
                            </div>
                        @endforeach
                    </div>
                    @if($unmappedCategories > 10)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            ve {{ $unmappedCategories - 10 }} kategori daha...
                        </p>
                    @endif
                @endif
                
                <div class="mt-4">
                    <a href="{{ route('admin.marketplace-category-mappings.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                        Tümünü Görüntüle
                    </a>
                </div>
            @else
                <p class="text-sm text-green-600 dark:text-green-400">
                    ✅ Tüm kategoriler eşleştirilmiş!
                </p>
            @endif
        </div>

        <!-- Unmapped Brands -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Eşleştirilmemiş Markalar</h3>
                <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $unmappedBrands > 0 ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' }}">
                    {{ $unmappedBrands }}
                </span>
            </div>
            
            @if($unmappedBrands > 0)
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Toplam <strong>{{ $totalBrands }}</strong> markadan <strong>{{ $mappedBrands }}</strong> tanesi eşleştirilmiş, <strong>{{ $unmappedBrands }}</strong> tanesi eşleştirilmemiş.
                </p>
                
                @if($unmappedBrandsList->count() > 0)
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($unmappedBrandsList as $brand)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $brand->name }}</span>
                                <a href="{{ route('admin.brand-mappings.index') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                    Eşleştir →
                                </a>
                            </div>
                        @endforeach
                    </div>
                    @if($unmappedBrands > 10)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            ve {{ $unmappedBrands - 10 }} marka daha...
                        </p>
                    @endif
                @endif
                
                <div class="mt-4">
                    <a href="{{ route('admin.brand-mappings.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                        Tümünü Görüntüle
                    </a>
                </div>
            @else
                <p class="text-sm text-green-600 dark:text-green-400">
                    ✅ Tüm markalar eşleştirilmiş!
                </p>
            @endif
        </div>

        <!-- Categories Without Attributes -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Özellik Aktarılmamış Kategoriler</h3>
                <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $categoriesWithoutAttributes > 0 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' }}">
                    {{ $categoriesWithoutAttributes }}
                </span>
            </div>
            
            @if($categoriesWithoutAttributes > 0)
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Eşleştirilmiş kategorilerden <strong>{{ $categoriesWithoutAttributes }}</strong> tanesine henüz özellik aktarılmamış.
                </p>
                
                @if($categoriesWithoutAttributesList->count() > 0)
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($categoriesWithoutAttributesList as $category)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $category->name }}</span>
                                <a href="{{ route('admin.category-attributes.index', ['category_id' => $category->id]) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                    Özellik Ekle →
                                </a>
                            </div>
                        @endforeach
                    </div>
                    @if($categoriesWithoutAttributes > 10)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            ve {{ $categoriesWithoutAttributes - 10 }} kategori daha...
                        </p>
                    @endif
                @endif
                
                <div class="mt-4">
                    <a href="{{ route('admin.category-attributes.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                        Tümünü Görüntüle
                    </a>
                </div>
            @else
                <p class="text-sm text-green-600 dark:text-green-400">
                    ✅ Tüm eşleştirilmiş kategorilere özellik aktarılmış!
                </p>
            @endif
        </div>
    </div>
</div>
@endsection

