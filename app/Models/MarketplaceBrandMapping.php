<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceBrandMapping extends Model
{
    use HasFactory;

    protected $table = 'marketplace_brand_mappings';

    protected $fillable = [
        'marketplace_id',
        'brand_id',
        'marketplace_brand_id',
        'marketplace_brand_name',
        'status',
    ];

    protected $casts = [
        'marketplace_id' => 'integer',
        'brand_id' => 'integer',
    ];

    public function marketplace()
    {
        return $this->belongsTo(Marketplace::class, 'marketplace_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}

