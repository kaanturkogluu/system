<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImportItem;
use App\Models\Product;
use Illuminate\Http\Request;

class XmlProductController extends Controller
{
    /**
     * XML ürünleri listesi
     */
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'category', 'images'])
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

    /**
     * XML ürün detayı
     */
    public function show($id)
    {
        $product = Product::with(['brand.originCountry', 'category.parent', 'variants', 'images'])
            ->where('source_type', 'xml')
            ->findOrFail($id);
        
        // İlgili import item'ı bul (varsa)
        $importItem = ImportItem::where(function($query) use ($product) {
            $query->where('sku', $product->sku)
                  ->orWhere('external_id', $product->source_reference);
        })
        ->latest()
        ->first();
        
        return view('admin.xml-products.show', compact('product', 'importItem'));
    }
}

