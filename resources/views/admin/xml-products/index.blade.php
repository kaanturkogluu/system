@extends('admin.layouts.app')

@section('title', 'XML Ürünleri')
@section('page-title', 'XML Ürünleri')

@section('content')
{{-- 
    Controller Query Örneği:
    
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'category'])
            ->where('source_type', 'xml');
        
        if ($request->filled('sku')) {
            $query->where('sku', 'like', '%' . $request->sku . '%');
        }
        
        if ($request->filled('barcode')) {
            $query->where('barcode', 'like', '%' . $request->barcode . '%');
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $products = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.xml-products.index', compact('products'));
    }
--}}

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">XML Ürünleri</h2>
            <p class="text-gray-600 dark:text-gray-400 mt-1">XML feed'lerden çekilen tüm ürünler</p>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Toplam: <span class="font-semibold">{{ $products->total() }}</span> ürün
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <form method="GET" action="{{ route('admin.xml-products.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Barkod</label>
                <input 
                    type="text" 
                    name="barcode" 
                    value="{{ request('barcode') }}"
                    placeholder="Barkod ile ara..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durum</label>
                <select 
                    name="status"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                >
                    <option value="">Tümü</option>
                    <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                    <option value="IMPORTED" {{ request('status') == 'IMPORTED' ? 'selected' : '' }}>IMPORTED</option>
                    <option value="READY" {{ request('status') == 'READY' ? 'selected' : '' }}>READY</option>
                    <option value="EXPORTED" {{ request('status') == 'EXPORTED' ? 'selected' : '' }}>EXPORTED</option>
                    <option value="PASSIVE" {{ request('status') == 'PASSIVE' ? 'selected' : '' }}>PASSIVE</option>
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
                    href="{{ route('admin.xml-products.index') }}"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition duration-200"
                >
                    Temizle
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Resim</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Başlık</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Marka</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Barkod</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fiyat</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Durum</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Oluşturma</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">İşlem</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            {{ $product->id }}
                        </td>
                        <td class="px-4 py-3">
                            @if($product->images && $product->images->count() > 0)
                                <img 
                                    src="{{ $product->images->first()->url }}" 
                                    alt="{{ $product->title }}"
                                    class="h-12 w-12 object-cover rounded"
                                    onerror="this.style.display='none'"
                                >
                            @else
                                <div class="h-12 w-12 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="font-mono text-blue-600 dark:text-blue-400">{{ $product->sku }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            <div class="max-w-xs truncate" title="{{ $product->title }}">
                                {{ $product->title }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $product->brand->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $product->category->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="font-mono text-gray-600 dark:text-gray-400">{{ $product->barcode ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            @if($product->reference_price)
                                {{ number_format($product->reference_price, 2) }} {{ $product->currency }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $statusColors = [
                                    'DRAFT' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    'IMPORTED' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                    'READY' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                    'EXPORTED' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                    'PASSIVE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                ];
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$product->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $product->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $product->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <a 
                                href="{{ route('admin.xml-products.show', $product->id) }}"
                                class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition duration-200"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Detay
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="font-medium">Ürün bulunamadı</p>
                            <p class="text-sm mt-1">Henüz XML'den ürün çekilmemiş veya filtrelerinize uygun ürün yok.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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

