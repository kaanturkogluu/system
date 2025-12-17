@extends('admin.layouts.app')

@section('title', 'Marka Eşleştirmeleri')
@section('page-title', 'Marka Eşleştirmeleri')

@section('content')
<div class="space-y-6">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Marka Eşleştirmeleri</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Global markaları pazaryeri markalarına eşleştirin</p>
        </div>
        <div id="autoMapButtonContainer" class="hidden">
            <button 
                id="autoMapButton"
                type="button"
                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                %100 Eşleşenleri Otomatik Eşleştir
            </button>
        </div>
    </div>

    <!-- Auto Map Result Message -->
    <div id="autoMapResult" class="hidden"></div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.brand-mappings.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Marka Ara
                </label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    value="{{ request('search') }}"
                    placeholder="Marka adı..."
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
            </div>

            <!-- Marketplace Filter -->
            <div>
                <label for="marketplace_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Pazaryeri
                </label>
                <select 
                    id="marketplace_id" 
                    name="marketplace_id" 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    onchange="handleMarketplaceChange()"
                >
                    <option value="">Tümü</option>
                    @foreach($marketplaces as $mp)
                        <option value="{{ $mp->id }}" data-slug="{{ $mp->slug }}" {{ request('marketplace_id') == $mp->id ? 'selected' : '' }}>
                            {{ $mp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Mapping Status Filter -->
            <div>
                <label for="mapping" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Eşleştirme Durumu
                </label>
                <select 
                    id="mapping" 
                    name="mapping" 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
                    <option value="all" {{ request('mapping') == 'all' ? 'selected' : '' }}>Tümü</option>
                    <option value="mapped" {{ request('mapping') == 'mapped' ? 'selected' : '' }}>Eşleştirilmiş</option>
                    <option value="unmapped" {{ request('mapping') == 'unmapped' ? 'selected' : '' }}>Eşleştirilmemiş</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Marka Durumu
                </label>
                <select 
                    id="status" 
                    name="status" 
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Tümü</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Pasif</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="md:col-span-4 flex justify-end space-x-2">
                <a 
                    href="{{ route('admin.brand-mappings.index') }}"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200"
                >
                    Temizle
                </a>
                <button 
                    type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200"
                >
                    Filtrele
                </button>
            </div>
        </form>
    </div>

    <!-- Brands Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Marka Adı
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Normalized Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Durum
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Pazaryeri Eşleştirmeleri
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            İşlemler
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($brands as $brand)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $brand->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-600 dark:text-gray-400">
                                    {{ $brand->normalized_name ?? '—' }}
                                </code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $brand->status === 'active' ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                                    {{ $brand->status === 'active' ? 'Aktif' : 'Pasif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @foreach($marketplaces as $marketplace)
                                        @php
                                            $mapping = $mappingStatuses[$brand->id][$marketplace->id] ?? null;
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $mapping ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}" title="{{ $mapping ? 'Eşleştirilmiş: ' . $mapping->marketplace_brand_name : 'Eşleştirilmemiş' }}">
                                            {{ $marketplace->name }}: {{ $mapping ? '✓' : '✗' }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    @foreach($marketplaces as $marketplace)
                                        @php
                                            $mapping = $mappingStatuses[$brand->id][$marketplace->id] ?? null;
                                        @endphp
                                        @if($mapping)
                                            <form 
                                                method="POST" 
                                                action="{{ route('admin.brand-mappings.destroy', ['brand' => $brand, 'marketplace' => $marketplace]) }}"
                                                onsubmit="return confirm('{{ $marketplace->name }} eşleştirmesini silmek istediğinizden emin misiniz?');"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button 
                                                    type="submit"
                                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-xs"
                                                    title="Eşleştirmeyi Sil"
                                                >
                                                    {{ $marketplace->slug }} Sil
                                                </button>
                                            </form>
                                        @else
                                            <a 
                                                href="{{ route('admin.brand-mappings.search-results', ['brand' => $brand, 'marketplace' => $marketplace]) }}" 
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 text-xs"
                                                title="{{ $marketplace->name }}'e Eşleştir"
                                            >
                                                {{ $marketplace->slug }} Eşleştir
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Marka bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($brands->hasPages())
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 border-t border-gray-200 dark:border-gray-600">
                {{ $brands->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    // Handle marketplace selection change
    function handleMarketplaceChange() {
        const marketplaceSelect = document.getElementById('marketplace_id');
        const marketplaceId = marketplaceSelect.value;
        const autoMapButtonContainer = document.getElementById('autoMapButtonContainer');
        const autoMapResult = document.getElementById('autoMapResult');

        if (marketplaceId) {
            autoMapButtonContainer.classList.remove('hidden');
        } else {
            autoMapButtonContainer.classList.add('hidden');
            autoMapResult.classList.add('hidden');
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        handleMarketplaceChange();

        // Auto map button click handler
        const autoMapButton = document.getElementById('autoMapButton');
        if (autoMapButton) {
            autoMapButton.addEventListener('click', function() {
                performAutoMap();
            });
        }
    });

    // Perform auto mapping
    function performAutoMap() {
        const marketplaceSelect = document.getElementById('marketplace_id');
        const marketplaceId = marketplaceSelect.value;
        const selectedOption = marketplaceSelect.options[marketplaceSelect.selectedIndex];
        const marketplaceSlug = selectedOption.getAttribute('data-slug');

        if (!marketplaceId) {
            alert('Lütfen bir pazaryeri seçin.');
            return;
        }

        if (!confirm(`"${selectedOption.text}" pazaryeri için %100 eşleşen markaları otomatik olarak eşleştirmek istediğinizden emin misiniz?`)) {
            return;
        }

        const autoMapButton = document.getElementById('autoMapButton');
        const autoMapResult = document.getElementById('autoMapResult');

        // Disable button and show loading
        autoMapButton.disabled = true;
        autoMapButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            İşleniyor...
        `;

        // Make AJAX request
        fetch(`{{ route('admin.brand-mappings.auto-map') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                marketplace_id: marketplaceId
            })
        })
        .then(response => response.json())
        .then(data => {
            // Re-enable button
            autoMapButton.disabled = false;
            autoMapButton.innerHTML = `
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                %100 Eşleşenleri Otomatik Eşleştir
            `;

            // Show result
            autoMapResult.classList.remove('hidden');
            
            if (data.success) {
                autoMapResult.className = 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6';
                autoMapResult.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                ${data.message}
                            </p>
                            <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Eşleştirilen: <strong>${data.data.matched}</strong> marka</li>
                                    <li>Atlanan: <strong>${data.data.skipped}</strong> marka</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                `;

                // Reload page after 2 seconds to show updated mappings
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                autoMapResult.className = 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6';
                autoMapResult.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                ${data.message}
                            </p>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            // Re-enable button
            autoMapButton.disabled = false;
            autoMapButton.innerHTML = `
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                %100 Eşleşenleri Otomatik Eşleştir
            `;

            // Show error
            autoMapResult.classList.remove('hidden');
            autoMapResult.className = 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6';
            autoMapResult.innerHTML = `
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">
                            Bir hata oluştu: ${error.message}
                        </p>
                    </div>
                </div>
            `;
        });
    }
</script>
@endsection

