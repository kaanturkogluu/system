@extends('web.layouts.app')

@section('title', 'Ürünler')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Search Bar -->
    <div class="mb-8">
        <form method="GET" action="{{ route('products.index') }}" class="flex gap-4">
            <div class="flex-1 relative">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}"
                    placeholder="Ürün, marka veya kategori ara..." 
                    class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                >
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
            <button type="submit" class="px-6 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition">
                Ara
            </button>
            @if(request('search'))
                <a href="{{ route('products.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Temizle
                </a>
            @endif
        </form>
    </div>

    <!-- Results Info -->
    @if(request('search'))
        <div class="mb-4 text-gray-600">
            <strong>{{ $products->total() }}</strong> ürün bulundu
        </div>
    @endif

    <!-- Products Grid -->
    @if($products->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            @foreach($products as $product)
                <a href="{{ route('products.show', $product) }}" class="bg-white rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden group">
                    <!-- Product Image -->
                    <div class="relative aspect-square bg-gray-100 overflow-hidden">
                        @if($product->images->count() > 0)
                            <img 
                                src="{{ $product->images->first()->url }}" 
                                alt="{{ $product->title }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                onerror="this.src='https://via.placeholder.com/300x300?text=Resim+Yok'"
                            >
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <i class="fas fa-image text-4xl"></i>
                            </div>
                        @endif
                        @if($product->variants->sum('stock') <= 0)
                            <div class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-semibold">
                                Stokta Yok
                            </div>
                        @endif
                    </div>
                    
                    <!-- Product Info -->
                    <div class="p-4">
                        <!-- Brand -->
                        @if($product->brand)
                            <div class="text-xs text-gray-500 mb-1">{{ $product->brand->name }}</div>
                        @endif
                        
                        <!-- Title -->
                        <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 h-12">
                            {{ $product->title }}
                        </h3>
                        
                        <!-- Price -->
                        <div class="flex items-center justify-between">
                            @if($product->variants->count() > 0)
                                @php
                                    $minPrice = $product->variants->min('price');
                                    $maxPrice = $product->variants->max('price');
                                @endphp
                                @if($minPrice == $maxPrice)
                                    <span class="text-lg font-bold text-orange-500">{{ number_format($minPrice, 2) }} ₺</span>
                                @else
                                    <span class="text-lg font-bold text-orange-500">{{ number_format($minPrice, 2) }} - {{ number_format($maxPrice, 2) }} ₺</span>
                                @endif
                            @else
                                <span class="text-lg font-bold text-orange-500">Fiyat Belirtilmemiş</span>
                            @endif
                        </div>
                        
                        <!-- Stock Info -->
                        @if($product->variants->sum('stock') > 0)
                            <div class="text-xs text-green-600 mt-2">
                                <i class="fas fa-check-circle"></i> Stokta var
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @else
        <div class="text-center py-16">
            <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">Ürün bulunamadı</h3>
            <p class="text-gray-500">Arama kriterlerinizi değiştirerek tekrar deneyin.</p>
        </div>
    @endif
</div>
@endsection

