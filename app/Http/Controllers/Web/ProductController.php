<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'category', 'images', 'variants'])
            ->where('status', '!=', 'PASSIVE')
            ->where('status', '!=', 'DRAFT');

        // Arama
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhereHas('brand', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(30);

        return view('web.products.index', compact('products'));
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        $product->load([
            'brand',
            'category',
            'images',
            'variants',
            'productAttributes.attribute',
            'productAttributes.attributeValue',
            'currencyRelation'
        ]);

        // İlgili ürünler (aynı kategoriden)
        $relatedProducts = Product::with(['brand', 'images'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', '!=', 'PASSIVE')
            ->where('status', '!=', 'DRAFT')
            ->limit(8)
            ->get();

        return view('web.products.show', compact('product', 'relatedProducts'));
    }
}
