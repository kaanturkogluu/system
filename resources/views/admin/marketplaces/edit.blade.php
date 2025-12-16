@extends('admin.layouts.app')

@section('title', 'Pazaryeri Düzenle')
@section('page-title', 'Pazaryeri Düzenle')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.marketplaces.update', $marketplace) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Pazaryeri Adı <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name', $marketplace->name) }}"
                    required
                    maxlength="50"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                    placeholder="Örn: Trendyol, Hepsiburada"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Slug -->
            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Slug <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="slug" 
                    name="slug" 
                    value="{{ old('slug', $marketplace->slug) }}"
                    required
                    maxlength="50"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('slug') border-red-500 @enderror"
                    placeholder="trendyol, hepsiburada"
                >
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    URL için kullanılacak benzersiz tanımlayıcı (küçük harf, tire ile ayrılmış)
                </p>
                @error('slug')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            Bu pazaryerine ait <strong>{{ \App\Models\MarketplaceCategory::where('marketplace_id', $marketplace->id)->count() }}</strong> kategori bulunmaktadır.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a 
                    href="{{ route('admin.marketplaces.index') }}"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200"
                >
                    İptal
                </a>
                <button 
                    type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200"
                >
                    Güncelle
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

