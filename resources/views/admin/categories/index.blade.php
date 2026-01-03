@extends('admin.layouts.app')

@section('title', 'Kategoriler')
@section('page-title', 'Kategoriler')

@section('content')
<div class="space-y-6">
    <!-- Import Button -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Trendyol Kategori İçe Aktarma</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Trendyol API'den kategorileri indirip sisteme aktarın</p>
            </div>
            <button 
                type="button"
                id="importTrendyolBtn"
                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200 flex items-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                İçeriye Aktar
            </button>
        </div>
    </div>

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
                            Komisyon Oranı (%)
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            KDV Oranı (%)
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <input 
                                    type="number" 
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value="{{ $category->commission_rate ?? '' }}"
                                    data-category-id="{{ $category->id }}"
                                    data-field="commission_rate"
                                    class="category-rate-input w-20 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                                    placeholder="20"
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <input 
                                    type="number" 
                                    step="1"
                                    min="0"
                                    max="100"
                                    value="{{ $category->vat_rate ?? '' }}"
                                    data-category-id="{{ $category->id }}"
                                    data-field="vat_rate"
                                    class="category-rate-input w-20 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                                    placeholder="20"
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.categories.index', ['parent_id' => $category->id]) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3">
                                    Alt Kategoriler
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
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

<!-- Import Modal -->
<div id="importModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Trendyol Kategorilerini İçe Aktar</h3>
                <button type="button" id="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div id="modalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Category rate update handler
document.addEventListener('DOMContentLoaded', function() {
    const rateInputs = document.querySelectorAll('.category-rate-input');
    
    rateInputs.forEach(input => {
        let timeout;
        
        input.addEventListener('blur', function() {
            clearTimeout(timeout);
            
            const categoryId = this.dataset.categoryId;
            const field = this.dataset.field;
            const value = this.value;
            
            // Update via AJAX
            fetch(`/admin/categories/${categoryId}/update-rate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    field: field,
                    value: value || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Visual feedback
                    this.classList.add('bg-green-50', 'dark:bg-green-900/20');
                    setTimeout(() => {
                        this.classList.remove('bg-green-50', 'dark:bg-green-900/20');
                    }, 1000);
                } else {
                    alert('Güncelleme başarısız: ' + (data.message || 'Bilinmeyen hata'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            });
        });
    });
});

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM yüklendi, script çalışıyor...');
        
        const importBtn = document.getElementById('importTrendyolBtn');
        
        if (!importBtn) {
            console.error('❌ İçeriye Aktar butonu bulunamadı!');
            return;
        }
        
        console.log('✅ İçeriye Aktar butonu bulundu');
        
        const modal = document.getElementById('importModal');
        const closeModal = document.getElementById('closeModal');
        const modalContent = document.getElementById('modalContent');

        if (!modal || !closeModal || !modalContent) {
            console.error('❌ Modal elementleri bulunamadı!');
            return;
        }

        // Download and show categories
        importBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('✅ İçeriye Aktar butonuna tıklandı');
            
            importBtn.disabled = true;
            importBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>İndiriliyor...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                alert('CSRF token bulunamadı!');
                importBtn.disabled = false;
                importBtn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>İçeriye Aktar';
                return;
            }

            const url = '{{ route("admin.categories.download-trendyol") }}';
            console.log('📤 İstek gönderiliyor:', url);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken.content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            })
            .then(response => {
                console.log('📥 Yanıt alındı:', response.status);
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'HTTP ' + response.status);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Yanıt verisi:', data);
                importBtn.disabled = false;
                importBtn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>İçeriye Aktar';

                if (data.success) {
                    showCategorySelection(data.main_categories);
                } else {
                    alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
                }
            })
            .catch(error => {
                console.error('❌ Hata:', error);
                importBtn.disabled = false;
                importBtn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>İçeriye Aktar';
                alert('Hata oluştu: ' + error.message);
            });
        });

        // Show category selection
        function showCategorySelection(categories) {
            let html = `
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    İçe aktarmak istediğiniz ana kategorileri seçin. Seçilen kategoriler ve alt kategorileri otomatik olarak sisteme aktarılacak ve Trendyol ile eşleştirilecektir.
                </p>
                <div class="max-h-96 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                    <div class="space-y-2">
            `;

            categories.forEach(cat => {
                html += `
                    <label class="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer">
                        <input type="checkbox" name="category_ids[]" value="${cat.id}" class="mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-900 dark:text-white">${cat.name} (ID: ${cat.id})</span>
                    </label>
                `;
            });

            html += `
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" id="cancelImport" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                        İptal
                    </button>
                    <button type="button" id="confirmImport" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        İçe Aktar
                    </button>
                </div>
            `;

            modalContent.innerHTML = html;
            modal.classList.remove('hidden');

                // Confirm import
                document.getElementById('confirmImport').addEventListener('click', function() {
                    const selectedCategories = Array.from(document.querySelectorAll('input[name="category_ids[]"]:checked'))
                        .map(cb => parseInt(cb.value));

                    if (selectedCategories.length === 0) {
                        alert('Lütfen en az bir kategori seçin.');
                        return;
                    }

                    const confirmBtn = document.getElementById('confirmImport');
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>İçe Aktarılıyor...';

                    fetch('{{ route("admin.categories.import") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            category_ids: selectedCategories
                        }),
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(data => {
                                throw new Error(data.message || 'HTTP ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            modal.classList.add('hidden');
                            location.reload();
                        } else {
                            alert('Hata: ' + data.message);
                            confirmBtn.disabled = false;
                            confirmBtn.innerHTML = 'İçe Aktar';
                        }
                    })
                    .catch(error => {
                        console.error('Import hatası:', error);
                        alert('Hata oluştu: ' + error.message);
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = 'İçe Aktar';
                    });
                });

                // Cancel
                document.getElementById('cancelImport').addEventListener('click', function() {
                    modal.classList.add('hidden');
                });
            }

        // Close modal
        closeModal.addEventListener('click', function() {
            modal.classList.add('hidden');
        });

        // Close on outside click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });
})();
</script>
@endpush
@endsection

