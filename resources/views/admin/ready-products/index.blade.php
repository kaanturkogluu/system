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
                                @if($product->category && $product->category->trendyolCategory)
                                    <span>Trendyol Kategori ID: <span class="font-mono text-green-600 dark:text-green-400">{{ $product->category->trendyolCategory->marketplace_category_id }}</span></span>
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

<script>
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
});
</script>
@endsection

