@extends('admin.layouts.app')

@section('title', 'Kategoriler')
@section('page-title', 'Kategoriler')

@section('content')
<div class="space-y-6">
    <!-- Filters and Search -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Ara
                    </label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Kategori adı veya slug..."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                </div>

                <!-- Level Filter -->
                <div>
                    <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Seviye
                    </label>
                    <select 
                        id="level" 
                        name="level"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Tümü</option>
                        <option value="0" {{ request('level') == '0' ? 'selected' : '' }}>0 - Ana Kategori</option>
                        <option value="1" {{ request('level') == '1' ? 'selected' : '' }}>1</option>
                        <option value="2" {{ request('level') == '2' ? 'selected' : '' }}>2</option>
                        <option value="3" {{ request('level') == '3' ? 'selected' : '' }}>3</option>
                    </select>
                </div>

                <!-- Parent Filter -->
                <div>
                    <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Üst Kategori
                    </label>
                    <select 
                        id="parent_id" 
                        name="parent_id"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Tümü</option>
                        <option value="null" {{ request('parent_id') == 'null' ? 'selected' : '' }}>Ana Kategoriler</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <button 
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200"
                >
                    Filtrele
                </button>
                @if(request()->hasAny(['search', 'level', 'parent_id']))
                    <a 
                        href="{{ route('admin.categories.index') }}"
                        class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                    >
                        Filtreleri Temizle
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Kategori</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $categories->total() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Ana Kategoriler</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ \App\Models\Category::whereNull('parent_id')->count() }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Yaprak Kategoriler</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ \App\Models\Category::where('is_leaf', true)->count() }}</p>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Kategori Adı
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Slug
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Seviye
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Üst Kategori
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Durum
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            İşlemler
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $category->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-2 h-2 rounded-full mr-3" style="margin-left: {{ $category->level * 20 }}px; background-color: {{ ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'][$category->level % 4] ?? '#6B7280' }};"></div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $category->name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $category->slug }}</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300">
                                    {{ $category->level }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if($category->parent)
                                    <a href="{{ route('admin.categories.index', ['parent_id' => $category->parent_id]) }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ $category->parent->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($category->is_leaf)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300">
                                        Yaprak
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                        Alt Kategori Var
                                    </span>
                                @endif
                                @if($category->is_active)
                                    <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300">
                                        Aktif
                                    </span>
                                @else
                                    <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300">
                                        Pasif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.categories.index', ['parent_id' => $category->id]) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">
                                    Alt Kategoriler
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Kategori bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($categories->hasPages())
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

