@extends('admin.layouts.app')

@section('title', 'Feed Kaynağı Düzenle')
@section('page-title', 'Feed Kaynağı Düzenle')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.feed-sources.update', $feedSource) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Feed Adı <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name', $feedSource->name) }}"
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
                    value="{{ old('url', $feedSource->url) }}"
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
                    <option value="xml" {{ old('type', $feedSource->type) == 'xml' ? 'selected' : '' }}>XML</option>
                    <option value="json" {{ old('type', $feedSource->type) == 'json' ? 'selected' : '' }}>JSON</option>
                    <option value="api" {{ old('type', $feedSource->type) == 'api' ? 'selected' : '' }}>API</option>
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
                    value="{{ old('schedule', $feedSource->schedule) }}"
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
                    {{ old('is_active', $feedSource->is_active) ? 'checked' : '' }}
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                >
                <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Aktif
                </label>
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
                            Bu feed kaynağına ait <strong>{{ $feedSource->feedRuns()->count() }}</strong> çalıştırma kaydı bulunmaktadır.
                        </p>
                    </div>
                </div>
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
                    Güncelle
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

