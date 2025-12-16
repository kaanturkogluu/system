@extends('admin.layouts.app')

@section('title', 'XML Kategori Eşleştirme')
@section('page-title', 'XML Kategori Eşleştirme')

@section('content')
<div class="space-y-6" id="xmlCategoryMappingApp">
    <!-- Toast Notification -->
    <div 
        id="toast"
        class="fixed top-4 right-4 z-50 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 shadow-lg max-w-md hidden"
    >
        <div class="flex items-center">
            <svg class="h-5 w-5 text-green-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="text-sm font-medium text-green-800 dark:text-green-200" id="toastMessage"></p>
        </div>
    </div>

    <!-- Feed Source Selection -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Feed Kaynağı Seçin <span class="text-red-500">*</span>
                </label>
                <select 
                    id="feedSourceSelect"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Feed kaynağı seçin...</option>
                    @foreach($feedSources as $feedSource)
                        <option value="{{ $feedSource->id }}">{{ $feedSource->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <label class="flex items-center">
                    <input 
                        type="checkbox"
                        id="filterUnmappedOnly"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    >
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Sadece eşleşmemişleri göster</span>
                </label>
            </div>
            <div class="flex items-end">
                <button 
                    id="btnRefresh"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                >
                    Yenile
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div 
        id="bulkActions"
        class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 hidden"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    <span id="selectedCount">0</span> öğe seçildi
                </span>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex-1 min-w-[300px]">
                    <select 
                        id="bulkCategorySelect"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Global kategori seçin...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat['id'] }}">{{ $cat['full_path'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button 
                    id="btnApplyBulk"
                    class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                >
                    Seçilenlere Uygula
                </button>
                <button 
                    id="btnClearSelection"
                    class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                >
                    Temizle
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input 
                                type="checkbox"
                                id="selectAll"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            XML Kategori Yolu
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Global Kategori
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Durum
                        </th>
                    </tr>
                </thead>
                <tbody id="tableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Lütfen feed kaynağı seçin
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const categories = @json($categories);
    let items = [];
    let selectedItems = new Set();

    const elements = {
        feedSourceSelect: document.getElementById('feedSourceSelect'),
        filterUnmappedOnly: document.getElementById('filterUnmappedOnly'),
        btnRefresh: document.getElementById('btnRefresh'),
        selectAll: document.getElementById('selectAll'),
        tableBody: document.getElementById('tableBody'),
        bulkActions: document.getElementById('bulkActions'),
        selectedCount: document.getElementById('selectedCount'),
        bulkCategorySelect: document.getElementById('bulkCategorySelect'),
        btnApplyBulk: document.getElementById('btnApplyBulk'),
        btnClearSelection: document.getElementById('btnClearSelection'),
        toast: document.getElementById('toast'),
        toastMessage: document.getElementById('toastMessage')
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showToast(message, type = 'success') {
        elements.toastMessage.textContent = message;
        if (type === 'error') {
            elements.toast.className = elements.toast.className.replace('bg-green-50', 'bg-red-50').replace('border-green-200', 'border-red-200');
        } else {
            elements.toast.className = elements.toast.className.replace('bg-red-50', 'bg-green-50').replace('border-red-200', 'border-green-200');
        }
        elements.toast.classList.remove('hidden');
        setTimeout(() => {
            elements.toast.classList.add('hidden');
        }, 3000);
    }

    async function loadData() {
        const feedSourceId = elements.feedSourceSelect.value;
        
        if (!feedSourceId) {
            elements.tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Lütfen feed kaynağı seçin</td></tr>';
            return;
        }

        elements.tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Yükleniyor...</td></tr>';

        try {
            const params = new URLSearchParams({
                feed_source_id: feedSourceId,
                unmapped_only: elements.filterUnmappedOnly.checked ? '1' : '0'
            });

            const response = await fetch(`{{ route('admin.xml.category-mappings.data') }}?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Loaded data:', data);
            items = Array.isArray(data) ? data : [];
            renderTable();
        } catch (error) {
            console.error('Error loading data:', error);
            showToast('Veri yüklenirken hata oluştu: ' + error.message, 'error');
            elements.tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-red-500">Hata oluştu: ' + escapeHtml(error.message) + '</td></tr>';
        }
    }

    function renderTable() {
        if (items.length === 0) {
            elements.tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Kategori bulunamadı.</td></tr>';
            return;
        }

        elements.tableBody.innerHTML = items.map(item => {
            const isSelected = selectedItems.has(item.id);
            const mappedCategory = item.mapped_category_id ? categories.find(c => c.id == item.mapped_category_id) : null;

            return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input 
                            type="checkbox"
                            data-item-id="${item.id}"
                            ${isSelected ? 'checked' : ''}
                            class="item-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 dark:text-white max-w-md">${escapeHtml(item.raw_path)}</div>
                    </td>
                    <td class="px-6 py-4">
                        <select 
                            data-item-id="${item.id}"
                            class="category-select w-full min-w-[300px] px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">Seçiniz...</option>
                            ${categories.map(cat => `
                                <option value="${cat.id}" ${item.mapped_category_id == cat.id ? 'selected' : ''}>
                                    ${escapeHtml(cat.full_path)}
                                </option>
                            `).join('')}
                        </select>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${item.mapped_category_id ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300'}">
                            ${item.mapped_category_id ? 'MAPPED' : 'NEEDS_MAPPING'}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        attachListeners();
    }

    function attachListeners() {
        document.querySelectorAll('.item-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const itemId = parseInt(this.dataset.itemId);
                if (this.checked) {
                    selectedItems.add(itemId);
                } else {
                    selectedItems.delete(itemId);
                }
                updateBulkActions();
            });
        });

        elements.selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
                const itemId = parseInt(cb.dataset.itemId);
                if (this.checked) {
                    selectedItems.add(itemId);
                } else {
                    selectedItems.delete(itemId);
                }
            });
            updateBulkActions();
        });

        document.querySelectorAll('.category-select').forEach(select => {
            select.addEventListener('change', function() {
                if (this.value) {
                    updateMapping(this.dataset.itemId, this.value);
                }
            });
        });
    }

    function updateBulkActions() {
        const count = selectedItems.size;
        elements.selectedCount.textContent = count;
        elements.bulkActions.classList.toggle('hidden', count === 0);
    }

    async function updateMapping(itemId, categoryId) {
        const mappings = [{
            external_id: parseInt(itemId),
            global_id: parseInt(categoryId)
        }];

        try {
            const response = await fetch('{{ route('admin.xml.category-mappings.bulk') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(mappings)
            });

            const result = await response.json();
            if (result.success) {
                const item = items.find(i => i.id == itemId);
                if (item) {
                    item.mapped_category_id = parseInt(categoryId);
                }
                renderTable();
                showToast('Eşleştirme kaydedildi');
            } else {
                showToast('Eşleştirme kaydedilemedi', 'error');
            }
        } catch (error) {
            console.error('Error updating mapping:', error);
            showToast('Eşleştirme kaydedilirken hata oluştu', 'error');
        }
    }

    async function applyBulk() {
        if (!elements.bulkCategorySelect.value || selectedItems.size === 0) return;

        const categoryId = parseInt(elements.bulkCategorySelect.value);
        const mappings = Array.from(selectedItems).map(id => ({
            external_id: id,
            global_id: categoryId
        }));

        elements.btnApplyBulk.disabled = true;
        elements.btnApplyBulk.textContent = 'Kaydediliyor...';

        try {
            const response = await fetch('{{ route('admin.xml.category-mappings.bulk') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(mappings)
            });

            const result = await response.json();
            if (result.success) {
                showToast(`${mappings.length} eşleştirme başarıyla kaydedildi`);
                clearSelection();
                loadData();
            } else {
                showToast('Eşleştirmeler kaydedilemedi', 'error');
            }
        } catch (error) {
            console.error('Error bulk updating:', error);
            showToast('Eşleştirmeler kaydedilirken hata oluştu', 'error');
        } finally {
            elements.btnApplyBulk.disabled = false;
            elements.btnApplyBulk.textContent = 'Seçilenlere Uygula';
        }
    }

    function clearSelection() {
        selectedItems.clear();
        elements.selectAll.checked = false;
        elements.bulkCategorySelect.value = '';
        updateBulkActions();
    }

    elements.feedSourceSelect.addEventListener('change', loadData);
    elements.filterUnmappedOnly.addEventListener('change', loadData);
    elements.btnRefresh.addEventListener('click', loadData);
    elements.btnApplyBulk.addEventListener('click', applyBulk);
    elements.btnClearSelection.addEventListener('click', clearSelection);
})();
</script>
@endsection

