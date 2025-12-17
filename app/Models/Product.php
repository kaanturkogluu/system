<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'source_type',
        'source_reference',
        'sku',
        'barcode',
        'title',
        'description',
        'brand_id',
        'category_id',
        'product_type',
        'reference_price',
        'currency',
        'status',
        'raw_xml',
    ];

    protected $casts = [
        'brand_id' => 'integer',
        'category_id' => 'integer',
        'reference_price' => 'decimal:2',
        'raw_xml' => 'array',
    ];

    /**
     * Brand ilişkisi
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Category ilişkisi
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Product variants ilişkisi
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /**
     * Product images ilişkisi
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }
}

