@extends('admin.layouts.app')

@section('title', $marketplace->name . ' - Kargo Şirketleri')
@section('page-title', $marketplace->name . ' - Kargo Şirketleri')

@section('content')
<div class="max-w-6xl space-y-6">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li>
                    <a href="{{ route('admin.marketplaces.index') }}" class="text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        Pazaryerleri
                    </a>
                </li>
                <li>
                    <span class="text-gray-500 dark:text-gray-400">/</span>
                </li>
                <li>
                    <a href="{{ route('admin.marketplaces.settings', $marketplace) }}" class="text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        {{ $marketplace->name }} - Ayarlar
                    </a>
                </li>
                <li>
                    <span class="text-gray-500 dark:text-gray-400">/</span>
                </li>
                <li>
                    <span class="text-gray-900 dark:text-white">Kargo Şirketleri</span>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Default Shipping Company Selection -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Varsayılan Kargo Şirketi</h3>
        
        <form method="POST" action="{{ route('admin.marketplaces.update-default-shipping-company', $marketplace) }}">
            @csrf
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Varsayılan Kargo Şirketi Seçin
                    </label>
                    <select 
                        name="default_shipping_company_mapping_id" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Varsayılan seçilmedi</option>
                        @foreach($shippingCompanyMappings as $mapping)
                            <option value="{{ $mapping->id }}" {{ $mapping->is_default ? 'selected' : '' }}>
                                {{ $mapping->shippingCompany->name }}
                                @if($mapping->external_name)
                                    ({{ $mapping->external_name }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Bu pazaryeri için varsayılan kargo şirketini seçin. Ürün gönderimlerinde otomatik olarak kullanılacaktır.
                    </p>
                </div>

                <div>
                    <button 
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                    >
                        Kaydet
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Shipping Company Mappings List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kargo Şirketi Eşleştirmeleri</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Bu pazaryeri için eşleştirilmiş kargo şirketleri</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Kargo Şirketi
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            External ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            External Code
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            External Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Vergi No
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Durum
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Varsayılan
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            İşlemler
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($shippingCompanyMappings as $mapping)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $mapping->shippingCompany->name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $mapping->shippingCompany->code }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $mapping->external_id ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $mapping->external_code ?? '-' }}</code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $mapping->external_name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $mapping->tax_number ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($mapping->status === 'active')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300">
                                        Aktif
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300">
                                        Pasif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($mapping->is_default)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300">
                                        Varsayılan
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a 
                                    href="{{ route('admin.shipping-companies.edit', $mapping->shippingCompany) }}" 
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                                >
                                    Düzenle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                Bu pazaryeri için kargo şirketi eşleştirmesi bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

