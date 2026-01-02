@extends('admin.layouts.app')

@section('title', 'XML Attribute Analysis')
@section('page-title', 'XML Attribute Analysis')

@section('content')
<div class="space-y-6">
    <!-- Analysis Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Analiz Yap</h3>
        <form method="GET" action="{{ route('admin.xml-attribute-analysis.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Analiz Edilecek Ürün Sayısı</label>
                    <input 
                        type="number" 
                        name="limit" 
                        value="{{ $limit }}"
                        min="1" 
                        max="10000"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Kategori Seç (Opsiyonel)</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="category-search-input"
                            name="category_search" 
                            value="{{ $categorySearch }}"
                            placeholder="Kategori ara veya listeden seç..."
                            autocomplete="off"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            onfocus="showCategoryDropdown()"
                            onblur="setTimeout(() => hideCategoryDropdown(), 200)"
                            oninput="filterCategories(this.value)"
                        >
                        <!-- Category Dropdown -->
                        <div id="category-dropdown" class="hidden absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            @foreach($mappedCategories as $category)
                                <div 
                                    class="category-option px-4 py-2 hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer text-gray-900 dark:text-white"
                                    onclick="selectCategory('{{ $category['name'] }}')"
                                >
                                    {{ $category['name'] }}
                                </div>
                            @endforeach
                            @if(count($mappedCategories) === 0)
                                <div class="px-4 py-2 text-gray-500 dark:text-gray-400 text-sm">
                                    Eşleştirilmiş kategori bulunamadı
                                </div>
                            @endif
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Boş bırakılırsa tüm eşleştirilmiş kategoriler analiz edilir. 
                        <span class="text-blue-600 dark:text-blue-400 font-medium">Not: Sadece XML kategori mapping'te eşleştirilmiş kategoriler işlenir.</span>
                    </p>
                </div>
            </div>
            <div class="flex items-end">
                <button 
                    type="submit" 
                    name="analyze" 
                    value="1"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200"
                >
                    Analiz Yap
                </button>
                @if(request('analyze') || request('category_search'))
                    <a 
                        href="{{ route('admin.xml-attribute-analysis.index') }}"
                        class="ml-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold py-2 px-6 rounded-lg transition duration-200"
                    >
                        Temizle
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/20 border border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/20 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    @if($summary)
        <!-- Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $summary['total'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm font-medium text-green-600 dark:text-green-400">Eşleşti (HIGH)</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">{{ $summary['matched'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">İnceleme Gerekli</div>
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-2">{{ $summary['needs_review'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm font-medium text-red-600 dark:text-red-400">Eşleşmedi</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-2">{{ $summary['unmapped'] }}</div>
            </div>
        </div>
    @endif

    @if($groupedByCategory)
        <!-- Grouped by Category -->
        @foreach($groupedByCategory as $categoryGroup)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        {{ $categoryGroup['category_name'] }}
                        <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                            ({{ count($categoryGroup['attributes']) }} attribute)
                        </span>
                    </div>
                    @if(isset($categoryGroup['category_id']) && $categoryGroup['category_id'] && isset($requiredAttributesByCategory[$categoryGroup['category_id']]) && count($requiredAttributesByCategory[$categoryGroup['category_id']]) > 0)
                        <button 
                            onclick="showRequiredAttributes({{ $categoryGroup['category_id'] }}, '{{ $categoryGroup['category_name'] }}')"
                            class="ml-4 px-3 py-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition duration-200 flex items-center"
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Gerekli Özellikler ({{ count($requiredAttributesByCategory[$categoryGroup['category_id']]) }})
                        </button>
                    @endif
                </h3>
                
                <!-- Group by confidence -->
                @php
                    $matched = array_filter($categoryGroup['attributes'], fn($a) => ($a['confidence'] === 'HIGH' || $a['confidence'] === 'MAPPED') && $a['matched_attribute_id']);
                    $needsReview = array_filter($categoryGroup['attributes'], fn($a) => $a['confidence'] === 'MEDIUM' || ($a['confidence'] === 'LOW' && $a['matched_attribute_id']));
                    $unmapped = array_filter($categoryGroup['attributes'], fn($a) => $a['confidence'] === 'LOW' && !$a['matched_attribute_id']);
                @endphp

                @if(!empty($matched))
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold mb-2 text-green-600 dark:text-green-400">✓ Eşleşti</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">XML Key</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Suggested Code</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Matched Attribute</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Products</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Examples</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($matched as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-white">{{ $item['xml_attribute_key'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $item['suggested_global_code'] }}</td>
                                            <td class="px-4 py-2 text-sm text-green-600 dark:text-green-400 font-medium">
                                                {{ $item['matched_attribute_code'] }}
                                                @if($item['confidence'] === 'MAPPED')
                                                    <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                                        Eşleştirilmiş
                                                    </span>
                                                @else
                                                    <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                                        Otomatik
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $item['product_count'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                                {{ implode(', ', array_slice($item['example_values'], 0, 3)) }}
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <button 
                                                    onclick="openMappingModalWithCategory('{{ $item['xml_attribute_key'] }}', {{ $item['matched_attribute_id'] ?? 'null' }}, {{ $categoryGroup['category_id'] ?? 'null' }})"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    Manuel Eşleştir
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(!empty($needsReview))
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold mb-2 text-yellow-600 dark:text-yellow-400">⚠ İnceleme Gerekli</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">XML Key</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Suggested Code</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Matched Attribute</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Confidence</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Products</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Examples</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($needsReview as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-white">{{ $item['xml_attribute_key'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $item['suggested_global_code'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $item['matched_attribute_code'] ?? '—' }}</td>
                                            <td class="px-4 py-2 text-sm">
                                                <span class="px-2 py-1 rounded text-xs font-medium 
                                                    {{ $item['confidence'] === 'MEDIUM' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                                    {{ $item['confidence'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $item['product_count'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                                {{ implode(', ', array_slice($item['example_values'], 0, 3)) }}
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <button 
                                                    onclick="openMappingModalWithCategory('{{ $item['xml_attribute_key'] }}', {{ $item['matched_attribute_id'] ?? 'null' }}, {{ $categoryGroup['category_id'] ?? 'null' }})"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    Eşleştir
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if(!empty($unmapped))
                    <div>
                        <h4 class="text-sm font-semibold mb-2 text-red-600 dark:text-red-400">✗ Eşleşmedi</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">XML Key</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Suggested Code</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Products</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Examples</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($unmapped as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm font-mono text-gray-900 dark:text-white">{{ $item['xml_attribute_key'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ $item['suggested_global_code'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $item['product_count'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                                                {{ implode(', ', array_slice($item['example_values'], 0, 3)) }}
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                <button 
                                                    onclick="openMappingModalWithCategory('{{ $item['xml_attribute_key'] }}', null, {{ $categoryGroup['category_id'] ?? 'null' }})"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    Manuel Eşleştir
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    @elseif($analysis)
        <!-- Fallback: Show without grouping if no categories -->
        <!-- Matched (HIGH Confidence) -->
        @php
            $matched = array_filter($analysis, fn($a) => ($a['confidence'] === 'HIGH' || $a['confidence'] === 'MAPPED') && $a['matched_attribute_id']);
        @endphp
        @if(!empty($matched))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-green-600 dark:text-green-400">✓ Eşleşti</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">XML Key</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Suggested Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Matched Attribute</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Products</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Examples</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($matched as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ $item['xml_attribute_key'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $item['suggested_global_code'] }}</td>
                                    <td class="px-4 py-3 text-sm text-green-600 dark:text-green-400 font-medium">
                                        {{ $item['matched_attribute_code'] }}
                                        @if($item['confidence'] === 'MAPPED')
                                            <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                                Eşleştirilmiş
                                            </span>
                                        @else
                                            <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                                Otomatik
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item['product_count'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ implode(', ', array_slice($item['example_values'], 0, 3)) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <button 
                                            onclick="openMappingModalWithCategory('{{ $item['xml_attribute_key'] }}', {{ $item['matched_attribute_id'] ?? 'null' }}, {{ !empty($item['categories']) ? $item['categories'][0] : 'null' }})"
                                            class="text-blue-600 dark:text-blue-400 hover:underline"
                                        >
                                            Manuel Eşleştir
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Needs Review -->
        @php
            $needsReview = array_filter($analysis, fn($a) => $a['confidence'] === 'MEDIUM' || ($a['confidence'] === 'LOW' && $a['matched_attribute_id']));
        @endphp
        @if(!empty($needsReview))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-yellow-600 dark:text-yellow-400">⚠ İnceleme Gerekli</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">XML Key</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Suggested Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Matched Attribute</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Confidence</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Products</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Examples</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($needsReview as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ $item['xml_attribute_key'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $item['suggested_global_code'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $item['matched_attribute_code'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-medium 
                                            {{ $item['confidence'] === 'MEDIUM' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $item['confidence'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item['product_count'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ implode(', ', array_slice($item['example_values'], 0, 3)) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <button 
                                            onclick="openMappingModalWithCategory('{{ $item['xml_attribute_key'] }}', {{ $item['matched_attribute_id'] ?? 'null' }}, {{ !empty($item['categories']) ? $item['categories'][0] : 'null' }})"
                                            class="text-blue-600 dark:text-blue-400 hover:underline"
                                        >
                                            Eşleştir
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Unmapped -->
        @php
            $unmapped = array_filter($analysis, fn($a) => $a['confidence'] === 'LOW' && !$a['matched_attribute_id']);
        @endphp
        @if(!empty($unmapped))
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4 text-red-600 dark:text-red-400">✗ Eşleşmedi</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">XML Key</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Suggested Code</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Products</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Examples</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($unmapped as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ $item['xml_attribute_key'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $item['suggested_global_code'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $item['product_count'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ implode(', ', array_slice($item['example_values'], 0, 3)) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <button 
                                            onclick="openMappingModalWithCategory('{{ $item['xml_attribute_key'] }}', null, {{ !empty($item['categories']) ? $item['categories'][0] : 'null' }})"
                                            class="text-blue-600 dark:text-blue-400 hover:underline"
                                        >
                                            Manuel Eşleştir
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    <!-- Existing Mappings -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Mevcut Eşleştirmeler</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">XML Key</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Attribute</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Durum</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($existingMappings as $mapping)
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ $mapping->source_attribute_key }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $mapping->attribute->name ?? '—' }} 
                                <span class="text-gray-500 dark:text-gray-400">({{ $mapping->attribute->code ?? '—' }})</span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 rounded text-xs font-medium 
                                    {{ $mapping->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $mapping->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <form method="POST" action="{{ route('admin.xml-attribute-analysis.mapping.delete', $mapping) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Bu eşleştirmeyi silmek istediğinizden emin misiniz?')" class="text-red-600 dark:text-red-400 hover:underline">
                                        Sil
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                Henüz eşleştirme yok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Mapping Modal -->
<div id="mapping-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Manuel Eşleştirme</h3>
        <form method="POST" action="{{ route('admin.xml-attribute-analysis.mapping.store') }}">
            @csrf
            <input type="hidden" name="source_attribute_key" id="modal-xml-key">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">XML Attribute Key</label>
                    <p id="modal-xml-key-display" class="text-gray-900 dark:text-white font-mono text-sm"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Global Attribute</label>
                    <select 
                        name="attribute_id" 
                        id="modal-attribute-id"
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Seçin...</option>
                        @foreach($allAttributes as $attribute)
                            <option value="{{ $attribute->id }}" data-categories="{{ $attribute->categories()->pluck('categories.id')->implode(',') }}">
                                {{ $attribute->name }} ({{ $attribute->code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" id="modal-category-info"></p>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        Kaydet
                    </button>
                    <button type="button" onclick="closeMappingModal()" class="flex-1 bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-lg text-gray-800 dark:text-gray-200">
                        İptal
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Store attributes by category data
const attributesByCategory = @json($attributesByCategory ?? []);

function openMappingModal(xmlKey, attributeId) {
    openMappingModalWithCategory(xmlKey, attributeId, null);
}

function openMappingModalWithCategory(xmlKey, attributeId, categoryId) {
    const modalXmlKey = document.getElementById('modal-xml-key');
    const modalXmlKeyDisplay = document.getElementById('modal-xml-key-display');
    const modalAttributeId = document.getElementById('modal-attribute-id');
    const mappingModal = document.getElementById('mapping-modal');
    
    // Null checks
    if (!modalXmlKey || !modalXmlKeyDisplay || !modalAttributeId || !mappingModal) {
        console.error('Modal elements not found');
        return;
    }
    
    modalXmlKey.value = xmlKey;
    modalXmlKeyDisplay.textContent = xmlKey;
    // Handle attributeId - convert 'null' string to empty string
    const attributeIdValue = (attributeId && attributeId !== 'null' && attributeId !== null) ? attributeId : '';
    modalAttributeId.value = attributeIdValue;
    
    // Filter attributes by category
    const select = modalAttributeId;
    const options = select.querySelectorAll('option');
    const categoryInfo = document.getElementById('modal-category-info');
    
    // Always filter by category if categoryId is provided
    if (categoryId && categoryId !== 'null' && categoryId !== null) {
        // Try to get attributes from attributesByCategory
        if (attributesByCategory[categoryId] && attributesByCategory[categoryId].length > 0) {
            // Show only attributes for this category
            const categoryAttributeIds = attributesByCategory[categoryId].map(a => a.id.toString());
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block'; // Keep "Seçin..." option
                } else {
                    if (categoryAttributeIds.includes(option.value)) {
                        option.style.display = 'block';
                    } else {
                        option.style.display = 'none';
                    }
                }
            });
            
            if (categoryInfo) {
                categoryInfo.textContent = `Sadece bu kategoriye ait ${categoryAttributeIds.length} özellik gösteriliyor.`;
                categoryInfo.classList.remove('hidden');
            }
        } else {
            // Category ID exists but no attributes found - filter by data-categories attribute
            const categoryIdStr = categoryId.toString();
            let visibleCount = 0;
            
            options.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block'; // Keep "Seçin..." option
                } else {
                    const optionCategories = option.getAttribute('data-categories');
                    if (optionCategories && optionCategories.split(',').includes(categoryIdStr)) {
                        option.style.display = 'block';
                        visibleCount++;
                    } else {
                        option.style.display = 'none';
                    }
                }
            });
            
            if (categoryInfo) {
                if (visibleCount > 0) {
                    categoryInfo.textContent = `Sadece bu kategoriye ait ${visibleCount} özellik gösteriliyor.`;
                } else {
                    categoryInfo.textContent = `Bu kategoriye ait özellik bulunamadı.`;
                }
                categoryInfo.classList.remove('hidden');
            }
        }
    } else {
        // No category specified - show message that category is required
        options.forEach(option => {
            option.style.display = 'none';
        });
        // Show "Seçin..." option
        const firstOption = select.querySelector('option[value=""]');
        if (firstOption) {
            firstOption.style.display = 'block';
        }
        
        if (categoryInfo) {
            categoryInfo.textContent = 'Kategori bilgisi bulunamadı. Lütfen kategoriye göre eşleştirme yapın.';
            categoryInfo.classList.remove('hidden');
            categoryInfo.classList.add('text-yellow-600', 'dark:text-yellow-400');
        }
    }
    
    mappingModal.classList.remove('hidden');
}

function closeMappingModal() {
    document.getElementById('mapping-modal').classList.add('hidden');
}

document.getElementById('mapping-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeMappingModal();
    }
});

// Category Dropdown Functions
function showCategoryDropdown() {
    const dropdown = document.getElementById('category-dropdown');
    if (dropdown) {
        dropdown.classList.remove('hidden');
        filterCategories(document.getElementById('category-search-input').value);
    }
}

function hideCategoryDropdown() {
    const dropdown = document.getElementById('category-dropdown');
    if (dropdown) {
        dropdown.classList.add('hidden');
    }
}

function filterCategories(searchTerm) {
    const dropdown = document.getElementById('category-dropdown');
    const options = dropdown?.querySelectorAll('.category-option');
    
    if (!options) return;
    
    const term = searchTerm.toLowerCase().trim();
    
    options.forEach(option => {
        const text = option.textContent.toLowerCase();
        if (text.includes(term)) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
}

function selectCategory(categoryName) {
    const input = document.getElementById('category-search-input');
    if (input) {
        input.value = categoryName;
        hideCategoryDropdown();
        // Optionally auto-submit or just let user click analyze
    }
}

// Required Attributes Modal
const requiredAttributesByCategory = @json($requiredAttributesByCategory ?? []);

function showRequiredAttributes(categoryId, categoryName) {
    const modal = document.getElementById('required-attributes-modal');
    const modalTitle = document.getElementById('required-attributes-title');
    const modalList = document.getElementById('required-attributes-list');
    
    if (!modal || !modalTitle || !modalList) {
        console.error('Required attributes modal elements not found');
        return;
    }
    
    modalTitle.textContent = `${categoryName} - Trendyol Gerekli Özellikler`;
    
    const attributes = requiredAttributesByCategory[categoryId] || [];
    
    if (attributes.length === 0) {
        modalList.innerHTML = '<div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Bu kategori için zorunlu özellik bulunamadı.</div>';
    } else {
        modalList.innerHTML = attributes.map(attr => `
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">${attr.name}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 font-mono">${attr.code}</div>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300 rounded">
                        Zorunlu
                    </span>
                </div>
            </div>
        `).join('');
    }
    
    modal.classList.remove('hidden');
}

function closeRequiredAttributesModal() {
    document.getElementById('required-attributes-modal').classList.add('hidden');
}

document.getElementById('required-attributes-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRequiredAttributesModal();
    }
});
</script>

<!-- Required Attributes Modal -->
<div id="required-attributes-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <h3 id="required-attributes-title" class="text-lg font-semibold text-gray-900 dark:text-white">Gerekli Özellikler</h3>
            <button 
                onclick="closeRequiredAttributesModal()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="required-attributes-list" class="flex-1 overflow-y-auto">
            <!-- Attributes will be populated here -->
        </div>
    </div>
</div>

@endsection

