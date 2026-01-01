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
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Toplam: <span class="font-semibold">{{ $products->total() }}</span> ürün
        </div>
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
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
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
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
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
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">API Formatı</h4>
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
                        <pre class="text-xs text-gray-800 dark:text-gray-200 font-mono">{{ json_encode(['items' => [$apiData]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
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
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Stok</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ is_string($apiData['quantity']) && $apiData['quantity'] === 'Sistemde Veri yok' ? '❌ Yok' : '✅ ' . $apiData['quantity'] }}
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
@endsection

