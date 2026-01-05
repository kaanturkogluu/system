@extends('admin.layouts.app')

@section('title', 'Gönderilmeye Hazır Ürünler')
@section('page-title', 'Gönderilmeye Hazır Ürünler')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Gönderilmeye Hazır Ürünler</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">API formatına uygun hazırlanmış ürünler</p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Toplam: <span class="font-semibold">{{ $products->total() }}</span> ürün
            </div>
            <button 
                type="button"
                id="check-status-btn"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition duration-200 flex items-center space-x-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>IMPORTED Ürünleri Kontrol Et</span>
            </button>
            <button 
                type="button"
                id="send-products-btn"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-200 flex items-center space-x-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <span>Trendyol'a Gönder</span>
            </button>
        </div>
    </div>

    <!-- Status Check Result -->
    <div id="status-check-result" class="hidden bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div id="status-check-content"></div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('admin.ready-products.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                <input 
                    type="text" 
                    name="sku" 
                    value="{{ request('sku') }}"
                    placeholder="SKU ile ara..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                <select 
                    name="category_id"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                >
                    <option value="">Tüm Kategoriler</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button 
                    type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition duration-200"
                >
                    Filtrele
                </button>
                <a 
                    href="{{ route('admin.ready-products.index') }}"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition duration-200"
                >
                    Temizle
                </a>
            </div>
        </form>
    </div>

    <!-- Products List -->
    <div class="space-y-4">
        @forelse($productsWithApiData as $item)
        @php
            $product = $item['product'];
            $apiData = $item['api_data'];
            $priceDetails = $item['price_details'] ?? null;
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" data-product-id="{{ $product->id }}">
            <!-- Product Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-4 flex-1">
                        @if($product->images && $product->images->count() > 0)
                            <img 
                                src="{{ $product->images->first()->url }}" 
                                alt="{{ $product->title }}"
                                class="h-16 w-16 object-cover rounded"
                                onerror="this.style.display='none'"
                            >
                        @endif
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $product->title }}</h3>
                            <div class="flex items-center space-x-4 mt-1 text-sm text-gray-600 dark:text-gray-400">
                                <span>SKU: <span class="font-mono text-blue-600 dark:text-blue-400">{{ $product->sku }}</span></span>
                                <span>ID: {{ $product->id }}</span>
                                @if($product->brand)
                                    <span>Marka: {{ $product->brand->name }}</span>
                                @endif
                                @if($product->category)
                                    <span>Kategori: {{ $product->category->name }}</span>
                                @endif
                                @if($product->category && $product->category->trendyolCategory)
                                    <span>Trendyol Kategori ID: <span class="font-mono text-green-600 dark:text-green-400">{{ $product->category->trendyolCategory->marketplace_category_id }}</span></span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        @php
                            $lastRequest = \App\Models\TrendyolProductRequest::where('product_id', $product->id)
                                ->latest()
                                ->first();
                        @endphp
                        @if($lastRequest)
                            <div class="flex items-center space-x-1 px-2 py-1 rounded text-xs
                                @if($lastRequest->status === 'success') bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                @elseif($lastRequest->status === 'failed') bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                @elseif($lastRequest->status === 'sent') bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200
                                @endif">
                                @if($lastRequest->status === 'success' || $lastRequest->status === 'sent')
                                    <a 
                                        href="{{ route('admin.ready-products.batch-details', $product->id) }}"
                                        class="flex items-center space-x-1 hover:opacity-80 transition-opacity cursor-pointer"
                                        title="Batch Detaylarını Görüntüle"
                                    >
                                        <span>{{ ucfirst($lastRequest->status) }}</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                @else
                                    <button 
                                        type="button"
                                        onclick="showRequestHistory({{ $product->id }})"
                                        class="flex items-center space-x-1 hover:opacity-80 transition-opacity"
                                        title="İstek Geçmişini Görüntüle"
                                    >
                                        <span>{{ ucfirst($lastRequest->status) }}</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                @endif
                                @if($lastRequest->batch_request_id && $lastRequest->status !== 'success' && $lastRequest->status !== 'failed')
                                    <button 
                                        type="button"
                                        onclick="checkBatchStatus('{{ $lastRequest->batch_request_id }}')"
                                        class="ml-1 underline hover:no-underline"
                                        title="Durumu Kontrol Et"
                                    >
                                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endif
                        <button 
                            type="button"
                            onclick="sendSingleProduct({{ $product->id }}, this)"
                            class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded transition duration-200 flex items-center space-x-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            <span>Gönder</span>
                        </button>
                        <a 
                            href="{{ route('admin.xml-products.show', $product->id) }}"
                            class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded transition duration-200"
                        >
                            Detay
                        </a>
                    </div>
                </div>
            </div>

            <!-- API Data -->
            <div class="p-4" x-data="{ showJson: false }">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-3">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">API Formatı</h4>
                        @if($priceDetails)
                        <button 
                            onclick="showPriceDetailsModal({{ $product->id }})"
                            class="text-xs px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg transition duration-200 flex items-center space-x-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span>Fiyat Detay</span>
                        </button>
                        @endif
                    </div>
                    <button 
                        @click="showJson = !showJson"
                        class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
                    >
                        <span x-show="!showJson">JSON Göster</span>
                        <span x-show="showJson" x-cloak>JSON Gizle</span>
                    </button>
                </div>

                <!-- Collapsible JSON -->
                <div x-show="showJson" x-cloak class="mt-3">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-4 overflow-x-auto">
                        @php
                            $jsonData = $apiData;
                            // priceCalculationDetails'ı JSON'dan kaldır
                            unset($jsonData['priceCalculationDetails']);
                        @endphp
                        <pre class="text-xs text-gray-800 dark:text-gray-200 font-mono">{{ json_encode(['items' => [$jsonData]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Barkod</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ is_string($apiData['barcode']) && $apiData['barcode'] === 'Sistemde Veri yok' ? '❌ Yok' : '✅ ' . $apiData['barcode'] }}
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Marka ID</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ is_string($apiData['brandId']) && $apiData['brandId'] === 'Sistemde Veri yok' ? '❌ Yok' : '✅ ' . $apiData['brandId'] }}
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Kategori ID</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ is_string($apiData['categoryId']) && $apiData['categoryId'] === 'Sistemde Veri yok' ? '❌ Yok' : '✅ ' . $apiData['categoryId'] }}
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Trendyol Kategori ID</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            @if($product->category && $product->category->trendyolCategory)
                                <span class="text-green-600 dark:text-green-400">✅ {{ $product->category->trendyolCategory->marketplace_category_id }}</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">❌ Yok</span>
                            @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Trendyol Kategori ID</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            @if($product->category && $product->category->trendyolCategory)
                                <span class="text-green-600 dark:text-green-400">✅ {{ $product->category->trendyolCategory->marketplace_category_id }}</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">❌ Yok</span>
                            @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Stok</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ is_string($apiData['quantity']) && $apiData['quantity'] === 'Sistemde Veri yok' ? '❌ Yok' : '✅ ' . $apiData['quantity'] }}
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Satış Fiyatı (TRY)</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            @if(is_string($apiData['salePrice']) && $apiData['salePrice'] === 'Sistemde Veri yok')
                                ❌ Yok
                            @else
                                ✅ {{ number_format((float)$apiData['salePrice'], 2, ',', '.') }} ₺
                            @endif
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">KDV Oranı</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $apiData['vatRate'] }}%
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded p-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Kategori Komisyon Oranı</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            @if($product->category && $product->category->commission_rate !== null)
                                {{ number_format((float)$product->category->commission_rate, 2, ',', '.') }}%
                            @else
                                20.00% <span class="text-xs text-gray-400">(Varsayılan)</span>
                            @endif
                        </div>
                    </div>
                </div>


                <!-- Attributes Summary -->
                <div class="mt-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                        Zorunlu Özellikler ({{ count($apiData['attributes']) }} adet)
                    </div>
                    @if(count($apiData['attributes']) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($apiData['attributes'] as $attr)
                                <div class="bg-gray-50 dark:bg-gray-900 rounded p-2 text-xs">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        Attribute ID: {{ $attr['attributeId'] }}
                                    </div>
                                    <div class="text-gray-600 dark:text-gray-400 mt-1">
                                        @if(isset($attr['attributeValueId']))
                                            <span class="text-green-600 dark:text-green-400">✅ Value ID: {{ $attr['attributeValueId'] }}</span>
                                        @elseif(isset($attr['customAttributeValue']))
                                            <span class="{{ $attr['customAttributeValue'] === 'Sistemde Veri yok' ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                                                {{ $attr['customAttributeValue'] === 'Sistemde Veri yok' ? '❌ Sistemde Veri yok' : '📝 ' . $attr['customAttributeValue'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 rounded p-2">
                            ⚠️ Bu kategori için zorunlu özellik tanımlanmamış
                        </div>
                    @endif
                </div>

                <!-- Images Summary -->
                <div class="mt-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                        Resimler ({{ count($apiData['images']) }} adet)
                    </div>
                    @if(count($apiData['images']) > 0)
                        <div class="flex space-x-2 overflow-x-auto">
                            @foreach($apiData['images'] as $image)
                                <img 
                                    src="{{ $image['url'] }}" 
                                    alt="Ürün resmi"
                                    class="h-16 w-16 object-cover rounded border border-gray-200 dark:border-gray-700"
                                    onerror="this.style.display='none'"
                                >
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 rounded p-2">
                            ⚠️ Ürün için resim bulunamadı
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="font-medium text-gray-900 dark:text-white">Gönderilmeye hazır ürün bulunamadı</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Henüz READY durumunda ürün yok veya filtrelerinize uygun ürün bulunamadı.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($products->hasPages())
    <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 px-4 py-3">
        <div class="flex-1 flex justify-between sm:hidden">
            @if($products->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-400 dark:text-gray-600 bg-white dark:bg-gray-800 cursor-not-allowed">
                    Önceki
                </span>
            @else
                <a href="{{ $products->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Önceki
                </a>
            @endif

            @if($products->hasMorePages())
                <a href="{{ $products->nextPageUrl() }}" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Sonraki
                </a>
            @else
                <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-400 dark:text-gray-600 bg-white dark:bg-gray-800 cursor-not-allowed">
                    Sonraki
                </span>
            @endif
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    <span class="font-medium">{{ $products->firstItem() }}</span>
                    -
                    <span class="font-medium">{{ $products->lastItem() }}</span>
                    arası, toplam
                    <span class="font-medium">{{ $products->total() }}</span>
                    ürün
                </p>
            </div>
            <div>
                {{ $products->links() }}
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Request Details Modal -->
<div id="request-details-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
    <div class="bg-white dark:bg-gray-800 w-full h-full flex flex-col">
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between z-10 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">İstek Yanıt Detayları</h3>
            <div class="flex items-center space-x-3">
                <button 
                    id="refresh-batch-status-btn"
                    onclick="refreshBatchStatus()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition duration-200 flex items-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    title="Batch durumunu tekrar sorgula"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Tekrar Sorgula</span>
                </button>
                <button 
                    onclick="closeRequestDetailsModal()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <div id="request-details-content">
                <!-- İçerik JavaScript ile doldurulacak -->
            </div>
        </div>
    </div>
</div>

<!-- Fiyat Detay Modal -->
<div id="price-details-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Fiyat Hesaplama Detayları</h3>
            <button 
                onclick="closePriceDetailsModal()"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6" id="price-details-content">
            <!-- İçerik JavaScript ile doldurulacak -->
        </div>
    </div>
</div>

<script>
// Fiyat detayları verilerini sakla
const priceDetailsData = {
    @foreach($productsWithApiData as $item)
        @if($item['price_details'])
        {{ $item['product']->id }}: @json($item['price_details']),
        @endif
    @endforeach
};

function showPriceDetailsModal(productId) {
    const details = priceDetailsData[productId];
    if (!details) {
        alert('Fiyat detayları bulunamadı');
        return;
    }
    
    const modal = document.getElementById('price-details-modal');
    const content = document.getElementById('price-details-content');
    
    let html = `
        <div class="space-y-4">
            <!-- Base Price -->
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border-l-4 border-blue-500">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white">1. Base Fiyat (Fiyat_Ozel)</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">XML'den gelen Fiyat_Ozel değerinin TRY'ye çevrilmiş hali</p>
                    </div>
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">${formatPrice(details.base_price)} ₺</span>
                </div>
            </div>
            
            <!-- Kategori Komisyon Oranı -->
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border-l-4 border-green-500">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white">2. Kategori Komisyon Oranı Ekleme <span class="text-xs font-normal text-gray-500">(Sistem Komisyonu)</span></h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Kategori Komisyon Oranı: <span class="font-semibold">${formatNumber(details.category_commission_rate)}%</span> 
                            <span class="text-gray-400">[${details.category_commission_rate_source}]</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Base fiyat üzerine kategori komisyon oranı eklenir: ${formatPrice(details.base_price)} × (1 + ${formatNumber(details.category_commission_rate)}%) = ${formatPrice(details.price_with_category_commission)}
                        </p>
                    </div>
                    <span class="text-lg font-bold text-green-600 dark:text-green-400">+ ${formatPrice(details.category_commission_amount)} ₺</span>
                </div>
                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Kategori Komisyon ile Fiyat:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">${formatPrice(details.price_with_category_commission)} ₺</span>
                    </div>
                </div>
            </div>
            
            <!-- KDV -->
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border-l-4 border-orange-500">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white">3. KDV Ekleme</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            KDV Oranı: <span class="font-semibold">${details.vat_rate}%</span> 
                            <span class="text-gray-400">[${details.vat_rate_source}]</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Kategori komisyon ile fiyat üzerinden KDV hesaplanır: ${formatPrice(details.price_with_category_commission)} × ${details.vat_rate}% = ${formatPrice(details.vat_amount)}
                        </p>
                    </div>
                    <span class="text-lg font-bold text-orange-600 dark:text-orange-400">+ ${formatPrice(details.vat_amount)} ₺</span>
                </div>
                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">KDV ile Fiyat:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">${formatPrice(details.price_with_vat)} ₺</span>
                    </div>
                </div>
            </div>
            
            <!-- Pazaryeri Kategori Komisyonu -->
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border-l-4 border-indigo-500">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white">4. Pazaryeri Kategori Komisyonu Ekleme <span class="text-xs font-normal text-gray-500">(Pazaryeri Komisyonu)</span></h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Pazaryeri Kategori Komisyon Oranı: <span class="font-semibold">${formatNumber(details.marketplace_category_commission_rate || 0)}%</span> 
                            <span class="text-gray-400">[${details.marketplace_category_commission_source || 'Uygulanmadı'}]</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            ${details.marketplace_category_commission_rate > 0 ? 
                                `KDV ile fiyat üzerinden pazaryeri kategori komisyonu hesaplanır: ${formatPrice(details.price_with_vat)} × ${formatNumber(details.marketplace_category_commission_rate)}% = ${formatPrice(details.marketplace_category_commission_amount)}` :
                                `Pazaryeri kategori komisyonu tanımlanmamış, bu adım atlanır.`
                            }
                        </p>
                    </div>
                    <span class="text-lg font-bold ${details.marketplace_category_commission_rate > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500'}">+ ${formatPrice(details.marketplace_category_commission_amount || 0)} ₺</span>
                </div>
                <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Pazaryeri Komisyon ile Fiyat:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">${formatPrice(details.price_with_marketplace_commission || details.price_with_vat)} ₺</span>
                    </div>
                </div>
            </div>
            
            <!-- Kargo -->
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border-l-4 border-red-500">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white">5. Kargo Ekleme</h5>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sabit kargo ücreti (100 TL)</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            ${details.marketplace_category_commission_rate > 0 
                                ? `Pazaryeri komisyon ile fiyata kargo eklenir: ${formatPrice(details.price_with_marketplace_commission)} + ${formatPrice(details.cargo_fee)} = ${formatPrice(details.final_price)}`
                                : `KDV ile fiyata kargo eklenir: ${formatPrice(details.price_with_vat)} + ${formatPrice(details.cargo_fee)} = ${formatPrice(details.final_price)}`
                            }
                        </p>
                    </div>
                    <span class="text-lg font-bold text-red-600 dark:text-red-400">+ ${formatPrice(details.cargo_fee)} ₺</span>
                </div>
            </div>
            
            <!-- Final Price -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-lg p-6 mt-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h5 class="text-lg font-bold text-white">FİNAL SATIŞ FİYATI</h5>
                        <p class="text-xs text-blue-100 mt-1">Tüm eklemeler yapıldıktan sonraki nihai fiyat</p>
                    </div>
                    <span class="text-3xl font-bold text-white">${formatPrice(details.final_price)} ₺</span>
                </div>
            </div>
        </div>
    `;
    
    content.innerHTML = html;
    modal.classList.remove('hidden');
}

function closePriceDetailsModal() {
    document.getElementById('price-details-modal').classList.add('hidden');
}

function formatPrice(price) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
}

function formatNumber(num) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
}

