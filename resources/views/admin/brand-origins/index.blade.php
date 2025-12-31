@extends('admin.layouts.app')

@section('title', 'Marka Menşei Yönetimi')
@section('page-title', 'Marka Menşei Yönetimi')

@section('content')
<div class="space-y-6">
    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Marka</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $totalBrands }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Menşei Tanımlı</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">{{ $brandsWithOrigin }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Menşei Eksik</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-2">{{ $brandsWithoutOrigin }}</p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.brand-origins.index') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}"
                    placeholder="Marka ara..." 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <div>
                <select 
                    name="filter" 
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Tümü</option>
                    <option value="missing" {{ request('filter') === 'missing' ? 'selected' : '' }}>Menşei Eksik</option>
                    <option value="has" {{ request('filter') === 'has' ? 'selected' : '' }}>Menşei Tanımlı</option>
                </select>
            </div>
            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                    Filtrele
                </button>
            </div>
            @if(request('search') || request('filter'))
                <div>
                    <a href="{{ route('admin.brand-origins.index') }}" class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold py-2 px-6 rounded-lg transition duration-200">
                        Temizle
                    </a>
                </div>
            @endif
        </form>
    </div>

    <!-- Success/Error Messages -->
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

    <!-- Brands Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Markalar</h3>
                <button 
                    onclick="toggleBulkMode()" 
                    id="bulk-mode-btn"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-sm transition"
                >
                    Toplu Düzenle
                </button>
            </div>
        </div>

        <!-- Bulk Update Form -->
        <form id="bulk-update-form" method="POST" action="{{ route('admin.brand-origins.bulk-update') }}" class="hidden px-6 py-4 bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-800">
            @csrf
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <select 
                        name="origin_country_id" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Menşei Seçin</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }} ({{ $country->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        Seçili Markaları Güncelle
                    </button>
                </div>
                <div>
                    <button type="button" onclick="toggleBulkMode()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg">
                        İptal
                    </button>
                </div>
            </div>
            <div id="selected-brand-inputs"></div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <input type="checkbox" id="select-all" class="rounded" onchange="toggleAll(this)">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Marka</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Menşei</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pazaryeri Eşleştirmeleri</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($brands as $brand)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" class="brand-checkbox rounded" value="{{ $brand->id }}" onchange="updateSelectedBrands()">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $brand->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $brand->slug }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($brand->originCountry)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300">
                                        {{ $brand->originCountry->name }} ({{ $brand->originCountry->code }})
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300">
                                        Menşei Tanımlı Değil
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($brand->originCountry)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($marketplaces as $marketplace)
                                            @php
                                                $mapping = null;
                                                if ($marketplaceCountryMappings->has($marketplace->id)) {
                                                    $marketplaceMappings = $marketplaceCountryMappings->get($marketplace->id);
                                                    if ($marketplaceMappings && $marketplaceMappings->has($brand->origin_country_id)) {
                                                        $mapping = $marketplaceMappings->get($brand->origin_country_id)->first();
                                                    }
                                                }
                                                $mappingData = $mapping ? [
                                                    'external_country_id' => $mapping->external_country_id ?? '',
                                                    'external_country_code' => $mapping->external_country_code ?? '',
                                                    'external_country_name' => $mapping->external_country_name ?? '',
                                                    'status' => $mapping->status ?? 'active'
                                                ] : null;
                                            @endphp
                                            <div class="flex items-center gap-1">
                                                @if($mapping && $mapping->status === 'active')
                                                    <span class="px-2 py-1 text-xs rounded bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300" title="{{ $mapping->external_country_name ?? $mapping->external_country_code ?? 'Eşleştirme mevcut' }}">
                                                        {{ $marketplace->name }}
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 text-xs rounded bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300">
                                                        {{ $marketplace->name }}
                                                    </span>
                                                @endif
                                                <button 
                                                    onclick="openMarketplaceMappingModal({{ $marketplace->id }}, '{{ addslashes($marketplace->name) }}', {{ $brand->origin_country_id }}, '{{ addslashes($brand->originCountry->name) }}', {{ $mappingData ? json_encode($mappingData) : 'null' }})"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline text-xs"
                                                    title="Düzenle"
                                                >
                                                    ✏️
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">Menşei tanımlı değil</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button 
                                    onclick="openEditModal({{ $brand->id }}, '{{ $brand->name }}', {{ $brand->origin_country_id ?? 'null' }})"
                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                >
                                    Düzenle
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                Marka bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $brands->links() }}
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="edit-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Menşei Düzenle</h3>
        <form id="edit-form" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Marka</label>
                    <p id="modal-brand-name" class="text-gray-900 dark:text-white font-medium"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Menşei</label>
                    <select 
                        name="origin_country_id" 
                        id="modal-origin-country"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="">Menşei Seçin</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }} ({{ $country->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        Kaydet
                    </button>
                    <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-lg text-gray-800 dark:text-gray-200">
                        İptal
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Marketplace Mapping Modal -->
<div id="marketplace-mapping-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-lg w-full mx-4">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Pazaryeri Menşei Eşleştirmesi</h3>
        <form id="marketplace-mapping-form">
            @csrf
            <input type="hidden" name="marketplace_id" id="mp-modal-marketplace-id">
            <input type="hidden" name="country_id" id="mp-modal-country-id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Pazaryeri</label>
                    <p id="mp-modal-marketplace-name" class="text-gray-900 dark:text-white font-medium"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Menşei</label>
                    <p id="mp-modal-country-name" class="text-gray-900 dark:text-white font-medium"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Pazaryeri Ülke ID</label>
                    <input 
                        type="number" 
                        name="external_country_id" 
                        id="mp-modal-external-id"
                        placeholder="Örn: 10633877"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pazaryerindeki ülke ID'si (Trendyol attribute value ID)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Pazaryeri Ülke Kodu</label>
                    <input 
                        type="text" 
                        name="external_country_code" 
                        id="mp-modal-external-code"
                        placeholder="Örn: KR, CN, TR"
                        maxlength="50"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pazaryerinde kullanılan ülke kodu</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Pazaryeri Ülke Adı</label>
                    <input 
                        type="text" 
                        name="external_country_name" 
                        id="mp-modal-external-name"
                        placeholder="Örn: Güney Kore, Çin, Türkiye"
                        maxlength="255"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pazaryerinde kullanılan ülke adı</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Durum</label>
                    <select 
                        name="status" 
                        id="mp-modal-status"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="active">Aktif</option>
                        <option value="passive">Pasif</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        Kaydet
                    </button>
                    <button type="button" onclick="closeMarketplaceMappingModal()" class="flex-1 bg-gray-200 dark:bg-gray-700 px-4 py-2 rounded-lg text-gray-800 dark:text-gray-200">
                        İptal
                    </button>
                    <button type="button" onclick="deleteMarketplaceMapping()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                        Sil
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let bulkMode = false;
let selectedBrands = new Set();

function toggleBulkMode() {
    bulkMode = !bulkMode;
    const form = document.getElementById('bulk-update-form');
    const btn = document.getElementById('bulk-mode-btn');
    
    if (bulkMode) {
        form.classList.remove('hidden');
        btn.textContent = 'Toplu Düzenlemeyi Kapat';
        btn.classList.remove('bg-gray-200', 'dark:bg-gray-700');
        btn.classList.add('bg-yellow-500', 'text-white');
    } else {
        form.classList.add('hidden');
        btn.textContent = 'Toplu Düzenle';
        btn.classList.remove('bg-yellow-500', 'text-white');
        btn.classList.add('bg-gray-200', 'dark:bg-gray-700');
        selectedBrands.clear();
        document.querySelectorAll('.brand-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('select-all').checked = false;
        updateSelectedBrands();
    }
}

function toggleAll(checkbox) {
    document.querySelectorAll('.brand-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
        if (checkbox.checked) {
            selectedBrands.add(parseInt(cb.value));
        } else {
            selectedBrands.delete(parseInt(cb.value));
        }
    });
    updateSelectedBrands();
}

function updateSelectedBrands() {
    selectedBrands.clear();
    document.querySelectorAll('.brand-checkbox:checked').forEach(cb => {
        selectedBrands.add(parseInt(cb.value));
    });
    
    // Update hidden inputs for bulk update
    const container = document.getElementById('selected-brand-inputs');
    container.innerHTML = '';
    selectedBrands.forEach(brandId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'brand_ids[]';
        input.value = brandId;
        container.appendChild(input);
    });
    
    // Update select-all checkbox
    const allChecked = document.querySelectorAll('.brand-checkbox').length > 0 && 
                      document.querySelectorAll('.brand-checkbox:checked').length === document.querySelectorAll('.brand-checkbox').length;
    document.getElementById('select-all').checked = allChecked;
}

function openEditModal(brandId, brandName, originCountryId) {
    document.getElementById('modal-brand-name').textContent = brandName;
    document.getElementById('modal-origin-country').value = originCountryId || '';
    document.getElementById('edit-form').action = `/admin/brand-origins/${brandId}`;
    document.getElementById('edit-modal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}

// Close modal on outside click
document.getElementById('edit-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.getElementById('marketplace-mapping-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeMarketplaceMappingModal();
    }
});

// Marketplace Mapping Modal Functions
function openMarketplaceMappingModal(marketplaceId, marketplaceName, countryId, countryName, mapping) {
    document.getElementById('mp-modal-marketplace-id').value = marketplaceId;
    document.getElementById('mp-modal-marketplace-name').textContent = marketplaceName;
    document.getElementById('mp-modal-country-id').value = countryId;
    document.getElementById('mp-modal-country-name').textContent = countryName;
    
    if (mapping) {
        document.getElementById('mp-modal-external-id').value = mapping.external_country_id || '';
        document.getElementById('mp-modal-external-code').value = mapping.external_country_code || '';
        document.getElementById('mp-modal-external-name').value = mapping.external_country_name || '';
        document.getElementById('mp-modal-status').value = mapping.status || 'active';
    } else {
        document.getElementById('mp-modal-external-id').value = '';
        document.getElementById('mp-modal-external-code').value = '';
        document.getElementById('mp-modal-external-name').value = '';
        document.getElementById('mp-modal-status').value = 'active';
    }
    
    document.getElementById('marketplace-mapping-modal').classList.remove('hidden');
}

function closeMarketplaceMappingModal() {
    document.getElementById('marketplace-mapping-modal').classList.add('hidden');
}

// Submit marketplace mapping form
document.getElementById('marketplace-mapping-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('{{ route("admin.brand-origins.marketplace-mapping.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Hata: ' + (result.message || 'Bir hata oluştu'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Bir hata oluştu: ' + error.message);
    }
});

// Delete marketplace mapping
async function deleteMarketplaceMapping() {
    if (!confirm('Bu pazaryeri menşei eşleştirmesini silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    const marketplaceId = document.getElementById('mp-modal-marketplace-id').value;
    const countryId = document.getElementById('mp-modal-country-id').value;
    
    try {
        const response = await fetch('{{ route("admin.brand-origins.marketplace-mapping.delete") }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                marketplace_id: marketplaceId,
                country_id: countryId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert('Hata: ' + (result.message || 'Bir hata oluştu'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Bir hata oluştu: ' + error.message);
    }
}
</script>
@endsection

