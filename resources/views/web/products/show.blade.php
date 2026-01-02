@extends('web.layouts.app')

@section('title', $product->title)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="mb-6 text-sm text-gray-600">
        <a href="{{ route('products.index') }}" class="hover:text-orange-500">Ana Sayfa</a>
        <span class="mx-2">/</span>
        @if($product->category)
            <span>{{ $product->category->name }}</span>
            <span class="mx-2">/</span>
        @endif
        <span class="text-gray-900">{{ $product->title }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <!-- Product Images -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            @if($product->images->count() > 0)
                <div class="mb-4">
                    <img 
                        src="{{ $product->images->first()->url }}" 
                        alt="{{ $product->title }}"
                        id="mainImage"
                        class="w-full h-96 object-contain rounded-lg"
                        onerror="this.src='https://via.placeholder.com/600x600?text=Resim+Yok'"
                    >
                </div>
                @if($product->images->count() > 1)
                    <div class="grid grid-cols-4 gap-2">
                        @foreach($product->images as $image)
                            <img 
                                src="{{ $image->url }}" 
                                alt="{{ $product->title }}"
                                onclick="document.getElementById('mainImage').src = this.src"
                                class="w-full h-20 object-cover rounded border-2 border-transparent hover:border-orange-500 cursor-pointer"
                                onerror="this.src='https://via.placeholder.com/150x150?text=Resim+Yok'"
                            >
                        @endforeach
                    </div>
                @endif
            @else
                <div class="w-full h-96 flex items-center justify-center bg-gray-100 rounded-lg">
                    <i class="fas fa-image text-6xl text-gray-300"></i>
                </div>
            @endif
        </div>

        <!-- Product Info -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <!-- Brand -->
            @if($product->brand)
                <div class="text-sm text-gray-500 mb-2">{{ $product->brand->name }}</div>
            @endif

            <!-- Title -->
            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $product->title }}</h1>

            <!-- Price -->
            <div class="mb-6">
                @if($product->variants->count() > 0)
                    @php
                        $minPrice = $product->variants->min('price');
                        $maxPrice = $product->variants->max('price');
                    @endphp
                    @if($minPrice == $maxPrice)
                        <div class="text-3xl font-bold text-orange-500">{{ number_format($minPrice, 2) }} ₺</div>
                    @else
                        <div class="text-3xl font-bold text-orange-500">{{ number_format($minPrice, 2) }} - {{ number_format($maxPrice, 2) }} ₺</div>
                    @endif
                @else
                    <div class="text-3xl font-bold text-gray-400">Fiyat Belirtilmemiş</div>
                @endif
            </div>

            <!-- Stock Status -->
            @php
                $totalStock = $product->variants->sum('stock');
            @endphp
            @if($totalStock > 0)
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center text-green-700">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span class="font-semibold">Stokta var ({{ $totalStock }} adet)</span>
                    </div>
                </div>
            @else
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center text-red-700">
                        <i class="fas fa-times-circle mr-2"></i>
                        <span class="font-semibold">Stokta yok</span>
                    </div>
                </div>
            @endif

            <!-- SKU & Barcode -->
            <div class="mb-6 space-y-2 text-sm text-gray-600">
                <div><strong>SKU:</strong> {{ $product->sku }}</div>
                @if($product->barcode)
                    <div><strong>Barkod:</strong> {{ $product->barcode }}</div>
                @endif
                @if($product->desi)
                    <div><strong>Desi:</strong> {{ number_format($product->desi, 2) }}</div>
                @endif
            </div>

            <!-- Description -->
            @if($product->description)
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Ürün Açıklaması</h3>
                    <div class="text-gray-700 prose max-w-none">{!! $product->description !!}</div>
                </div>
            @endif

            <!-- Variants -->
            @if($product->variants->count() > 1)
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 mb-3">Varyantlar</h3>
                    <div class="space-y-2">
                        @foreach($product->variants as $variant)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <div class="font-medium">{{ $variant->sku }}</div>
                                    <div class="text-sm text-gray-600">Stok: {{ $variant->stock }}</div>
                                </div>
                                <div class="text-lg font-bold text-orange-500">{{ number_format($variant->price, 2) }} ₺</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Product Attributes -->
    @if($product->productAttributes->count() > 0)
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Ürün Özellikleri</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($product->productAttributes as $productAttribute)
                    <div class="flex border-b border-gray-200 pb-2">
                        <div class="font-semibold text-gray-700 w-1/2">{{ $productAttribute->attribute->name ?? '—' }}:</div>
                        <div class="text-gray-600 w-1/2">
                            @if($productAttribute->attributeValue)
                                {{ $productAttribute->attributeValue->value }}
                            @elseif($productAttribute->value_string)
                                {{ $productAttribute->value_string }}
                            @elseif($productAttribute->value_number)
                                {{ number_format($productAttribute->value_number, 2) }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Related Products -->
    @if($relatedProducts->count() > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Benzer Ürünler</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-8 gap-4">
                @foreach($relatedProducts as $relatedProduct)
                    <a href="{{ route('products.show', $relatedProduct) }}" class="bg-white rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden group">
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            @if($relatedProduct->images->count() > 0)
                                <img 
                                    src="{{ $relatedProduct->images->first()->url }}" 
                                    alt="{{ $relatedProduct->title }}"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                                    onerror="this.src='https://via.placeholder.com/200x200?text=Resim+Yok'"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                    <i class="fas fa-image text-2xl"></i>
                                </div>
                            @endif
                        </div>
                        <div class="p-2">
                            <h3 class="text-xs font-semibold text-gray-900 line-clamp-2 mb-1">{{ $relatedProduct->title }}</h3>
                            @if($relatedProduct->variants->count() > 0)
                                <div class="text-sm font-bold text-orange-500">{{ number_format($relatedProduct->variants->min('price'), 2) }} ₺</div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

