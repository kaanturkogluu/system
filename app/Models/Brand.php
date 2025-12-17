<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'brands';

    protected $fillable = [
        'name',
        'normalized_name',
        'slug',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function marketplaceMappings()
    {
        return $this->hasMany(MarketplaceBrandMapping::class, 'brand_id');
    }
}

