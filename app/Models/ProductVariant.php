<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'price',
        'currency',
        'price_sk',
        'price_bayi',
        'price_ozel',
        'currency_id',
        'stock',
        'attributes',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'price' => 'decimal:2',
        'price_sk' => 'decimal:6',
        'price_bayi' => 'decimal:6',
        'price_ozel' => 'decimal:6',
        'currency_id' => 'integer',
        'stock' => 'integer',
        'attributes' => 'array',
    ];

    /**
     * Product ilişkisi
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Currency ilişkisi
     */
    public function currencyRelation()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}

