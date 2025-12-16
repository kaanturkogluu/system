@extends('admin.layouts.app')

@section('title', 'Yeni Pazaryeri')
@section('page-title', 'Yeni Pazaryeri Ekle')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.marketplaces.store') }}" class="space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Pazaryeri Adı <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') }}"
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
                    value="{{ old('slug') }}"
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
                    Kaydet
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

