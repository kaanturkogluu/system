@extends('admin.layouts.app')

@section('title', 'Pazaryeri Kategori Eşleştirme')
@section('page-title', 'Pazaryeri Kategori Eşleştirme')

@section('content')
<div class="space-y-6">
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

    <!-- Pazaryeri Seçimi -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <label class="block text-sm font-medium mb-2">Pazaryeri Seçin</label>
        <select 
            id="marketplace-selector" 
            onchange="changeMarketplace(this.value)"
            class="w-full md:w-64 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
        >
            <option value="">-- Pazaryeri Seçin --</option>
            @foreach($activeMarketplaces as $marketplace)
                <option value="{{ $marketplace->id }}" {{ $selectedMarketplaceId == $marketplace->id ? 'selected' : '' }}>
                    {{ $marketplace->name }}
                </option>
            @endforeach
        </select>
    </div>

    @if(!$selectedMarketplace)
        <div class="bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-400 text-yellow-700 dark:text-yellow-300 px-4 py-3 rounded">
            Lütfen bir pazaryeri seçin.
        </div>
    @endif

    <!-- Eşleştirilmiş Kategoriler ve Komisyon Oranları -->
    @if($selectedMarketplace && isset($mappingDetails) && count($mappingDetails) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">{{ $selectedMarketplace->name }} - Eşleştirilmiş Kategoriler ve Komisyon Oranları</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $selectedMarketplace->name }} Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sistem Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Komisyon Oranı (%)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $mappedCategories = \App\Models\MarketplaceCategory::where('marketplace_id', $selectedMarketplace->id)
                            ->whereNotNull('global_category_id')
                            ->where('is_mapped', true)
                            ->with('globalCategory')
                            ->get();
                    @endphp
                    @foreach($mappedCategories as $mapping)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            {{ $mapping->name }} (ID: {{ $mapping->marketplace_category_id }})
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            {{ $mapping->globalCategory ? $mapping->globalCategory->name : 'N/A' }} (ID: {{ $mapping->global_category_id }})
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <input 
                                type="number" 
                                step="0.01"
                                min="0"
                                max="100"
                                value="{{ $mapping->commission_rate ?? '' }}"
                                data-mapping-id="{{ $mapping->id }}"
                                class="marketplace-commission-input w-24 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                                placeholder="0.00"
                            >
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <button 
                                onclick="updateCommissionRate({{ $mapping->id }})"
                                class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition"
                            >
                                Kaydet
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Sol: Sistem Kategorileri -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">Sistem Kategorileri</h2>
                <button 
                    id="import-attributes-btn" 
                    onclick="importAttributes()" 
                    class="hidden bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-lg"
                    disabled
                >
                    Özellikleri İçe Aktar
                </button>
            </div>
            <div class="mb-4">
                <input 
                    type="text" 
                    id="system-search" 
                    placeholder="Kategori ara..." 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                    onkeyup="filterSystemCategories(this.value)"
                >
            </div>
            <div class="max-h-[600px] overflow-y-auto" id="system-categories-container">
                @php
                    function renderSystemCategory($category, $level = 0, $existingMappings = []) {
                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                        $hasChildren = $category->children->count() > 0;
                        $nameEscaped = htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8');
                        $html = '<div class="system-category-item py-2 px-3 hover:bg-gray-100 dark:hover:bg-gray-700 rounded cursor-pointer" data-category-id="' . $category->id . '" onclick="selectSystemCategory(' . $category->id . ', \'' . $nameEscaped . '\')">';
                        $html .= '<div class="flex items-center justify-between">';
                        $html .= '<span>' . $indent . ($hasChildren ? '📁 ' : '📄 ') . $nameEscaped . '</span>';
                        $html .= '<span class="text-xs text-gray-500">ID: ' . $category->id . '</span>';
                        $html .= '</div></div>';
                        
                        foreach ($category->children as $child) {
                            $html .= renderSystemCategory($child, $level + 1, $existingMappings);
                        }
                        
                        return $html;
                    }
                @endphp
                @foreach($systemCategories as $category)
                    {!! renderSystemCategory($category, 0, $existingMappings) !!}
                @endforeach
            </div>
        </div>

        <!-- Sağ: Pazaryeri Kategorileri -->
        @if($selectedMarketplace)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">{{ $selectedMarketplace->name }} Kategorileri</h2>
                @if(!$hasCategories)
                    <button 
                        id="download-categories-btn" 
                        onclick="downloadCategories('{{ $selectedMarketplace->slug }}')"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-lg transition"
                    >
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Kategorileri İndir
                    </button>
                @endif
            </div>
            @if($hasCategories)
            <div class="mb-4">
                <input 
                    type="text" 
                    id="marketplace-search" 
                    placeholder="Kategori ara..." 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                    onkeyup="filterMarketplaceCategories(this.value)"
                >
            </div>
            @endif
            <div class="max-h-[600px] overflow-y-auto" id="marketplace-categories-container">
                @if($hasCategories && !empty($marketplaceCategories))
                    @php
                        function renderMarketplaceCategory($category, $level = 0, $existingMappings = []) {
                            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                            $hasChildren = !empty($category['subCategories']);
                            $isMapped = isset($existingMappings[$category['id']]);
                            $mappedClass = $isMapped ? 'bg-green-50 dark:bg-green-900/20 border-green-300' : '';
                            $mappedBadge = $isMapped ? '<span class="text-xs bg-green-500 text-white px-2 py-1 rounded ml-2">Eşleştirilmiş</span>' : '';
                            $nameEscaped = htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8');
                            
                            $html = '<div class="marketplace-category-item py-2 px-3 hover:bg-gray-100 dark:hover:bg-gray-700 rounded border ' . $mappedClass . '" data-marketplace-category-id="' . $category['id'] . '">';
                            $html .= '<div class="flex items-center justify-between">';
                            $html .= '<div class="flex-1">';
                            $html .= '<span>' . $indent . ($hasChildren ? '📁 ' : '📄 ') . $nameEscaped . '</span>';
                            $html .= $mappedBadge;
                            $html .= '</div>';
                            $html .= '<div class="flex items-center gap-2">';
                            $html .= '<span class="text-xs text-gray-500">ID: ' . $category['id'] . '</span>';
                            $html .= '<button onclick="mapCategory(' . $category['id'] . ', \'' . $nameEscaped . '\')" class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Eşleştir</button>';
                            $html .= '</div>';
                            $html .= '</div></div>';
                            
                            if ($hasChildren) {
                                foreach ($category['subCategories'] as $subCategory) {
                                    $html .= renderMarketplaceCategory($subCategory, $level + 1, $existingMappings);
                                }
                            }
                            
                            return $html;
                        }
                    @endphp
                    @foreach($marketplaceCategories as $category)
                        {!! renderMarketplaceCategory($category, 0, $existingMappings) !!}
                    @endforeach
                @elseif(!$hasCategories)
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">Bu pazaryeri için kategori bulunamadı.</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Kategorileri indirmek için yukarıdaki "Kategorileri İndir" butonuna tıklayın.</p>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">Bu pazaryeri için kategori bulunamadı.</p>
                @endif
            </div>
        </div>
        @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Pazaryeri Kategorileri</h2>
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">Lütfen bir pazaryeri seçin.</p>
        </div>
        @endif
    </div>
</div>

<!-- Mapping Modal -->
<div id="mapping-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold mb-4">Kategori Eşleştirme</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Pazaryeri Kategorisi</label>
                <div id="selected-marketplace-category" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded"></div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Sistem Kategorisi</label>
                <div id="selected-system" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded"></div>
                <p class="text-xs text-gray-500 mt-1">Yukarıdan bir sistem kategorisi seçin</p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Pazaryeri Kategori Komisyon Oranı (%)</label>
                <input 
                    type="number" 
                    id="commission-rate-input"
                    step="0.01"
                    min="0"
                    max="100"
                    placeholder="Örn: 5.00"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500"
                >
                <p class="text-xs text-gray-500 mt-1">Boş bırakılırsa komisyon eklenmez</p>
            </div>
            <div class="flex gap-3">
                <button onclick="saveMapping()" id="save-mapping-btn" disabled class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Eşleştir</button>
                <button onclick="closeMappingModal()" class="flex-1 bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-lg">İptal</button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedSystemCategoryId = null;
let selectedSystemCategoryName = null;
let selectedMarketplaceCategoryId = null;
let selectedMarketplaceCategoryName = null;
let selectedMarketplaceId = @json($selectedMarketplaceId);

function selectSystemCategory(id, name) {
    selectedSystemCategoryId = id;
    selectedSystemCategoryName = name;
    
    // Update UI
    document.querySelectorAll('.system-category-item').forEach(item => {
        item.classList.remove('bg-blue-100', 'dark:bg-blue-900');
        if (item.getAttribute('data-category-id') == id) {
            item.classList.add('bg-blue-100', 'dark:bg-blue-900');
        }
    });
    
    // Show import button
    const importBtn = document.getElementById('import-attributes-btn');
    if (importBtn) {
        importBtn.classList.remove('hidden');
        importBtn.disabled = false;
        importBtn.setAttribute('data-category-id', id);
    }
    
    // Update modal if open
    const modal = document.getElementById('mapping-modal');
    if (!modal.classList.contains('hidden')) {
        document.getElementById('selected-system').textContent = name + ' (ID: ' + id + ')';
        
        // Enable save button if trendyol category is selected
        if (selectedTrendyolCategoryId) {
            document.getElementById('save-mapping-btn').disabled = false;
        }
    }
}

function importAttributes() {
    const btn = document.getElementById('import-attributes-btn');
    const categoryId = btn.getAttribute('data-category-id');
    
    if (!categoryId) {
        alert('Lütfen bir kategori seçin');
        return;
    }
    
    if (!confirm('Bu kategorinin Trendyol özelliklerini içe aktarmak istediğinize emin misiniz?')) {
        return;
    }
    
    btn.disabled = true;
    btn.textContent = 'İçe aktarılıyor...';
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.marketplace-category-mappings.import-attributes") }}';
    
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    
    const categoryInput = document.createElement('input');
    categoryInput.type = 'hidden';
    categoryInput.name = 'category_id';
    categoryInput.value = categoryId;
    form.appendChild(categoryInput);
    
    document.body.appendChild(form);
    form.submit();
}

function mapCategory(marketplaceCategoryId, marketplaceCategoryName) {
    selectedMarketplaceCategoryId = marketplaceCategoryId;
    selectedMarketplaceCategoryName = marketplaceCategoryName;
    
    document.getElementById('selected-marketplace-category').textContent = marketplaceCategoryName + ' (ID: ' + marketplaceCategoryId + ')';
    document.getElementById('selected-system').textContent = selectedSystemCategoryName ? selectedSystemCategoryName + ' (ID: ' + selectedSystemCategoryId + ')' : 'Seçilmedi';
    document.getElementById('save-mapping-btn').disabled = !selectedSystemCategoryId;
    
    // Mevcut komisyon oranını yükle (eğer eşleştirme varsa)
    @if(isset($mappingDetails))
        const mappingDetails = @json($mappingDetails);
        const existingMapping = mappingDetails[marketplaceCategoryId];
        if (existingMapping && existingMapping.commission_rate !== null) {
            document.getElementById('commission-rate-input').value = existingMapping.commission_rate;
        } else {
            document.getElementById('commission-rate-input').value = '';
        }
    @else
        document.getElementById('commission-rate-input').value = '';
    @endif
    
    document.getElementById('mapping-modal').classList.remove('hidden');
}

function closeMappingModal() {
    document.getElementById('mapping-modal').classList.add('hidden');
    selectedSystemCategoryId = null;
    selectedSystemCategoryName = null;
    selectedMarketplaceCategoryId = null;
    selectedMarketplaceCategoryName = null;
}

function saveMapping() {
    if (!selectedSystemCategoryId || !selectedMarketplaceCategoryId || !selectedMarketplaceId) {
        return;
    }

    const commissionRate = document.getElementById('commission-rate-input').value;

    fetch('{{ route("admin.marketplace-category-mappings.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            marketplace_id: selectedMarketplaceId,
            marketplace_category_id: selectedMarketplaceCategoryId,
            global_category_id: selectedSystemCategoryId,
            commission_rate: commissionRate || null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Hata: ' + (data.message || 'Eşleştirme yapılamadı'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu');
    });
}

