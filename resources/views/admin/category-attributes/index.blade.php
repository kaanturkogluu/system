@extends('admin.layouts.app')

@section('title', 'Kategori Özellikleri')
@section('page-title', 'Kategori Özellikleri')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Category Tree -->
    <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold">Kategoriler</h3>
        </div>
        
        <!-- Search Input -->
        <div class="mb-4">
            <input 
                type="text" 
                id="category-search" 
                placeholder="Kategori ara..." 
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                value="{{ $searchQuery ?? '' }}"
            >
        </div>
        
        <div class="space-y-1 max-h-96 overflow-y-auto" id="category-tree">
            @php
                function renderCategoryTree($category, $level = 0) {
                    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                    $hasChildren = $category->children->count() > 0;
                    $isSelected = request('category_id') == $category->id;
                    $selectedClass = $isSelected ? 'bg-blue-100 dark:bg-blue-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700';
                    
                    $html = '<a href="' . route('admin.category-attributes.index', ['category_id' => $category->id]) . '" ';
                    $html .= 'class="block px-3 py-2 rounded ' . $selectedClass . '" ';
                    $html .= 'style="margin-left: ' . ($level * 16) . 'px;">';
                    $html .= ($hasChildren ? '📁 ' : '📄 ') . htmlspecialchars($category->name);
                    $html .= '</a>';
                    
                    if ($hasChildren) {
                        foreach ($category->children as $child) {
                            $html .= renderCategoryTree($child, $level + 1);
                        }
                    }
                    
                    return $html;
                }
            @endphp
            @foreach($categories as $category)
                {!! renderCategoryTree($category, 0) !!}
            @endforeach
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('category-search');
            const categoryTree = document.getElementById('category-tree');
            
            if (!searchInput || !categoryTree) return;
            
            // Store original display states
            const allCategoryLinks = Array.from(categoryTree.querySelectorAll('a'));
            
            searchInput.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                
                if (searchTerm === '') {
                    // Show all categories
                    allCategoryLinks.forEach(link => {
                        link.style.display = 'block';
                    });
                    return;
                }
                
                // Filter categories
                allCategoryLinks.forEach(link => {
                    const categoryName = link.textContent.toLowerCase();
                    const matches = categoryName.includes(searchTerm);
                    
                    if (matches) {
                        link.style.display = 'block';
                        // Show all parent categories
                        let parent = link.parentElement;
                        while (parent && parent !== categoryTree) {
                            const parentLink = parent.querySelector('a');
                            if (parentLink) {
                                parentLink.style.display = 'block';
                            }
                            parent = parent.parentElement;
                        }
                    } else {
                        link.style.display = 'none';
                    }
                });
            });
        });
    </script>

    <!-- Attribute List -->
    <div class="lg:col-span-3">
        @if($category)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">Kategori: {{ $category->name }}</h3>
                        <p class="text-sm text-gray-500 mt-1">Toplam {{ $categoryAttributes->count() }} özellik</p>
                    </div>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.marketplace-category-mappings.import-attributes') }}" class="inline" onsubmit="return confirm('Trendyol\'dan bu kategorinin özelliklerini içe aktarmak istediğinize emin misiniz?')">
                            @csrf
                            <input type="hidden" name="category_id" value="{{ $category->id }}">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                                Trendyol'dan İçe Aktar
                            </button>
                        </form>
                        <button onclick="document.getElementById('add-attribute-modal').classList.remove('hidden')" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            Özellik Ekle
                        </button>
                    </div>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 dark:bg-green-900/20 border border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 dark:bg-red-900/20 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-400 text-yellow-700 dark:text-yellow-300 px-4 py-3 rounded mb-4">
                        {{ session('warning') }}
                    </div>
                @endif

                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Özellik</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Kod</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tip</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Değerler</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Zorunlu</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($categoryAttributes as $ca)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $ca->attribute->name }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $ca->attribute->code }}</code>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-purple-100 dark:bg-purple-900/20 text-purple-800 dark:text-purple-300">
                                        {{ ucfirst($ca->attribute->data_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    @if($ca->attribute->data_type === 'enum')
                                        {{ $ca->attribute->values()->count() }} değer
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.category-attributes.update', $ca) }}" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="is_required" value="{{ $ca->is_required ? '0' : '1' }}">
                                        <button type="submit" class="px-2 py-1 text-xs rounded {{ $ca->is_required ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $ca->is_required ? 'Evet' : 'Hayır' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ route('admin.attributes.show', $ca->attribute) }}" class="text-blue-600 dark:text-blue-400 hover:underline mr-3">Detay</a>
                                    <form method="POST" action="{{ route('admin.category-attributes.destroy', $ca) }}" class="inline" onsubmit="return confirm('Bu özelliği kategoriden kaldırmak istediğinize emin misiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-800">Kaldır</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="text-sm">Bu kategoriye henüz özellik atanmamış</p>
                                        <p class="text-xs text-gray-400 mt-2">Trendyol'dan içe aktarabilir veya manuel olarak ekleyebilirsiniz</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center text-gray-500">
                Bir kategori seçin
            </div>
        @endif
    </div>
</div>

<!-- Add Attribute Modal -->
@if($category)
<div id="add-attribute-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold mb-4">Özellik Ekle</h3>
        <form method="POST" action="{{ route('admin.category-attributes.store', $category) }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Özellik</label>
                    <select name="attribute_id" required class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        @foreach($allAttributes as $attr)
                            <option value="{{ $attr->id }}">{{ $attr->name }} ({{ $attr->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_required" value="1" class="rounded">
                        <span class="ml-2">Zorunlu</span>
                    </label>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Ekle</button>
                    <button type="button" onclick="document.getElementById('add-attribute-modal').classList.add('hidden')" class="flex-1 bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-lg">İptal</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

