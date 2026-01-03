@extends('admin.layouts.app')

@section('title', 'Pazaryeri Kategori Eşleştirme')
@section('page-title', 'Trendyol Kategori Eşleştirme')

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

    <!-- Eşleştirilmiş Kategoriler ve Komisyon Oranları -->
    @if(isset($mappingDetails) && count($mappingDetails) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Eşleştirilmiş Kategoriler ve Komisyon Oranları</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Trendyol Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sistem Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Komisyon Oranı (%)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $trendyolMarketplace = \App\Models\Marketplace::where('slug', 'trendyol')->first();
                        $mappedCategories = \App\Models\MarketplaceCategory::where('marketplace_id', $trendyolMarketplace->id)
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

        <!-- Sağ: Trendyol Kategorileri -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold mb-4">Trendyol Kategorileri</h2>
            <div class="mb-4">
                <input 
                    type="text" 
                    id="trendyol-search" 
                    placeholder="Kategori ara..." 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                    onkeyup="filterTrendyolCategories(this.value)"
                >
            </div>
            <div class="max-h-[600px] overflow-y-auto" id="trendyol-categories-container">
                @php
                    function renderTrendyolCategory($category, $level = 0, $existingMappings = []) {
                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                        $hasChildren = !empty($category['subCategories']);
                        $isMapped = isset($existingMappings[$category['id']]);
                        $mappedClass = $isMapped ? 'bg-green-50 dark:bg-green-900/20 border-green-300' : '';
                        $mappedBadge = $isMapped ? '<span class="text-xs bg-green-500 text-white px-2 py-1 rounded ml-2">Eşleştirilmiş</span>' : '';
                        $nameEscaped = htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8');
                        
                        $html = '<div class="trendyol-category-item py-2 px-3 hover:bg-gray-100 dark:hover:bg-gray-700 rounded border ' . $mappedClass . '" data-trendyol-id="' . $category['id'] . '">';
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
                                $html .= renderTrendyolCategory($subCategory, $level + 1, $existingMappings);
                            }
                        }
                        
                        return $html;
                    }
                @endphp
                @foreach($trendyolCategories as $category)
                    {!! renderTrendyolCategory($category, 0, $existingMappings) !!}
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Mapping Modal -->
<div id="mapping-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-semibold mb-4">Kategori Eşleştirme</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Trendyol Kategorisi</label>
                <div id="selected-trendyol" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded"></div>
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
let selectedTrendyolCategoryId = null;
let selectedTrendyolCategoryName = null;

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

function mapCategory(trendyolId, trendyolName) {
    selectedTrendyolCategoryId = trendyolId;
    selectedTrendyolCategoryName = trendyolName;
    
    document.getElementById('selected-trendyol').textContent = trendyolName + ' (ID: ' + trendyolId + ')';
    document.getElementById('selected-system').textContent = selectedSystemCategoryName ? selectedSystemCategoryName + ' (ID: ' + selectedSystemCategoryId + ')' : 'Seçilmedi';
    document.getElementById('save-mapping-btn').disabled = !selectedSystemCategoryId;
    
    // Mevcut komisyon oranını yükle (eğer eşleştirme varsa)
    @if(isset($mappingDetails))
        const mappingDetails = @json($mappingDetails);
        const existingMapping = mappingDetails[trendyolId];
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
    selectedTrendyolCategoryId = null;
    selectedTrendyolCategoryName = null;
}

function saveMapping() {
    if (!selectedSystemCategoryId || !selectedTrendyolCategoryId) {
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
            trendyol_category_id: selectedTrendyolCategoryId,
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

function filterTrendyolCategories(search) {
    const items = document.querySelectorAll('.trendyol-category-item');
    const searchLower = search.toLowerCase();
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchLower) ? 'block' : 'none';
    });
}
</script>
@endsection
