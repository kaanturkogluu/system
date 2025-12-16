@extends('admin.layouts.app')

@section('title', 'XML Kategori Eşleştirme')
@section('page-title', 'XML Kategori Eşleştirme')

@section('content')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

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

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Feed Kaynağı <span class="text-red-500">*</span>
                </label>
                <select 
                    id="feedSourceSelect"
                    name="feed_source_id"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Feed kaynağı seçin...</option>
                    @foreach($feedSources as $feedSource)
                        <option value="{{ $feedSource->id }}">{{ $feedSource->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Ana Kategori
                </label>
                <select 
                    id="parentCategorySelect"
                    name="parent_category_id"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Tüm Ana Kategoriler</option>
                    @foreach($mainCategories as $mainCat)
                        <option value="{{ $mainCat->id }}">{{ $mainCat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <label class="flex items-center">
                    <input 
                        type="checkbox"
                        id="filterUnmappedOnly"
                        name="unmapped_only"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    >
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Sadece eşleşmemişleri göster</span>
                </label>
            </div>
            <div class="flex items-end">
                <button 
                    type="button"
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
                        name="bulk_category_id"
                        class="w-full"
                    >
                        <option value="">Global kategori seçin...</option>
                    </select>
                </div>
                <button 
                    type="button"
                    id="btnApplyBulk"
                    class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                >
                    Seçilenlere Uygula
                </button>
                <button 
                    type="button"
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
                            name="select_all"
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
        <!-- Pagination -->
        <div id="paginationContainer" class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600 hidden">
            <!-- Pagination links will be loaded here -->
        </div>
    </div>
</div>

<!-- jQuery (Select2 dependency) - Must be loaded before Select2 -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
(function() {
    'use strict';

    let items = [];
    let selectedItems = new Set();
    let currentPage = 1;
    let lastPage = 1;
    let parentCategoryId = '';
    let allCategories = [];

    const elements = {
        feedSourceSelect: document.getElementById('feedSourceSelect'),
        parentCategorySelect: document.getElementById('parentCategorySelect'),
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
        toastMessage: document.getElementById('toastMessage'),
        paginationContainer: document.getElementById('paginationContainer')
    };

    // Select2 initialization
    let bulkCategorySelect2 = null;
    let rowCategorySelect2s = {};

    function initBulkCategorySelect() {
        if (bulkCategorySelect2 && $(elements.bulkCategorySelect).hasClass('select2-hidden-accessible')) {
            $(elements.bulkCategorySelect).select2('destroy');
        }

        bulkCategorySelect2 = $(elements.bulkCategorySelect).select2({
            theme: 'bootstrap-5',
            placeholder: 'Global kategori seçin...',
            allowClear: true,
            width: '100%',
            ajax: {
                url: '{{ route('admin.xml.category-mappings.categories') }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        parent_category_id: parentCategoryId
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            }
        });
    }

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

    async function loadData(page = 1) {
        const feedSourceId = elements.feedSourceSelect.value;
        parentCategoryId = elements.parentCategorySelect.value;
        
        if (!feedSourceId) {
            elements.tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Lütfen feed kaynağı seçin</td></tr>';
            elements.paginationContainer.classList.add('hidden');
            return;
        }

        elements.tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Yükleniyor...</td></tr>';

        try {
            const params = new URLSearchParams({
                feed_source_id: feedSourceId,
                unmapped_only: elements.filterUnmappedOnly.checked ? '1' : '0',
                page: page
            });

            if (parentCategoryId) {
                params.append('parent_category_id', parentCategoryId);
            }

            const response = await fetch(`{{ route('admin.xml.category-mappings.data') }}?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            items = Array.isArray(result.data) ? result.data : [];
            currentPage = result.meta.current_page;
            lastPage = result.meta.last_page;

            renderTable();
            renderPagination(result.links, result.meta);
        } catch (error) {
            console.error('Error loading data:', error);
            showToast('Veri yüklenirken hata oluştu: ' + error.message, 'error');
            elements.tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-red-500">Hata oluştu: ' + escapeHtml(error.message) + '</td></tr>';
            elements.paginationContainer.classList.add('hidden');
        }
    }

    async function loadCategoriesForSelect() {
        try {
            const params = new URLSearchParams();
            if (parentCategoryId) {
                params.append('parent_category_id', parentCategoryId);
            }

            const response = await fetch(`{{ route('admin.xml.category-mappings.categories') }}?${params}`);
            const result = await response.json();
            allCategories = result.results || [];
        } catch (error) {
            console.error('Error loading categories:', error);
            allCategories = [];
        }
    }

    function renderTable() {
        if (items.length === 0) {
            elements.tableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Kategori bulunamadı.</td></tr>';
            return;
        }

        // Destroy existing Select2 instances
        Object.keys(rowCategorySelect2s).forEach(itemId => {
            const select2Instance = rowCategorySelect2s[itemId];
            if (select2Instance && select2Instance.hasClass && select2Instance.hasClass('select2-hidden-accessible')) {
                select2Instance.select2('destroy');
            }
        });
        rowCategorySelect2s = {};

        elements.tableBody.innerHTML = items.map(item => {
            const isSelected = selectedItems.has(item.id);
            const mappedCategoryId = item.mapped_category_id;

            return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input 
                            type="checkbox"
                            id="item-checkbox-${item.id}"
                            name="item_${item.id}"
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
                            id="category-select-${item.id}"
                            name="category_${item.id}"
                            data-item-id="${item.id}"
                            class="category-select-${item.id} w-full"
                        >
                            <option value="">Seçiniz...</option>
                        </select>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${mappedCategoryId ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300'}">
                            ${mappedCategoryId ? 'MAPPED' : 'NEEDS_MAPPING'}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');

        // Initialize Select2 for each row - Use setTimeout to ensure DOM is ready
        setTimeout(() => {
            items.forEach(item => {
                const selectElement = document.querySelector(`.category-select-${item.id}`);
                if (selectElement) {
                    // Ensure element is not already initialized
                    if ($(selectElement).hasClass('select2-hidden-accessible')) {
                        $(selectElement).select2('destroy');
                    }

                    const select2 = $(selectElement).select2({
                        theme: 'bootstrap-5',
                        placeholder: 'Kategori seçin...',
                        allowClear: true,
                        width: '100%',
                        dropdownAutoWidth: true,
                        ajax: {
                            url: '{{ route('admin.xml.category-mappings.categories') }}',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    search: params.term,
                                    parent_category_id: parentCategoryId
                                };
                            },
                            processResults: function (data) {
                                return {
                                    results: data.results
                                };
                            },
                            cache: true
                        }
                    });

                    // Set selected value if exists - without triggering change event
                    if (item.mapped_category_id) {
                        // Load category name via AJAX first
                        fetch(`{{ route('admin.xml.category-mappings.categories') }}?parent_category_id=${parentCategoryId || ''}`)
                            .then(response => response.json())
                            .then(data => {
                                const category = data.results.find(c => c.id == item.mapped_category_id);
                                if (category) {
                                    // Create option with text
                                    const option = new Option(category.text, category.id, true, true);
                                    selectElement.appendChild(option);
                                    // Set value without triggering change event
                                    select2.val(item.mapped_category_id);
                                } else {
                                    // Fallback: set value without text
                                    const option = new Option('', item.mapped_category_id, true, true);
                                    selectElement.appendChild(option);
                                    select2.val(item.mapped_category_id);
                                }
                            })
                            .catch(() => {
                                // Fallback: set value without text
                                const option = new Option('', item.mapped_category_id, true, true);
                                selectElement.appendChild(option);
                                select2.val(item.mapped_category_id);
                            });
                    }

                // Handle change event - only when user actually changes the value
                let previousValue = item.mapped_category_id || null;
                let isInitializing = true;
                
                // Mark initialization as complete after a delay
                setTimeout(() => {
                    isInitializing = false;
                    previousValue = select2.val();
                }, 1000);
                
                select2.on('change', function() {
                    // Skip if we're still initializing
                    if (isInitializing) {
                        return;
                    }
                    
                    const currentValue = this.value ? parseInt(this.value) : null;
                    
                    // Only update if value actually changed and user selected something
                    if (currentValue && currentValue !== previousValue) {
                        previousValue = currentValue;
                        updateMapping(item.id, currentValue);
                    } else if (!currentValue) {
                        previousValue = null;
                    }
                });

                    // Store jQuery element for later destruction
                    rowCategorySelect2s[item.id] = select2;
                }
            });
        }, 100); // Small delay to ensure DOM is fully rendered

        attachListeners();
    }

    function renderPagination(links, meta) {
        if (meta.last_page <= 1) {
            elements.paginationContainer.classList.add('hidden');
            return;
        }

        elements.paginationContainer.classList.remove('hidden');

        let paginationHtml = '<div class="flex items-center justify-between"><div class="text-sm text-gray-700 dark:text-gray-300">';
        paginationHtml += `Toplam ${meta.total} kayıt, Sayfa ${meta.current_page} / ${meta.last_page}`;
        paginationHtml += '</div><div class="flex space-x-2">';

        // Previous button
        if (links.prev) {
            paginationHtml += `<button type="button" data-page="${meta.current_page - 1}" class="pagination-btn px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">Önceki</button>`;
        } else {
            paginationHtml += `<button type="button" disabled class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg opacity-50 cursor-not-allowed">Önceki</button>`;
        }

        // Page numbers
        const startPage = Math.max(1, meta.current_page - 2);
        const endPage = Math.min(meta.last_page, meta.current_page + 2);

        if (startPage > 1) {
            paginationHtml += `<button type="button" data-page="1" class="pagination-btn px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="px-3 py-2 text-sm">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            if (i === meta.current_page) {
                paginationHtml += `<button type="button" class="px-3 py-2 text-sm bg-blue-600 text-white rounded-lg">${i}</button>`;
            } else {
                paginationHtml += `<button type="button" data-page="${i}" class="pagination-btn px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">${i}</button>`;
            }
        }

        if (endPage < meta.last_page) {
            if (endPage < meta.last_page - 1) {
                paginationHtml += `<span class="px-3 py-2 text-sm">...</span>`;
            }
            paginationHtml += `<button type="button" data-page="${meta.last_page}" class="pagination-btn px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">${meta.last_page}</button>`;
        }

        // Next button
        if (links.next) {
            paginationHtml += `<button type="button" data-page="${meta.current_page + 1}" class="pagination-btn px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">Sonraki</button>`;
        } else {
            paginationHtml += `<button type="button" disabled class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg opacity-50 cursor-not-allowed">Sonraki</button>`;
        }

        paginationHtml += '</div></div>';
        elements.paginationContainer.innerHTML = paginationHtml;

        // Attach event listeners to pagination buttons
        elements.paginationContainer.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const page = parseInt(this.dataset.page);
                if (page && page > 0) {
                    loadData(page);
                }
            });
        });
    }

    function attachListeners() {
        // Remove existing listeners to prevent duplicates
        document.querySelectorAll('.item-checkbox').forEach(checkbox => {
            // Clone node to remove all event listeners
            const newCheckbox = checkbox.cloneNode(true);
            checkbox.parentNode.replaceChild(newCheckbox, checkbox);
            
            newCheckbox.addEventListener('change', function() {
                const itemId = parseInt(this.dataset.itemId);
                if (this.checked) {
                    selectedItems.add(itemId);
                } else {
                    selectedItems.delete(itemId);
                }
                updateBulkActions();
            });
        });

        // Remove and re-add selectAll listener to prevent duplicates
        const newSelectAll = elements.selectAll.cloneNode(true);
        elements.selectAll.parentNode.replaceChild(newSelectAll, elements.selectAll);
        elements.selectAll = newSelectAll;
        
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
    }

    function updateBulkActions() {
        const count = selectedItems.size;
        elements.selectedCount.textContent = count;
        elements.bulkActions.classList.toggle('hidden', count === 0);
    }

    function updateItemStatus(itemId, categoryId) {
        // Update status badge without reloading the entire table
        const row = document.querySelector(`tr:has(input[data-item-id="${itemId}"])`);
        if (row) {
            const statusCell = row.querySelector('td:last-child');
            if (statusCell) {
                statusCell.innerHTML = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300">MAPPED</span>';
            }
        }
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
                showToast('Eşleştirme kaydedildi');
                // Don't reload data, just update the status badge
                updateItemStatus(itemId, categoryId);
            } else {
                showToast('Eşleştirme kaydedilemedi', 'error');
            }
        } catch (error) {
            console.error('Error updating mapping:', error);
            showToast('Eşleştirme kaydedilirken hata oluştu', 'error');
        }
    }

    async function applyBulk() {
        if (!bulkCategorySelect2 || !bulkCategorySelect2.val() || selectedItems.size === 0) return;

        const categoryId = parseInt(bulkCategorySelect2.val());
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
                // Reload data after bulk update to refresh all statuses
                setTimeout(() => {
                    loadData(currentPage);
                }, 500);
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
        if (bulkCategorySelect2) {
            bulkCategorySelect2.val(null).trigger('change');
        }
        updateBulkActions();
    }

    // Event listeners
    elements.feedSourceSelect.addEventListener('change', function(e) {
        e.preventDefault();
        if (this.value) {
            loadData(1);
            initBulkCategorySelect();
        }
    });

    elements.parentCategorySelect.addEventListener('change', function(e) {
        e.preventDefault();
        parentCategoryId = this.value;
        if (elements.feedSourceSelect.value) {
            loadData(1);
            initBulkCategorySelect();
        }
    });

    elements.filterUnmappedOnly.addEventListener('change', function(e) {
        e.preventDefault();
        if (elements.feedSourceSelect.value) {
            loadData(1);
        }
    });

    elements.btnRefresh.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (elements.feedSourceSelect.value) {
            loadData(currentPage);
        }
    });

    elements.btnApplyBulk.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        applyBulk();
    });

    elements.btnClearSelection.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        clearSelection();
    });

    // Expose loadData to window for pagination
    window.xmlMappingApp = {
        loadData: loadData
    };

    // Initialize bulk category select
    initBulkCategorySelect();
})();
</script>

<style>
.select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
}
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    line-height: 38px;
}

/* Dark mode support for Select2 */
.dark .select2-container--bootstrap-5 .select2-selection {
    background-color: #374151;
    border-color: #4b5563;
    color: #f9fafb;
}

.dark .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    color: #f9fafb;
}

.dark .select2-container--bootstrap-5 .select2-dropdown {
    background-color: #374151;
    border-color: #4b5563;
}

.dark .select2-container--bootstrap-5 .select2-results__option {
    background-color: #374151;
    color: #f9fafb;
}

.dark .select2-container--bootstrap-5 .select2-results__option--highlighted {
    background-color: #1f2937;
    color: #f9fafb;
}

.dark .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
    background-color: #374151;
    border-color: #4b5563;
    color: #f9fafb;
}

/* Fix Select2 dropdown positioning after AJAX */
.select2-container {
    z-index: 9999;
}

/* Ensure Select2 dropdowns are properly styled after AJAX load */
.select2-container--bootstrap-5.select2-container--open .select2-dropdown {
    border-color: #4b5563;
}
</style>
@endsection
