@extends('admin.layouts.app')

@section('title', 'Yeni Feed Kaynağı')
@section('page-title', 'Yeni Feed Kaynağı Ekle')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.feed-sources.store') }}" class="space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Feed Adı <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name') }}"
                    required
                    maxlength="100"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                    placeholder="Örn: Trendyol XML Feed"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- URL -->
            <div>
                <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Feed URL <span class="text-red-500">*</span>
                </label>
                <input 
                    type="url" 
                    id="url" 
                    name="url" 
                    value="{{ old('url') }}"
                    required
                    maxlength="500"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('url') border-red-500 @enderror"
                    placeholder="https://example.com/feed.xml"
                >
                @error('url')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Type -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Feed Tipi <span class="text-red-500">*</span>
                </label>
                <select 
                    id="type" 
                    name="type"
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('type') border-red-500 @enderror"
                >
                    <option value="xml" {{ old('type') == 'xml' ? 'selected' : '' }}>XML</option>
                    <option value="json" {{ old('type') == 'json' ? 'selected' : '' }}>JSON</option>
                    <option value="api" {{ old('type') == 'api' ? 'selected' : '' }}>API</option>
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Schedule -->
            <div>
                <label for="schedule" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Zamanlama (Cron)
                </label>
                <input 
                    type="text" 
                    id="schedule" 
                    name="schedule" 
                    value="{{ old('schedule') }}"
                    maxlength="50"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('schedule') border-red-500 @enderror"
                    placeholder="0 2 * * * (Her gün saat 02:00)"
                >
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Otomatik indirme için cron formatı (opsiyonel)
                </p>
                @error('schedule')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Is Active -->
            <div class="flex items-center">
                <input 
                    id="is_active" 
                    name="is_active" 
                    type="checkbox" 
                    value="1"
                    {{ old('is_active', true) ? 'checked' : '' }}
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                >
                <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Aktif
                </label>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a 
                    href="{{ route('admin.feed-sources.index') }}"
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

