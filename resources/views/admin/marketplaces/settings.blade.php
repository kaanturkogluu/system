@extends('admin.layouts.app')

@section('title', $marketplace->name . ' - Ayarlar')
@section('page-title', $marketplace->name . ' - Ayarlar')

@section('content')
<div class="max-w-4xl">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
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
                    <a href="{{ route('admin.marketplaces.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        Pazaryerleri
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">{{ $marketplace->name }}</span>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">Ayarlar</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.marketplaces.settings.update', $marketplace) }}" class="space-y-6">
            @csrf

            <!-- API Configuration Section -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">API Yapılandırması</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pazaryeri API entegrasyonu için gerekli bilgiler</p>
            </div>

            <!-- Base URL -->
            <div>
                <label for="settings_base_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Base URL
                </label>
                <input 
                    type="url" 
                    id="settings_base_url" 
                    name="settings[base_url]" 
                    value="{{ old('settings.base_url', $settings['base_url'] ?? '') }}"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    placeholder="https://api.example.com"
                >
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    API endpoint'inin temel URL'i
                </p>
            </div>

            <!-- Supplier ID -->
            <div>
                <label for="settings_supplier_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Supplier ID
                </label>
                <input 
                    type="text" 
                    id="settings_supplier_id" 
                    name="settings[supplier_id]" 
                    value="{{ old('settings.supplier_id', $settings['supplier_id'] ?? '') }}"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    placeholder="12345"
                >
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Pazaryeri tarafından sağlanan tedarikçi kimliği
                </p>
            </div>

            <!-- API Key -->
            <div>
                <label for="settings_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    API Key <span class="text-yellow-500 text-xs">(Hassas)</span>
                </label>
                <input 
                    type="password" 
                    id="settings_api_key" 
                    name="settings[api_key]" 
                    value="{{ old('settings.api_key', $settings['api_key'] ?? '') }}"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    placeholder="••••••••••••"
                >
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    API kimlik doğrulama anahtarı
                </p>
            </div>

            <!-- API Secret -->
            <div>
                <label for="settings_api_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    API Secret <span class="text-yellow-500 text-xs">(Hassas)</span>
                </label>
                <input 
                    type="password" 
                    id="settings_api_secret" 
                    name="settings[api_secret]" 
                    value="{{ old('settings.api_secret', $settings['api_secret'] ?? '') }}"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    placeholder="••••••••••••"
                >
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    API gizli anahtarı
                </p>
            </div>

            <!-- Additional Settings Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ek Ayarlar</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pazaryeri özel yapılandırmaları</p>
                </div>

                <!-- Environment -->
                <div>
                    <label for="settings_environment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Environment
                    </label>
                    <select 
                        id="settings_environment" 
                        name="settings[environment]" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Seçiniz</option>
                        <option value="production" {{ old('settings.environment', $settings['environment'] ?? '') === 'production' ? 'selected' : '' }}>Production</option>
                        <option value="sandbox" {{ old('settings.environment', $settings['environment'] ?? '') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
                        <option value="test" {{ old('settings.environment', $settings['environment'] ?? '') === 'test' ? 'selected' : '' }}>Test</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        API ortamı (production, sandbox, test)
                    </p>
                </div>

                <!-- Timeout -->
                <div>
                    <label for="settings_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Timeout (saniye)
                    </label>
                    <input 
                        type="number" 
                        id="settings_timeout" 
                        name="settings[timeout]" 
                        value="{{ old('settings.timeout', $settings['timeout'] ?? '30') }}"
                        min="1"
                        max="300"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        placeholder="30"
                    >
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        API istekleri için timeout süresi (varsayılan: 30 saniye)
                    </p>
                </div>

                <!-- Barcode Prefix -->
                <div>
                    <label for="settings_barcode_prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Barkod Ön Eki
                    </label>
                    <input 
                        type="text" 
                        id="settings_barcode_prefix" 
                        name="settings[barcode_prefix]" 
                        value="{{ old('settings.barcode_prefix', $settings['barcode_prefix'] ?? '') }}"
                        maxlength="20"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        placeholder="GNS"
                    >
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Pazaryerine gönderilecek ürün barkodlarına eklenecek ön ek (örn: GNS). Örnek: "4719072749927" → "GNS4719072749927"
                    </p>
                </div>

                <!-- Default Commission Rate -->
                <div>
                    <label for="settings_default_commission_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Genel Komisyon Oranı (%)
                    </label>
                    <input 
                        type="number" 
                        id="settings_default_commission_rate" 
                        name="settings[default_commission_rate]" 
                        value="{{ old('settings.default_commission_rate', $settings['default_commission_rate'] ?? '20') }}"
                        min="0"
                        max="100"
                        step="0.01"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        placeholder="20"
                    >
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Ürüne özel veya kategori bazlı komisyon girilmemişse kullanılacak genel komisyon oranı (varsayılan: 20%)
                    </p>
                </div>

                <!-- Default VAT Rate -->
                <div>
                    <label for="settings_default_vat_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        KDV Oranı (%)
                    </label>
                    <input 
                        type="number" 
                        id="settings_default_vat_rate" 
                        name="settings[default_vat_rate]" 
                        value="{{ old('settings.default_vat_rate', $settings['default_vat_rate'] ?? '20') }}"
                        min="0"
                        max="100"
                        step="1"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        placeholder="20"
                    >
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Ürünler için kullanılacak KDV oranı (varsayılan: 20%)
                    </p>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>Güvenlik Uyarısı:</strong> API Key ve API Secret gibi hassas bilgiler güvenli bir şekilde saklanmaktadır. Bu bilgileri paylaşmayın.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a 
                    href="{{ route('admin.marketplaces.index') }}"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-200"
                >
                    İptal
                </a>
                <button 
                    type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200"
                >
                    Ayarları Kaydet
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

