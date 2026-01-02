@extends('admin.layouts.app')

@section('title', $shippingCompany->name . ' - Düzenle')
@section('page-title', $shippingCompany->name . ' - Düzenle')

@section('content')
<div class="max-w-6xl space-y-6">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li>
                    <a href="{{ route('admin.shipping-companies.index') }}" class="text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        Kargo Şirketleri
                    </a>
                </li>
                <li>
                    <span class="text-gray-500 dark:text-gray-400">/</span>
                </li>
                <li>
                    <span class="text-gray-900 dark:text-white">{{ $shippingCompany->name }}</span>
                </li>
            </ol>
        </nav>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- General Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Genel Bilgiler</h3>
            
            <form method="POST" action="{{ route('admin.shipping-companies.update', $shippingCompany) }}">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kod
                        </label>
                        <input 
                            type="text" 
                            value="{{ $shippingCompany->code }}" 
                            disabled
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400"
                        >
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kod değiştirilemez</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ad <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            value="{{ old('name', $shippingCompany->name) }}"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Durum <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="status" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white @error('status') border-red-500 @enderror"
                        >
                            <option value="active" {{ old('status', $shippingCompany->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="passive" {{ old('status', $shippingCompany->status) === 'passive' ? 'selected' : '' }}>Pasif</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <button 
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200"
                        >
                            Güncelle
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Marketplace Mappings -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Pazaryeri Eşleştirmeleri</h3>
            
            <div class="space-y-4">
                @forelse($marketplaces as $marketplace)
                    @php
                        $mapping = $mappings->get($marketplace->id);
                    @endphp
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium text-gray-900 dark:text-white">{{ $marketplace->name }}</h4>
                            @if($mapping)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300">
                                    Eşleştirilmiş
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300">
                                    Eşleştirilmemiş
                                </span>
                            @endif
                        </div>

                        @if($mapping)
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">External ID:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $mapping->external_id ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">External Code:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $mapping->external_code ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">External Name:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $mapping->external_name ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Vergi No:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $mapping->tax_number ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Durum:</span>
                                    <span class="px-2 py-1 text-xs rounded-full {{ $mapping->status === 'active' ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300' }}">
                                        {{ $mapping->status === 'active' ? 'Aktif' : 'Pasif' }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.shipping-companies.update-mapping', $shippingCompany) }}" class="mt-4">
                            @csrf
                            <input type="hidden" name="marketplace_id" value="{{ $marketplace->id }}">
                            
                            <div class="space-y-2">
                                <input 
                                    type="number" 
                                    name="external_id" 
                                    value="{{ old('external_id', $mapping->external_id ?? '') }}"
                                    placeholder="External ID"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                >
                                <input 
                                    type="text" 
                                    name="external_code" 
                                    value="{{ old('external_code', $mapping->external_code ?? '') }}"
                                    placeholder="External Code"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                >
                                <input 
                                    type="text" 
                                    name="external_name" 
                                    value="{{ old('external_name', $mapping->external_name ?? '') }}"
                                    placeholder="External Name"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                >
                                <input 
                                    type="text" 
                                    name="tax_number" 
                                    value="{{ old('tax_number', $mapping->tax_number ?? '') }}"
                                    placeholder="Vergi No"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                >
                                <select 
                                    name="status" 
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="active" {{ old('status', $mapping->status ?? 'active') === 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="passive" {{ old('status', $mapping->status ?? 'active') === 'passive' ? 'selected' : '' }}>Pasif</option>
                                </select>
                                <div class="flex gap-2">
                                    <button 
                                        type="submit"
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-3 rounded-lg transition duration-200"
                                    >
                                        {{ $mapping ? 'Güncelle' : 'Ekle' }}
                                    </button>
                                    @if($mapping)
                                        <form method="POST" action="{{ route('admin.shipping-companies.delete-mapping', [$shippingCompany, $mapping]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit"
                                                onclick="return confirm('Bu eşleştirmeyi silmek istediğinizden emin misiniz?');"
                                                class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-2 px-3 rounded-lg transition duration-200"
                                            >
                                                Sil
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pazaryeri bulunamadı.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

