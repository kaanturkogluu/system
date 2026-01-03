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
        'desi',
        'commission_rate',
        'vat_rate',
        'brand_id',
        'category_id',
        'product_type',
        'reference_price',
        'currency',
        'currency_id',
        'status',
        'raw_xml',
    ];

    protected $casts = [
        'brand_id' => 'integer',
        'category_id' => 'integer',
        'currency_id' => 'integer',
        'reference_price' => 'decimal:2',
        'desi' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'vat_rate' => 'integer',
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

    /**
     * Product attributes ilişkisi
     */
    public function productAttributes()
    {
        return $this->hasMany(ProductAttribute::class, 'product_id');
    }

    /**
     * Currency ilişkisi
     */
    public function currencyRelation()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}