// Modal dışına tıklanınca kapat
document.getElementById('price-details-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closePriceDetailsModal();
    }
});
document.addEventListener('DOMContentLoaded', function() {
    console.log('Ready products page loaded');
    const checkStatusBtn = document.getElementById('check-status-btn');
    const statusCheckResult = document.getElementById('status-check-result');
    const statusCheckContent = document.getElementById('status-check-content');

    console.log('Button element:', checkStatusBtn);

    if (checkStatusBtn) {
        checkStatusBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Button clicked');
            
            const btnText = checkStatusBtn.querySelector('span');
            const originalText = btnText ? btnText.textContent : 'IMPORTED Ürünleri Kontrol Et';
            
            // Disable button
            checkStatusBtn.disabled = true;
            if (btnText) {
                btnText.textContent = 'Kontrol ediliyor...';
            }
            
            // Get category_id from filter
            const categoryId = new URLSearchParams(window.location.search).get('category_id') || '';
            
            console.log('Making request to:', '{{ route("admin.ready-products.check-status") }}');
            
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
            
            // Make request
            fetch('{{ route("admin.ready-products.check-status") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    category_id: categoryId
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                // Show result
                if (statusCheckResult) {
                    statusCheckResult.classList.remove('hidden');
                }
                
                let html = `
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Kontrol Sonucu</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${data.message}</p>
                    </div>
                `;
                
                if (data.details && data.details.length > 0) {
                    html += `
                        <div class="max-h-96 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">SKU</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Başlık</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Durum</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Eksik Özellikler</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    `;
                    
                    data.details.forEach(item => {
                        const statusClass = item.status.includes('READY') 
                            ? 'text-green-600 dark:text-green-400' 
                            : 'text-yellow-600 dark:text-yellow-400';
                        
                        html += `
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">${item.sku}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">${item.title}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">${item.category}</td>
                                <td class="px-4 py-3 text-sm ${statusClass}">${item.status}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    ${item.missing && item.missing.length > 0 
                                        ? '<ul class="list-disc list-inside text-red-600 dark:text-red-400">' + 
                                          item.missing.map(m => '<li>' + m + '</li>').join('') + 
                                          '</ul>' 
                                        : '-'}
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                }
                
                if (statusCheckContent) {
                    statusCheckContent.innerHTML = html;
                }
                
                // Reload page after 2 seconds if products were updated
                if (data.updated > 0) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (statusCheckResult) {
                    statusCheckResult.classList.remove('hidden');
                }
                if (statusCheckContent) {
                    statusCheckContent.innerHTML = `
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <p class="text-red-800 dark:text-red-400">Hata: ${error.message}</p>
                            <p class="text-red-600 dark:text-red-500 text-sm mt-2">Konsolu kontrol edin (F12)</p>
                        </div>
                    `;
                }
            })
            .finally(() => {
                // Re-enable button
                checkStatusBtn.disabled = false;
                if (btnText) {
                    btnText.textContent = originalText;
                }
            });
        });
    }

    // Send Products to Trendyol
    const sendProductsBtn = document.getElementById('send-products-btn');
    if (sendProductsBtn) {
        sendProductsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get all product IDs from the page
            const productIds = Array.from(document.querySelectorAll('[data-product-id]')).map(el => {
                return parseInt(el.getAttribute('data-product-id'));
            });

            if (productIds.length === 0) {
                alert('Gönderilecek ürün bulunamadı.');
                return;
            }

            if (!confirm(`${productIds.length} ürün Trendyol API'ye gönderilecek. Devam etmek istiyor musunuz?`)) {
                return;
            }

            const btnText = sendProductsBtn.querySelector('span');
            const originalText = btnText ? btnText.textContent : 'Trendyol\'a Gönder';
            
            // Disable button
            sendProductsBtn.disabled = true;
            if (btnText) {
                btnText.textContent = 'Gönderiliyor...';
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            fetch('{{ route("admin.ready-products.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    product_ids: productIds
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Ürünler başarıyla gönderildi!\nBatch Request ID: ${data.batchRequestId}`);
                    
                    // Sayfayı yenile (durum güncellemesi için)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    
                    // Check batch status (opsiyonel - arka planda)
                    if (data.batchRequestId) {
                        setTimeout(() => {
                            checkBatchStatus(data.batchRequestId);
                        }, 2000);
                    }
                } else {
                    alert('Hata: ' + (data.message || 'Ürünler gönderilemedi.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Bir hata oluştu: ' + error.message);
            })
            .finally(() => {
                sendProductsBtn.disabled = false;
                if (btnText) {
                    btnText.textContent = originalText;
                }
            });
        });
    }

    // Check Batch Status (Global function)
    window.checkBatchStatus = function(batchRequestId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        
        fetch(`{{ route("admin.ready-products.batch-status", ":batchRequestId") }}`.replace(':batchRequestId', batchRequestId), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show response in a modal or alert
                const responseText = JSON.stringify(data.data, null, 2);
                alert('Batch Durumu:\n\n' + responseText);
                
                // Sayfayı yenile (durum güncellemesi için)
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Hata: ' + (data.message || 'Batch durumu kontrol edilemedi.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Batch durumu kontrol edilirken bir hata oluştu: ' + error.message);
        });
    }

    // Send Single Product
    window.sendSingleProduct = function(productId, buttonElement) {
        if (!confirm('Bu ürün Trendyol API\'ye gönderilecek. Devam etmek istiyor musunuz?')) {
            return;
        }

        const button = buttonElement || event.target.closest('button');
        const originalText = button.querySelector('span')?.textContent || 'Gönder';
        const buttonIcon = button.querySelector('svg');
        
        // Disable button
        button.disabled = true;
        if (button.querySelector('span')) {
            button.querySelector('span').textContent = 'Gönderiliyor...';
        }
        if (buttonIcon) {
            buttonIcon.classList.add('animate-spin');
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        fetch('{{ route("admin.ready-products.send") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                product_ids: [productId]
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Ürün başarıyla gönderildi!\nBatch Request ID: ${data.batchRequestId}`);
                
                // Sayfayı yenile (durum güncellemesi için)
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                
                // Check batch status (opsiyonel - arka planda)
                if (data.batchRequestId) {
                    setTimeout(() => {
                        checkBatchStatus(data.batchRequestId);
                    }, 2000);
                }
            } else {
                alert('Hata: ' + (data.message || 'Ürün gönderilemedi.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu: ' + error.message);
        })
        .finally(() => {
            button.disabled = false;
            if (button.querySelector('span')) {
                button.querySelector('span').textContent = originalText;
            }
            if (buttonIcon) {
                buttonIcon.classList.remove('animate-spin');
            }
        });
    };

    // Show Request History Modal
    window.showRequestHistory = function(productId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        
        fetch(`/admin/ready-products/request-history/${productId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.requests && data.requests.length > 0) {
                const modal = document.getElementById('request-details-modal');
                const content = document.getElementById('request-details-content');
                
                // En son isteğin batch_request_id'sini global değişkene kaydet
                const latestRequest = data.requests[0];
                window.currentBatchRequestId = latestRequest.batch_request_id;
                window.currentRequestId = latestRequest.id;
                
                let html = '<div class="space-y-4">';
                html += '<div class="mb-4"><h4 class="text-sm font-semibold text-gray-900 dark:text-white">Toplam ' + data.requests.length + ' istek kaydı bulundu (En son yanıt önce gösteriliyor)</h4></div>';
                
                // Tüm istekleri göster (en son önce)
                data.requests.forEach((request, index) => {
                    const isLatest = index === 0;
                    const statusColor = request.status === 'success' ? 'green' : 
                                      request.status === 'failed' ? 'red' : 
                                      request.status === 'sent' ? 'yellow' : 'gray';
                    
                    html += `<div class="border ${isLatest ? 'border-2 border-blue-500' : 'border-gray-200 dark:border-gray-700'} rounded-lg p-4 ${isLatest ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-white dark:bg-gray-800'}">`;
                    
                    if (isLatest) {
                        html += '<div class="mb-2"><span class="px-2 py-1 bg-blue-600 text-white text-xs font-semibold rounded">EN SON YANIT</span></div>';
                    }
                    
                    // Request Info
                    html += '<div class="mb-3">';
                    html += '<h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">İstek #' + (data.requests.length - index) + ' - ' + request.created_at + '</h5>';
                    html += '<div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">';
                    html += `<div><span class="text-gray-600 dark:text-gray-400">Durum:</span> <span class="font-semibold text-${statusColor}-600 dark:text-${statusColor}-400">${request.status}</span></div>`;
                    html += `<div><span class="text-gray-600 dark:text-gray-400">Batch ID:</span> <span class="font-mono text-gray-900 dark:text-white text-xs">${request.batch_request_id || 'N/A'}</span></div>`;
                    html += `<div><span class="text-gray-600 dark:text-gray-400">Ürün Sayısı:</span> <span class="font-semibold text-gray-900 dark:text-white">${request.items_count}</span></div>`;
                    if (request.success_count !== null) {
                        html += `<div><span class="text-gray-600 dark:text-gray-400">Başarılı:</span> <span class="font-semibold text-green-600 dark:text-green-400">${request.success_count}</span></div>`;
                    }
                    if (request.failed_count !== null) {
                        html += `<div><span class="text-gray-600 dark:text-gray-400">Başarısız:</span> <span class="font-semibold text-red-600 dark:text-red-400">${request.failed_count}</span></div>`;
                    }
                    html += `<div><span class="text-gray-600 dark:text-gray-400">Gönderim:</span> <span class="text-gray-900 dark:text-white text-xs">${request.sent_at || 'N/A'}</span></div>`;
                    html += `<div><span class="text-gray-600 dark:text-gray-400">Tamamlanma:</span> <span class="text-gray-900 dark:text-white text-xs">${request.completed_at || 'N/A'}</span></div>`;
                    html += '</div></div>';
                    
                    // Response Data (sadece en son yanıt için detaylı göster)
                    if (isLatest && request.response_data) {
                        html += '<div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 mb-2">';
                        html += '<h6 class="text-xs font-semibold text-gray-900 dark:text-white mb-2">API Yanıtı (Response Data)</h6>';
                        html += '<pre class="text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-x-auto max-h-40 overflow-y-auto">' + JSON.stringify(request.response_data, null, 2) + '</pre>';
                        html += '</div>';
                    }
                    
                    // Batch Status Data (sadece en son yanıt için detaylı göster)
                    if (isLatest && request.batch_status_data) {
                        // Description alanını kaldır ve yerine "..." koy
                        const batchStatusData = JSON.parse(JSON.stringify(request.batch_status_data));
                        if (batchStatusData.description !== undefined) {
                            batchStatusData.description = '...';
                        }
                        
                        // Items array'indeki description'ları da "..." ile değiştir
                        if (batchStatusData.items && Array.isArray(batchStatusData.items)) {
                            batchStatusData.items = batchStatusData.items.map(item => {
                                if (item.description !== undefined) {
                                    item.description = '...';
                                }
                                return item;
                            });
                        }
                        
                        html += '<div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 mb-2">';
                        html += '<h6 class="text-xs font-semibold text-gray-900 dark:text-white mb-2">Batch Durum Yanıtı (Batch Status Data)</h6>';
                        html += '<pre class="text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-x-auto max-h-40 overflow-y-auto">' + JSON.stringify(batchStatusData, null, 2) + '</pre>';
                        html += '</div>';
                    }
                    
                    // Error Message
                    if (request.error_message) {
                        html += '<div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-2 border border-red-200 dark:border-red-800">';
                        html += '<p class="text-xs text-red-800 dark:text-red-300">' + request.error_message + '</p>';
                        html += '</div>';
                    }
                    
                    // Diğer istekler için genişletilebilir detay butonu
                    if (!isLatest) {
                        html += '<button onclick="showRequestDetails(' + request.id + ')" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">Detayları Görüntüle</button>';
                    }
                    
                    html += '</div>';
                });
                
                html += '</div>';
                
                content.innerHTML = html;
                modal.classList.remove('hidden');
            } else {
                alert('Bu ürün için istek geçmişi bulunamadı.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu: ' + error.message);
        });
    };

    // Show Request Details Modal (Tek bir istek için)
    window.showRequestDetails = function(requestId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        
        fetch(`/admin/ready-products/request-details/${requestId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('request-details-modal');
                const content = document.getElementById('request-details-content');
                
                // Batch Request ID'yi global değişkene kaydet (refreshBatchStatus için)
                window.currentBatchRequestId = data.request.batch_request_id;
                window.currentRequestId = requestId;
                
                let html = '<div class="space-y-4">';
                
                // Request Info
                html += '<div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">';
                html += '<h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">İstek Bilgileri</h4>';
                html += '<div class="grid grid-cols-2 gap-3 text-sm">';
                html += `<div><span class="text-gray-600 dark:text-gray-400">Durum:</span> <span class="font-semibold text-gray-900 dark:text-white">${data.request.status}</span></div>`;
                html += `<div><span class="text-gray-600 dark:text-gray-400">Batch Request ID:</span> <span class="font-mono text-gray-900 dark:text-white">${data.request.batch_request_id || 'N/A'}</span></div>`;
                html += `<div><span class="text-gray-600 dark:text-gray-400">Ürün Sayısı:</span> <span class="font-semibold text-gray-900 dark:text-white">${data.request.items_count}</span></div>`;
                if (data.request.success_count !== null) {
                    html += `<div><span class="text-gray-600 dark:text-gray-400">Başarılı:</span> <span class="font-semibold text-green-600 dark:text-green-400">${data.request.success_count}</span></div>`;
                }
                if (data.request.failed_count !== null) {
                    html += `<div><span class="text-gray-600 dark:text-gray-400">Başarısız:</span> <span class="font-semibold text-red-600 dark:text-red-400">${data.request.failed_count}</span></div>`;
                }
                html += `<div><span class="text-gray-600 dark:text-gray-400">Gönderim Zamanı:</span> <span class="text-gray-900 dark:text-white">${data.request.sent_at || 'N/A'}</span></div>`;
                html += `<div><span class="text-gray-600 dark:text-gray-400">Tamamlanma Zamanı:</span> <span class="text-gray-900 dark:text-white">${data.request.completed_at || 'N/A'}</span></div>`;
                html += '</div></div>';
                
                // Response Data
                if (data.request.response_data) {
                    html += '<div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">';
                    html += '<h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">API Yanıtı (Response Data)</h4>';
                    html += '<pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded overflow-x-auto">' + JSON.stringify(data.request.response_data, null, 2) + '</pre>';
                    html += '</div>';
                }
                
                // Batch Status Data
                if (data.request.batch_status_data) {
                    // Description alanını kaldır ve yerine "..." koy
                    const batchStatusData = JSON.parse(JSON.stringify(data.request.batch_status_data));
                    if (batchStatusData.description !== undefined) {
                        batchStatusData.description = '...';
                    }
                    
                    // Items array'indeki description'ları da "..." ile değiştir
                    if (batchStatusData.items && Array.isArray(batchStatusData.items)) {
                        batchStatusData.items = batchStatusData.items.map(item => {
                            if (item.description !== undefined) {
                                item.description = '...';
                            }
                            return item;
                        });
                    }
                    
                    html += '<div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">';
                    html += '<h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Batch Durum Yanıtı (Batch Status Data)</h4>';
                    html += '<pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded overflow-x-auto">' + JSON.stringify(batchStatusData, null, 2) + '</pre>';
                    html += '</div>';
                }
                
                // Request Data
                if (data.request.request_data) {
                    html += '<div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">';
                    html += '<h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Gönderilen Veri (Request Data)</h4>';
                    html += '<pre class="text-xs bg-gray-100 dark:bg-gray-800 p-3 rounded overflow-x-auto max-h-96 overflow-y-auto">' + JSON.stringify(data.request.request_data, null, 2) + '</pre>';
                    html += '</div>';
                }
                
                // Error Message
                if (data.request.error_message) {
                    html += '<div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">';
                    html += '<h4 class="text-sm font-semibold text-red-900 dark:text-red-200 mb-2">Hata Mesajı</h4>';
                    html += `<p class="text-sm text-red-800 dark:text-red-300">${data.request.error_message}</p>`;
                    html += '</div>';
                }
                
                html += '</div>';
                
                content.innerHTML = html;
                modal.classList.remove('hidden');
            } else {
                alert('Hata: ' + (data.message || 'İstek detayları alınamadı.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu: ' + error.message);
        });
    };

    // Refresh Batch Status
    window.refreshBatchStatus = function() {
        if (!window.currentBatchRequestId) {
            alert('Batch Request ID bulunamadı.');
            return;
        }

        const btn = document.getElementById('refresh-batch-status-btn');
        const btnText = btn.querySelector('span');
        const originalText = btnText ? btnText.textContent : 'Tekrar Sorgula';
        
        // Butonu devre dışı bırak
        btn.disabled = true;
        if (btnText) {
            btnText.textContent = 'Sorgulanıyor...';
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        
        fetch(`{{ route("admin.ready-products.batch-status", ":batchRequestId") }}`.replace(':batchRequestId', window.currentBatchRequestId), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Başarılı - modal içeriğini yenile
                if (window.currentRequestId) {
                    showRequestDetails(window.currentRequestId);
                } else {
                    alert('Batch durumu güncellendi. Sayfa yenileniyor...');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                alert('Hata: ' + (data.message || 'Batch durumu kontrol edilemedi.'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu: ' + error.message);
        })
        .finally(() => {
            // Butonu tekrar etkinleştir
            btn.disabled = false;
            if (btnText) {
                btnText.textContent = originalText;
            }
        });
    };

    // Close Request Details Modal
    window.closeRequestDetailsModal = function() {
        document.getElementById('request-details-modal').classList.add('hidden');
        // Global değişkenleri temizle
        window.currentBatchRequestId = null;
        window.currentRequestId = null;
    };

    // Modal dışına tıklanınca kapat
    document.getElementById('request-details-modal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeRequestDetailsModal();
        }
    });
});
</script>
@endsection