function updateCommissionRate(mappingId) {
    const input = document.querySelector(`input[data-mapping-id="${mappingId}"]`);
    const commissionRate = input.value;
    
    fetch(`/admin/marketplace-category-mappings/${mappingId}/update-commission-rate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            commission_rate: commissionRate || null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Komisyon oranı başarıyla güncellendi.');
        } else {
            alert('Hata: ' + (data.message || 'Komisyon oranı güncellenemedi'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu');
    });
}

function filterSystemCategories(search) {
    const items = document.querySelectorAll('.system-category-item');
    const searchLower = search.toLowerCase();
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchLower) ? 'block' : 'none';
    });
}

function filterMarketplaceCategories(search) {
    const items = document.querySelectorAll('.marketplace-category-item');
    const searchLower = search.toLowerCase();
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchLower) ? 'block' : 'none';
    });
}

function changeMarketplace(marketplaceId) {
    if (marketplaceId) {
        window.location.href = '{{ route("admin.marketplace-category-mappings.index") }}?marketplace_id=' + marketplaceId;
    } else {
        window.location.href = '{{ route("admin.marketplace-category-mappings.index") }}';
    }
}

function downloadCategories(marketplaceSlug) {
    const btn = document.getElementById('download-categories-btn');
    if (!btn) return;

    if (!confirm('Kategorileri indirmek istediğinize emin misiniz? Bu işlem biraz zaman alabilir.')) {
        return;
    }

    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin w-5 h-5 inline mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>İndiriliyor...';

    // Route belirleme
    let routeUrl = '';
    if (marketplaceSlug === 'trendyol') {
        routeUrl = '{{ route("admin.categories.download-trendyol") }}';
    } else if (marketplaceSlug === 'n11') {
        routeUrl = '{{ route("admin.categories.download-n11") }}';
    } else {
        alert('Bu pazaryeri için kategori indirme desteği bulunmamaktadır.');
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        alert('CSRF token bulunamadı!');
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }

    fetch(routeUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken.content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
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
            alert('Kategoriler başarıyla indirildi! Sayfa yenileniyor...');
            // Sayfayı yenile
            window.location.reload();
        } else {
            alert('Hata: ' + (data.message || 'Kategoriler indirilemedi'));
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Bir hata oluştu: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>
@endsection

