@extends('admin.layouts.app')

@section('title', 'Marka Eşleştirme - ' . $brand->name)
@section('page-title', 'Marka Eşleştirme: ' . $brand->name)

@section('content')
<div class="max-w-4xl">
    <!-- Success/Error Messages -->
    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
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

    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.brand-mappings.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        Marka Eşleştirmeleri
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">{{ $brand->name }}</span>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">{{ $marketplace->name }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Global Brand Info -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Global Marka</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $brand->name }}</p>
                @if($brand->normalized_name)
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        <code>{{ $brand->normalized_name }}</code>
                    </p>
                @endif
            </div>
            <div class="text-right">
                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $brand->status === 'active' ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                    {{ $brand->status === 'active' ? 'Aktif' : 'Pasif' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Marketplace Info -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200">{{ $marketplace->name }}</h3>
                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">Pazaryeri marka eşleştirmesi</p>
            </div>
        </div>
    </div>

    <!-- Existing Mapping Warning -->
    @if($existingMapping)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        <strong>Uyarı:</strong> Bu marka için zaten bir eşleştirme mevcut: 
                        <strong>{{ $existingMapping->marketplace_brand_name }}</strong> (ID: {{ $existingMapping->marketplace_brand_id }})
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Search Results -->
    @if(!$searchResult)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                        Pazaryeri Arama Sonuçları Bulunamadı
                    </h3>
                    <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                        Bu marka için {{ $marketplace->name }} pazaryerinde arama sonuçları bulunmamaktadır. 
                        Önce marka arama sonuçlarını yüklemek için <code>php artisan app:trendyol-brand-sync</code> komutunu çalıştırın.
                    </p>
                </div>
            </div>
        </div>
    @elseif(empty($marketplaceBrands))
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Arama Sonucu Boş
                    </h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                        {{ $marketplace->name }} pazaryerinde "{{ $searchResult->query_name }}" için marka bulunamadı.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ $marketplace->name }} Marka Sonuçları
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                Arama sorgusu: <strong>{{ $searchResult->query_name }}</strong>
            </p>

            <form method="POST" action="{{ route('admin.brand-mappings.store', ['brand' => $brand, 'marketplace' => $marketplace]) }}" id="mappingForm">
                @csrf
                
                @if($existingMapping)
                    <input type="hidden" name="confirm_overwrite" value="1">
                @endif

                <div class="space-y-3">
                    @foreach($marketplaceBrands as $index => $marketplaceBrand)
                        <label class="flex items-center p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200 {{ $existingMapping && $existingMapping->marketplace_brand_id == $marketplaceBrand['id'] ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            <input 
                                type="radio" 
                                name="marketplace_brand_id" 
                                value="{{ $marketplaceBrand['id'] }}"
                                data-brand-name="{{ $marketplaceBrand['name'] }}"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600"
                                {{ $existingMapping && $existingMapping->marketplace_brand_id == $marketplaceBrand['id'] ? 'checked' : '' }}
                                required
                            >
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $marketplaceBrand['name'] }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            ID: <code>{{ $marketplaceBrand['id'] }}</code>
                                        </p>
                                    </div>
                                    @if($existingMapping && $existingMapping->marketplace_brand_id == $marketplaceBrand['id'])
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300">
                                            Mevcut Eşleştirme
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <!-- Hidden field for brand name (will be set by JS) -->
                <input type="hidden" name="marketplace_brand_name" id="marketplace_brand_name" required>

                <!-- Buttons -->
                <div class="flex items-center justify-end space-x-4 pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                    <a 
                        href="{{ route('admin.brand-mappings.index') }}"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200"
                    >
                        İptal
                    </a>
                    <button 
                        type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200"
                        onclick="return confirmMapping()"
                    >
                        {{ $existingMapping ? 'Eşleştirmeyi Güncelle' : 'Eşleştir' }}
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

<script>
    // Set brand name when radio is selected
    document.querySelectorAll('input[name="marketplace_brand_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('marketplace_brand_name').value = this.dataset.brandName;
        });
    });

    // Set initial value if one is already selected
    const selectedRadio = document.querySelector('input[name="marketplace_brand_id"]:checked');
    if (selectedRadio) {
        document.getElementById('marketplace_brand_name').value = selectedRadio.dataset.brandName;
    }

    // Confirm mapping
    function confirmMapping() {
        const selectedRadio = document.querySelector('input[name="marketplace_brand_id"]:checked');
        if (!selectedRadio) {
            alert('Lütfen bir pazaryeri markası seçin.');
            return false;
        }

        const brandName = selectedRadio.dataset.brandName;
        const globalBrand = '{{ $brand->name }}';
        const marketplace = '{{ $marketplace->name }}';
        
        @if($existingMapping)
            return confirm(`"${globalBrand}" markasını "${marketplace}" pazaryerinde "${brandName}" olarak güncellemek istediğinizden emin misiniz?`);
        @else
            return confirm(`"${globalBrand}" markasını "${marketplace}" pazaryerinde "${brandName}" olarak eşleştirmek istediğinizden emin misiniz?`);
        @endif
    }
</script>
@endsection

