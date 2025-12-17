@extends('admin.layouts.app')

@section('title', 'Ürün Detayı - ' . $product->sku)
@section('page-title', 'Ürün Detayı')

@section('content')
{{--
    Controller Query Örneği:
    
    public function show($id)
    {
        $product = Product::with(['brand', 'category', 'variants'])
            ->findOrFail($id);
        
        // Import item payload'ını çek (varsa)
        $importItem = ImportItem::where('feed_run_id', function($query) use ($product) {
            $query->select('feed_run_id')
                ->from('import_items')
                ->where('sku', $product->sku)
                ->orWhere('external_id', $product->source_reference)
                ->limit(1);
        })->first();
        
        return view('admin.xml-products.show', compact('product', 'importItem'));
    }
--}}

<div class="space-y-6">
    <!-- Back Button -->
    <div>
        <a 
            href="{{ route('admin.xml-products.index') }}"
            class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition duration-200"
        >
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Ürün Listesine Dön
        </a>
    </div>

    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $product->title }}</h2>
                <div class="flex items-center mt-2 space-x-4">
                    <span class="text-sm text-gray-600 dark:text-gray-400">SKU: <span class="font-mono text-blue-600 dark:text-blue-400">{{ $product->sku }}</span></span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">ID: {{ $product->id }}</span>
                </div>
            </div>
            <div>
                @php
                    $statusColors = [
                        'DRAFT' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        'IMPORTED' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                        'READY' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                        'EXPORTED' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                        'PASSIVE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    ];
                @endphp
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full {{ $statusColors[$product->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ $product->status }}
                </span>
            </div>
        </div>
    </div>

    <!-- Product Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Basic Information -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Temel Bilgiler
            </h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Kaynak Tipi:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 uppercase">{{ $product->source_type }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Kaynak Referans:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $product->source_reference ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">SKU:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $product->sku }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Barkod:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $product->barcode ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Ürün Tipi:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $product->product_type }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Marka:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $product->brand->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Kategori:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $product->category->name ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Pricing & Dates -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Fiyat & Tarihler
            </h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Referans Fiyat:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                        @if($product->reference_price)
                            {{ number_format($product->reference_price, 2) }} {{ $product->currency }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Para Birimi:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $product->currency }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Oluşturulma:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $product->created_at->format('d.m.Y H:i:s') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Son Güncelleme:</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $product->updated_at->format('d.m.Y H:i:s') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Description -->
    @if($product->description)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
            </svg>
            Açıklama
        </h3>
        <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">{{ $product->description }}</div>
    </div>
    @endif

    <!-- Product Images -->
    @if($product->images && $product->images->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Ürün Resimleri ({{ $product->images->count() }} adet)
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($product->images as $image)
            <div class="relative group">
                <div class="aspect-w-1 aspect-h-1 w-full overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-900">
                    <img 
                        src="{{ $image->url }}" 
                        alt="Ürün resmi {{ $image->sort_order + 1 }}"
                        class="h-48 w-full object-cover object-center group-hover:opacity-75 transition duration-200"
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23ddd%22 width=%22200%22 height=%22200%22/%3E%3Ctext fill=%22%23999%22 font-family=%22sans-serif%22 font-size=%2214%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3EResim Yüklenemedi%3C/text%3E%3C/svg%3E'"
                    >
                </div>
                @if($image->sort_order === 0)
                <div class="absolute top-2 left-2">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-blue-600 text-white">
                        Ana Resim
                    </span>
                </div>
                @endif
                <div class="mt-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $image->url }}">
                        {{ $image->url }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Variants -->
    @if($product->variants && $product->variants->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Varyantlar
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">SKU</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Barkod</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fiyat</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Stok</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($product->variants as $variant)
                    <tr>
                        <td class="px-4 py-2 text-sm font-mono text-blue-600 dark:text-blue-400">{{ $variant->sku }}</td>
                        <td class="px-4 py-2 text-sm font-mono text-gray-700 dark:text-gray-300">{{ $variant->barcode ?? '—' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ number_format($variant->price, 2) }} {{ $variant->currency }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $variant->stock }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Raw XML -->
    @if($product->raw_xml)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
            Ham XML Verisi
        </h3>
        <div class="bg-gray-50 dark:bg-gray-900 rounded p-4 overflow-x-auto">
            <pre class="text-xs text-gray-800 dark:text-gray-200 font-mono">{{ json_encode($product->raw_xml, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
    @endif

    <!-- Import Item Payload -->
    @if(isset($importItem) && $importItem)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Import Item Payload
            <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">(Import Item ID: {{ $importItem->id }})</span>
        </h3>
        <div class="space-y-3 mb-4">
            <div class="flex items-center space-x-4 text-sm">
                <span class="text-gray-600 dark:text-gray-400">Feed Run ID: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $importItem->feed_run_id }}</span></span>
                <span class="text-gray-600 dark:text-gray-400">External ID: <span class="font-mono text-gray-900 dark:text-gray-100">{{ $importItem->external_id ?? '—' }}</span></span>
                <span class="text-gray-600 dark:text-gray-400">Status: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $importItem->status }}</span></span>
            </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-900 rounded p-4 overflow-x-auto">
            <pre class="text-xs text-gray-800 dark:text-gray-200 font-mono">{{ json_encode($importItem->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
    @endif
</div>
@endsection

